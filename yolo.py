import cv2
from ultralytics import YOLO

# 1. Initialize the Webcam
cap = cv2.VideoCapture(0)

# Optional: Set resolution
cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480  )

# 2. Load YOLOv8 model
model = YOLO("yolov8n.pt")

while True:
    # Capture frame-by-frame
    ret, frame = cap.read()
    
    if not ret:
        print("Error: Failed to grab frame.")
        break

    # 3. Run YOLO inference
    # ADDED: classes=[39] to only detect bottles
    results = model(frame, stream=True, classes=[39])
    
    for result in results:
        # Plot detection boxes on the frame
        annotated_frame = result.plot()
        
        # 4. Calculate FPS
        inference_time = result.speed['inference']
        if inference_time > 0:
            fps = 1000 / inference_time
            text = f'FPS: {fps:.1f}'
            
            # Setup text positioning
            font = cv2.FONT_HERSHEY_SIMPLEX
            text_size = cv2.getTextSize(text, font, 1, 2)[0]
            text_x = annotated_frame.shape[1] - text_size[0] - 10
            text_y = text_size[1] + 10

            cv2.putText(annotated_frame, text, (text_x, text_y), font, 1, (0, 255, 0), 2, cv2.LINE_AA)

        # Display the frame
        cv2.imshow("Bottle Detection", annotated_frame)

    # Exit on 'q' key
    if cv2.waitKey(1) & 0xFF == ord("q"):
        break

# 5. Cleanup
cap.release()
cv2.destroyAllWindows()