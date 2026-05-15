import cv2
import time
import threading
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
                    bottle_detected = True
                    
                    w, h = float(box.xywh[0][2]), float(box.xywh[0][3])
                    size_cat, points = estimate_size_and_points(w, h)
                    
                    # Draw Bounding Box
                    x1, y1, x2, y2 = map(int, box.xyxy[0])
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