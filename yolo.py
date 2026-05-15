import cv2
import time
import threading
import numpy as np
from flask import Flask, jsonify
from flask_cors import CORS
from ultralytics import YOLO

# ==========================================
# 1. INITIALIZE FLASK & YOLO MODEL
# ==========================================
app = Flask(__name__)
CORS(app)

model = YOLO('yolov8n.pt') 
BOTTLE_CLASS_ID = 39 

# ==========================================
# 2. STATE & CONFIGURATION
# ==========================================
is_camera_active = False
detected_queue = []
last_detection_time = 0
COOLDOWN_SECONDS = 3.0  # Prevent scanning the same bottle multiple times per second

THRESHOLD_250ML_MAX = 180   
THRESHOLD_500ML_MAX = 250   
THRESHOLD_1000ML_MAX = 380  

def estimate_size_and_points(width, height):
    """Returns size label and corresponding points"""
    longest_side = max(width, height)
    
    if longest_side <= THRESHOLD_250ML_MAX:
        return "250ml", 0.5
    elif longest_side <= THRESHOLD_500ML_MAX:
        return "500ml", 1.0
    elif longest_side <= THRESHOLD_1000ML_MAX:
        return "1L", 2.0
    else:
        return "1.5L", 3.0

def is_bottle_upright(roi):
    """
    Heuristic to determine if a detected PET bottle is upright.
    Compares the horizontal span of edges in the top quarter vs bottom quarter.
    Upright bottles have a narrow neck (top) and wide base (bottom).
    """
    h, w = roi.shape[:2]
    
    # If the bounding box is too small, default to valid to avoid math errors
    if h < 40 or w < 20:
        return True 

    # Convert to grayscale and detect edges
    gray = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
    edges = cv2.Canny(gray, 50, 150)

    # Analyze the top 25% and bottom 25% of the cropped bottle image
    quarter_h = h // 4
    top_region = edges[0:quarter_h, :]
    bottom_region = edges[h - quarter_h:h, :]

    def get_average_width(region):
        widths = []
        # For every row of pixels, find the distance between the leftmost and rightmost edge
        for row in region:
            pts = np.where(row > 0)[0]
            if len(pts) >= 2:
                widths.append(pts[-1] - pts[0])
        return float(np.mean(widths)) if widths else 0.0

    top_width = get_average_width(top_region)
    bottom_width = get_average_width(bottom_region)

    # If the bottom is completely missing but the top has width, it's upside down
    if top_width > 0 and bottom_width == 0:
        return False
        
    # An upright bottle should have a base wider than (or roughly equal to) the neck.
    # We use a 0.85 multiplier to allow for slight angles, camera distortion, or labels.
    return bottom_width >= (top_width * 0.85)


# ==========================================
# 3. FLASK API ENDPOINTS
# ==========================================
@app.route('/start')
def start_camera():
    """Triggered by PHP when modal opens"""
    global is_camera_active
    is_camera_active = True
    return jsonify({"status": "started"})

@app.route('/stop')
def stop_camera():
    """Triggered by PHP when modal closes to save CPU & hide GUI"""
    global is_camera_active
    is_camera_active = False
    return jsonify({"status": "stopped"})

@app.route('/poll')
def poll_detection():
    """Polled by PHP to check if a bottle was successfully detected"""
    global detected_queue
    if len(detected_queue) > 0:
        data = detected_queue.pop(0) # Remove from queue and return
        return jsonify({"status": "success", "size": data["size"], "points": data["points"]})
    return jsonify({"status": "empty"})

# ==========================================
# 4. OPENCV NATIVE GUI LOOP (Main Thread)
# ==========================================
def main_gui_loop():
    global is_camera_active, detected_queue, last_detection_time
    cap = None
    window_name = "PET Bottle Scanner"

    print("PET Bottle Scanner ready. Waiting for start command...")

    while True:
        if not is_camera_active:
            # If camera shouldn't be active, release resources and close window
            if cap is not None:
                cap.release()
                cap = None
                cv2.destroyAllWindows()
            time.sleep(0.2)
            continue

        # If camera should be active but isn't opened yet
        if cap is None:
            cap = cv2.VideoCapture(0)
            
            # --- RASPBERRY PI 5 PERFORMANCE OPTIMIZATIONS ---
            # 1. Set Video stream explicitly to 480p
            cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
            cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
            
            # 2. Limit buffer size to 1 to drop old frames and avoid UI lag
            cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
            
            # 3. Setup window
            cv2.namedWindow(window_name, cv2.WINDOW_NORMAL)
            
            # Switch to Fullscreen and bring to Foreground initially
            cv2.setWindowProperty(window_name, cv2.WND_PROP_FULLSCREEN, cv2.WINDOW_FULLSCREEN)
            try:
                cv2.setWindowProperty(window_name, cv2.WND_PROP_TOPMOST, 1)
            except Exception:
                pass # Failsafe for older OpenCV versions/unsupported Wayland managers
            
        success, frame = cap.read()
        if not success:
            time.sleep(0.1)
            continue

        # Run YOLO inference
        # imgsz=320 is applied here: Massively speeds up CPU inference on Pi 5 while maintaining close-up accuracy
        results = model(frame, classes=[BOTTLE_CLASS_ID], verbose=False, imgsz=320)
        
        bottle_detected = False

        for result in results:
            for box in result.boxes:
                conf = float(box.conf[0])
                if conf > 0.60: # 60% confidence threshold
                    
                    bottle_detected = True # Keeps the UI awake
                    
                    w, h = float(box.xywh[0][2]), float(box.xywh[0][3])
                    x1, y1, x2, y2 = map(int, box.xyxy[0])
                    
                    # 1. Reject Sideways Bottles (width is greater than height)
                    if w > h:
                        cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 165, 255), 2) # Orange Box
                        cv2.putText(frame, "REJECTED (Sideways)", (x1, y1 - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 165, 255), 2)
                        continue
                    
                    # Safely extract the Region of Interest (ROI) avoiding out-of-bounds indices
                    h_frame, w_frame = frame.shape[:2]
                    x1_safe, y1_safe = max(0, x1), max(0, y1)
                    x2_safe, y2_safe = min(w_frame, x2), min(h_frame, y2)
                    roi = frame[y1_safe:y2_safe, x1_safe:x2_safe]
                    
                    # 2. Reject Upside Down Bottles using OpenCV Edge analysis
                    if not is_bottle_upright(roi):
                        continue
                    
                    # --- If we pass the checks, process the bottle! ---
                    size_cat, points = estimate_size_and_points(w, h)
                    
                    # Draw Green Bounding Box
                    cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 255, 0), 2)
                    
                    # Draw Label
                    label = f"PET {size_cat} (+{points}pts)"
                    cv2.putText(frame, label, (x1, y1 - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2)

                    # Accumulate logic (with cooldown)
                    current_time = time.time()
                    if (current_time - last_detection_time) > COOLDOWN_SECONDS:
                        detected_queue.append({"size": size_cat, "points": points})
                        last_detection_time = current_time

        # If a bottle is actively detected, aggressively re-assert Fullscreen and Foreground
        # Ensures that if the Debian OS minimized the window, the user sees it immediately
        if bottle_detected:
            cv2.setWindowProperty(window_name, cv2.WND_PROP_FULLSCREEN, cv2.WINDOW_FULLSCREEN)
            try:
                cv2.setWindowProperty(window_name, cv2.WND_PROP_TOPMOST, 1)
            except Exception:
                pass

        # Display the frame via native GUI on the Pi
        cv2.imshow(window_name, frame)

        # waitKey is required for OpenCV to refresh GUI events
        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    if cap:
        cap.release()
    cv2.destroyAllWindows()

if __name__ == '__main__':
    # 1. Run Flask in a daemon thread so it runs in the background
    # This allows it to listen to HTTP requests without blocking the OpenCV GUI loop
    flask_thread = threading.Thread(target=lambda: app.run(host='0.0.0.0', port=5000, debug=False, use_reloader=False))
    flask_thread.daemon = True
    flask_thread.start()

    # 2. Run the OpenCV loop on the main thread (required by most OS window managers)
    main_gui_loop()