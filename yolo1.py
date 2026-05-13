import cv2
import math
import time
from ultralytics import YOLO, YOLOWorld

# ==========================================\n# 1. INITIALIZE MODELS\n# ==========================================

# Standard YOLOv8 for general bottle detection (COCO class 39)
model_std = YOLO('yolov8n.pt')
BOTTLE_CLASS_ID = 39 

# YOLO-World for specific non-PET bottle detection
model_world = YOLOWorld('yolov8s-world.pt')
model_world.set_classes(["non-pet bottle"])

# ==========================================\n# 2. SETUP VIDEO STREAM\n# ==========================================
cap = cv2.VideoCapture(0) # Use 0 for webcams, or replace with video path
cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)

# --- OPTIMIZATION VARIABLES ---
PROCESS_EVERY_N_FRAMES = 3  # Run AI every 3rd frame (massive speedup)
frame_count = 0
prev_time = 0

# Store the last known detections to draw on skipped frames to prevent flickering
last_pet_boxes = []
last_reject_boxes = []

print("Starting Recycoin Bottle Detection...")

while True:
    success, frame = cap.read()
    if not success:
        print("Failed to grab frame or video ended.")
        break

    frame_count += 1

    # ==========================================\n    # 3. RUN AI INFERENCE (ONLY ON Nth FRAME)\n    # ==========================================
    if frame_count % PROCESS_EVERY_N_FRAMES == 0:
        last_pet_boxes.clear()
        last_reject_boxes.clear()
        
        # OPTIMIZATION: imgsz=320 (Downscales image for AI, huge CPU speedup)
        results_std = model_std(frame, stream=True, verbose=False, imgsz=320)
        
        for r in results_std:
            boxes = r.boxes
            for box in boxes:
                cls = int(box.cls[0])
                if cls == BOTTLE_CLASS_ID:
                    x1, y1, x2, y2 = box.xyxy[0]
                    conf = math.ceil((box.conf[0] * 100)) / 100
                    last_pet_boxes.append((int(x1), int(y1), int(x2), int(y2), conf))

        results_world = model_world(frame, stream=True, verbose=False, conf=0.15, imgsz=320)
        
        for r in results_world:
            boxes = r.boxes
            for box in boxes:
                x1, y1, x2, y2 = box.xyxy[0]
                conf = math.ceil((box.conf[0] * 100)) / 100
                cls_name = model_world.names[int(box.cls[0])]
                last_reject_boxes.append((int(x1), int(y1), int(x2), int(y2), conf, cls_name))

    # ==========================================\n    # 4. DRAW BOUNDING BOXES\n    # ==========================================
    
    # Draw PET Boxes
    for (x1, y1, x2, y2, conf) in last_pet_boxes:
        cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 255, 0), 2)
        cv2.putText(frame, f'Bottle (PET) {conf}', (max(0, x1), max(20, y1 - 10)),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2)

    # Draw Reject Boxes
    for (x1, y1, x2, y2, conf, cls_name) in last_reject_boxes:
        cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 0, 255), 2)
        cv2.putText(frame, f'REJECT: {cls_name} {conf}', (max(0, x1), max(20, y1 - 10)),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 255), 2)

    # Calculate and Display FPS
    curr_time = time.time()
    fps = 1 / (curr_time - prev_time) if prev_time else 0
    prev_time = curr_time
    cv2.putText(frame, f'FPS: {int(fps)}', (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 1, (255, 255, 0), 2)

    # Show the frame
    cv2.imshow('Recycoin Detection', frame)

    if cv2.waitKey(1) & 0xFF == ord('q'):
        break

cap.release()
cv2.destroyAllWindows()