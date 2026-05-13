import cv2
import time
# import requests # Uncomment this if you plan to send data to your api.php
from ultralytics import YOLO

# ==========================================
# 1. INITIALIZE MODEL & CONFIGURATION
# ==========================================
# Load the standard YOLOv8 nano model (or your custom trained model)
model = YOLO('yolov8n.pt') 

# COCO Class ID for 'bottle' is 39
BOTTLE_CLASS_ID = 39 

# ==========================================
# 2. CALIBRATION THRESHOLDS (PIXELS)
# ==========================================
# IMPORTANT: You MUST calibrate these pixel values based on your actual 
# RVM camera setup. Insert a 500ml, 1L, and 1.5L bottle and check the 
# terminal output for their 'Longest Side (px)' to adjust these numbers.

# Example pixel thresholds for the longest side of the bounding box:
THRESHOLD_250ML_MAX = 180   # If length <= 180px, it's 250ml
THRESHOLD_500ML_MAX = 250   # If length > 180px and <= 250px, it's 500ml
THRESHOLD_1000ML_MAX = 380  # If length > 250px and <= 380px, it's 1L
# Anything greater than 380px will be considered 1.5L

def estimate_size(width, height):
    """
    Estimates the bottle volume based on the bounding box size in pixels.
    We use the longest side (max of width or height) to account for 
    bottles being inserted upright or sideways.
    """
    longest_side = max(width, height)
    
    if longest_side <= THRESHOLD_250ML_MAX:
        return "250ml"
    elif longest_side <= THRESHOLD_500ML_MAX:
        return "500ml"
    elif longest_side <= THRESHOLD_1000ML_MAX:
        return "1000ml (1L)"
    else:
        return "1.5L"

# ==========================================
# 3. MAIN VIDEO LOOP
# ==========================================
def main():
    # Initialize video capture (0 for default webcam, or adjust to your camera index)
    cap = cv2.VideoCapture(0)
    
    if not cap.isOpened():
        print("Error: Could not open camera.")
        return

    print("Starting RECYCOIN Bottle Detection. Press 'q' to quit.")

    while True:
        ret, frame = cap.read()
        if not ret:
            print("Failed to grab frame.")
            break

        # Run YOLO inference only looking for bottles (classes=[39])
        results = model(frame, classes=[BOTTLE_CLASS_ID], verbose=False)

        for result in results:
            boxes = result.boxes
            
            for box in boxes:
                # Extract coordinates and dimensions
                x1, y1, x2, y2 = map(int, box.xyxy[0])
                w, h = float(box.xywh[0][2]), float(box.xywh[0][3])
                conf = float(box.conf[0])

                # Only process if confidence is above a certain threshold (e.g., 60%)
                if conf > 0.60:
                    # Determine the size based on bounding box
                    size_category = estimate_size(w, h)
                    longest_side = max(w, h)
                    
                    # Print to terminal for calibration purposes
                    # print(f"Detected: {size_category} | Longest Side: {longest_side:.1f}px | Confidence: {conf:.2f}")

                    # Draw Bounding Box
                    cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 255, 0), 2)
                    
                    # Draw Label with Size
                    label = f"PET {size_category} ({conf:.2f})"
                    
                    # Background for text for better readability
                    (text_w, text_h), _ = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 0.6, 2)
                    cv2.rectangle(frame, (x1, y1 - 25), (x1 + text_w, y1), (0, 255, 0), -1)
                    cv2.putText(frame, label, (x1, y1 - 5), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 0), 2)

                    # ---------------------------------------------------------
                    # OPTIONAL: Send data to your PHP Backend (api.php)
                    # ---------------------------------------------------------
                    # payload = {
                    #     "bottle_size": size_category,
                    #     "confidence": conf,
                    #     "points": 5 if size_category == "500ml" else 10 # Example point logic
                    # }
                    # try:
                    #     requests.post("http://localhost/reycoin/api.php", data=payload)
                    #     time.sleep(2) # Prevent spamming the API for the same bottle
                    # except Exception as e:
                    #     print(f"API Error: {e}")

        # Show the video feed
        cv2.imshow("RECYCOIN - PET Bottle Detection", frame)

        # Break loop on 'q' key press
        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    # Cleanup
    cap.release()
    cv2.destroyAllWindows()

if __name__ == "__main__":
    main()