import cv2
import time
import threading
import numpy as np
from flask import Flask, jsonify
from flask_cors import CORS # type: ignore
from ultralytics import YOLO # type: ignore
from gpiozero import AngularServo, DistanceSensor # ---> NEW: Hardware Control

# ==========================================
# 1. INITIALIZE FLASK & YOLO MODEL
# ==========================================
app = Flask(__name__)
CORS(app)

model = YOLO('yolov8n.pt') 
BOTTLE_CLASS_ID = 39 

# ==========================================
# 2. HARDWARE INITIALIZATION (Servo & Ultrasonic)
# ==========================================
# Initialize Servo (Trapdoor)
try:
    servo = AngularServo(17, min_angle=-180, max_angle=180, min_pulse_width=0.0005, max_pulse_width=0.0024)
    servo.angle = -180  # Default state: Flap Closed
    print("✅ Hardware: Servo Motor Initialized. Flap is closed.")
except Exception as e:
    print("⚠️ WARNING: Servo init failed. Running without hardware flap.", e)
    servo = None

# Initialize Ultrasonic Sensor (Bin Level)
try:
    sensor = DistanceSensor(echo=24, trigger=23)
    print("✅ Hardware: Ultrasonic Sensor Initialized.")
except Exception as e:
    print("⚠️ WARNING: Ultrasonic init failed.", e)
    sensor = None

# Physical Bin Configuration
BIN_HEIGHT_CM = 106.68 # 3.5 ft total height
MAX_FILL_CM = 15.0     # Clearance from top sensor to be considered 100% full

def get_bin_status():
    """Calculates the fill percentage based on ultrasonic distance reading."""
    if sensor:
        try:
            # sensor.distance returns meters, multiply by 100 for cm
            dist_cm = sensor.distance * 100
            
            current_fill_height = BIN_HEIGHT_CM - dist_cm
            usable_height = BIN_HEIGHT_CM - MAX_FILL_CM
            
            fill_pct = (current_fill_height / usable_height) * 100
            fill_pct = max(0.0, min(100.0, fill_pct)) # Clamp between 0% and 100%
            
            is_full = fill_pct >= 95.0 # Flag as full when capacity reaches 95%
            return round(fill_pct, 1), is_full
        except Exception as e:
            print(f"Sensor read error: {e}")
            return 0.0, False
    return 0.0, False

def open_trapdoor():
    """Opens the trapdoor for 2 seconds to accept the bottle, but aborts if the bin is full."""
    fill_pct, is_full = get_bin_status()
    
    # SAFETY LOCKOUT: Do not operate servo if the bin is overflowing
    if is_full:
        print(f">> Trapdoor aborted: BIN IS FULL! ({fill_pct}%)")
        return
        
    if servo:
        try:
            print(">> Triggering Trapdoor: OPEN")
            servo.angle = 180   # Rotate to open the flap
            time.sleep(2.0)     # Keep open for 2 seconds to let bottle drop
            print(">> Triggering Trapdoor: CLOSE")
            servo.angle = -180  # Return to closed state
        except Exception as e:
            print(f"Servo actuation error: {e}")

# ==========================================
# 3. STATE & CONFIGURATION
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
    """Heuristic to determine if a detected PET bottle is upright."""
    h, w = roi.shape[:2]
    
    if h < 40 or w < 20:
        return True 

    gray = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
    edges = cv2.Canny(gray, 50, 150)

    quarter_h = h // 4
    top_region = edges[0:quarter_h, :]
    bottom_region = edges[h - quarter_h:h, :]

    def get_average_width(region):
        widths = []
        for row in region:
            pts = np.where(row > 0)[0]
            if len(pts) >= 2:
                widths.append(pts[-1] - pts[0])
        return float(np.mean(widths)) if widths else 0.0

    top_width = get_average_width(top_region)
    bottom_width = get_average_width(bottom_region)

    if top_width > 0 and bottom_width == 0:
        return False
        
    return bottom_width >= (top_width * 0.85)

# ==========================================
# 4. FLASK API ENDPOINTS
# ==========================================
@app.route('/start')
def start_camera():
    global is_camera_active
    is_camera_active = True
    return jsonify({"status": "started"})

@app.route('/stop')
def stop_camera():
    global is_camera_active
    is_camera_active = False
    return jsonify({"status": "stopped"})

@app.route('/poll')
def poll_detection():
    global detected_queue
    if len(detected_queue) > 0:
        data = detected_queue.pop(0) 
        return jsonify({"status": "success", "size": data["size"], "points": data["points"]})
    return jsonify({"status": "empty"})

@app.route('/status')
def system_status():
    """Provides real-time hardware status (Fill Level) to the web dashboard"""
    fill_pct, is_full = get_bin_status()
    return jsonify({"status": "success", "fill_percent": fill_pct, "is_full": is_full})

# ==========================================
# 5. OPENCV NATIVE GUI LOOP (Main Thread)
# ==========================================
def main_gui_loop():
    global is_camera_active, detected_queue, last_detection_time
    cap = None
    window_name = "PET Bottle Scanner"

    print("PET Bottle Scanner ready. Waiting for start command...")

    while True:
        if not is_camera_active:
            if cap is not None:
                cap.release()
                cap = None
                cv2.destroyAllWindows()
            time.sleep(0.2)
            continue

        if cap is None:
            cap = cv2.VideoCapture(0)
            
            cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
            cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
            cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
            
            cv2.namedWindow(window_name, cv2.WINDOW_NORMAL)
            cv2.setWindowProperty(window_name, cv2.WND_PROP_FULLSCREEN, cv2.WINDOW_FULLSCREEN)
            try:
                cv2.setWindowProperty(window_name, cv2.WND_PROP_TOPMOST, 1)
            except Exception:
                pass 
            
        success, frame = cap.read()
        if not success:
            time.sleep(0.1)
            continue

        results = model(frame, classes=[BOTTLE_CLASS_ID], verbose=False, imgsz=320)
        bottle_detected = False

        for result in results:
            for box in result.boxes:
                conf = float(box.conf[0])
                if conf > 0.60: 
                    
                    bottle_detected = True 
                    w, h = float(box.xywh[0][2]), float(box.xywh[0][3])
                    x1, y1, x2, y2 = map(int, box.xyxy[0])
                    
                    if w > h: continue
                    
                    h_frame, w_frame = frame.shape[:2]
                    x1_safe, y1_safe = max(0, x1), max(0, y1)
                    x2_safe, y2_safe = min(w_frame, x2), min(h_frame, y2)
                    roi = frame[y1_safe:y2_safe, x1_safe:x2_safe]
                    
                    if not is_bottle_upright(roi): continue
                    
                    size_cat, points = estimate_size_and_points(w, h)
                    
                    cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 255, 0), 2)
                    label = f"PET {size_cat} (+{points}pts)"
                    cv2.putText(frame, label, (x1, y1 - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2)

                    current_time = time.time()
                    if (current_time - last_detection_time) > COOLDOWN_SECONDS:
                        detected_queue.append({"size": size_cat, "points": points})
                        last_detection_time = current_time
                        
                        # Trigger trapdoor asynchronously
                        threading.Thread(target=open_trapdoor, daemon=True).start()

        if bottle_detected:
            cv2.setWindowProperty(window_name, cv2.WND_PROP_FULLSCREEN, cv2.WINDOW_FULLSCREEN)
            try:
                cv2.setWindowProperty(window_name, cv2.WND_PROP_TOPMOST, 1)
            except Exception:
                pass

        cv2.imshow(window_name, frame)

        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    if cap:
        cap.release()
    cv2.destroyAllWindows()

if __name__ == '__main__':
    flask_thread = threading.Thread(target=lambda: app.run(host='0.0.0.0', port=5000, debug=False, use_reloader=False))
    flask_thread.daemon = True
    flask_thread.start()

    main_gui_loop()