import cv2
import math
from ultralytics import YOLO, YOLOWorld

# ==========================================
# 1. INITIALIZE MODELS
# ==========================================

# Standard YOLOv8 for general bottle detection (COCO class 39)
# Assuming standard bottle detection implies PET bottles for your RVM
model_std = YOLO('yolov8n.pt')
BOTTLE_CLASS_ID = 39 

# YOLO-World for specific non-PET bottle detection
model_world = YOLOWorld('yolov8s-world.pt')
# Set custom classes for YOLO-World to identify items to reject
# model_world.set_classes(["non-pet bottle", "glass bottle", "aluminum can"])
model_world.set_classes(["non-pet bottle"])

# ==========================================
# 2. SETUP VIDEO STREAM
# ==========================================
cap = cv2.VideoCapture(0) # Use 0 for webcams, or replace with video path
cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)

print("Starting Recycoin Bottle Detection...")

while True:
    success, frame = cap.read()
    if not success:
        print("Failed to grab frame or video ended.")
        break

    # ==========================================
    # 3. STANDARD BOTTLE DETECTION (PET)
    # ==========================================
    results_std = model_std(frame, stream=True, verbose=False)
    
    for r in results_std:
        boxes = r.boxes
        for box in boxes:
            cls = int(box.cls[0])
            
            # Filter only for 'bottle' class in standard YOLO
            if cls == BOTTLE_CLASS_ID:
                x1, y1, x2, y2 = box.xyxy[0]
                x1, y1, x2, y2 = int(x1), int(y1), int(x2), int(y2)
                conf = math.ceil((box.conf[0] * 100)) / 100

                # Draw Green Box for standard Bottle (Assumed PET)
                cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 255, 0), 2)
                cv2.putText(frame, f'Bottle (PET) {conf}', (max(0, x1), max(20, y1 - 10)),
                            cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2)

    # ==========================================
    # 4. YOLO-WORLD DETECTION (NON-PET)
    # ==========================================
    # We use a slightly higher confidence threshold to avoid false positives 
    results_world = model_world(frame, stream=True, verbose=False, conf=0.15)
    
    for r in results_world:
        boxes = r.boxes
        for box in boxes:
            x1, y1, x2, y2 = box.xyxy[0]
            x1, y1, x2, y2 = int(x1), int(y1), int(x2), int(y2)
            conf = math.ceil((box.conf[0] * 100)) / 100
            cls_name = model_world.names[int(box.cls[0])]

            # Draw Red Box for Non-PET or rejectable items
            cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 0, 255), 2)
            cv2.putText(frame, f'REJECT: {cls_name} {conf}', (max(0, x1), max(20, y1 + 20)), # Offset text to avoid overlap
                        cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 255), 2)

    # FPS text has been removed as requested

    # ==========================================
    # 5. DISPLAY OUTPUT
    # ==========================================
    cv2.imshow("Recycoin - Bottle Verification Stream", frame)

    # Press 'q' to quit the program
    if cv2.waitKey(1) & 0xFF == ord('q'):
        break

# Cleanup
cap.release()
cv2.destroyAllWindows()