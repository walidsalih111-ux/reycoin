import cv2
import time
import threading
import numpy as np
from flask import Flask, jsonify
from flask_cors import CORS # type: ignore
from ultralytics import YOLO # type: ignore
from gpiozero import AngularServo, DistanceSensor # ---> Hardware Control

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
try:
    # Set physical limits to 0 to 180 degrees mapped to standard servo pulse bounds (0.5ms to 2.5ms).
    # This allows us to command precise angles inside the 180° range.
    servo = AngularServo(17, min_angle=0, max_angle=180, min_pulse_width=0.0005, max_pulse_width=0.0025)
    servo.angle = 0    # Default state: Acceptance Gate Closed (0 degrees)
    time.sleep(0.5)    # Allow the servo to reach position
    servo.detach()     # Cut control signal to stop idle jitter/humming!
    print("✅ Hardware: Servo Motor Initialized. Acceptance gate is closed (0°) and detached.")
except Exception as e:
    print("⚠️ WARNING: Servo init failed. Running without hardware acceptance gate.", e)
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

def open_acceptance_gate():
    """Opens the acceptance gate by rotating to 90°, pauses for 4 seconds, then rotates back to 0°."""
    global gate_busy
    fill_pct, is_full = get_bin_status()
    
    if is_full:
        print(f">> Acceptance Gate aborted: BIN IS FULL! ({fill_pct}%)")
        return
        
    if servo:
        try:
            gate_busy = True  # Lock out frame detection calculations during gate active state
            
            # Step 1: Rotate to 90 degrees (Open position to accept bottle)
            print(">> Triggering Acceptance Gate: ROTATE TO 90° (OPEN)")
            servo.angle = 90       # Re-engages pulse train automatically, moves to 90°
            time.sleep(4.0)        # Keep acceptance gate open for exactly 4 seconds
            
            # Step 2: Rotate back to 0 degrees (Closed position)
            print(">> Triggering Acceptance Gate: ROTATE TO 0° (CLOSE)")
            servo.angle = 0  
            time.sleep(0.8)        # Allow plenty of time to physically return and rest at 0°
            
            # Step 3: Stop active signal
            servo.detach()         # Terminate active pulse control to eliminate servo motor hum/shake
            print(">> Acceptance Gate successfully closed and detached.")
        except Exception as e:
            print(f"Servo actuation error: {e}")
        finally:
            gate_busy = False  # Re-enable model inference for subsequent bottles

# ==========================================
# 3. STATE & CONFIGURATION
# ==========================================
is_camera_active = False
gate_busy = False  # Flag to block frame detection calculations while acceptance gate moves
detected_queue = []
last_detection_time = 0
COOLDOWN_SECONDS = 5.0  # Cooldown adjusted to match the 4-second acceptance duration + safety margin

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
    global is_camera_active, detected_queue, last_detection_time, gate_busy
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

        # Get bin status once per frame to lock interactions if needed
        fill_pct, is_full = get_bin_status()

        # -------------------------------------------------------------
        # BYPASS LOOP: If acceptance gate is actively moving, pause YOLOv8 
        # inferences to prevent CPU spikes from jittering the servo.
        # -------------------------------------------------------------
        if gate_busy:
            # Draw overlay status banner informing the user of the process
            cv2.rectangle(frame, (10, 15), (630, 85), (0, 0, 0), -1)
            cv2.rectangle(frame, (10, 15), (630, 85), (0, 255, 0), 2)
            cv2.putText(frame, "ACCEPTING BOTTLE... PLEASE WAIT", (35, 57), 
                        cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)
            
            cv2.imshow(window_name, frame)
            if cv2.waitKey(1) & 0xFF == ord('q'):
                break
            continue
        # -------------------------------------------------------------

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
                        # Prevent accumulating points if the machine is full
                        if not is_full:
                            detected_queue.append({"size": size_cat, "points": points})
                            last_detection_time = current_time
                            
                            # Trigger acceptance gate asynchronously
                            threading.Thread(target=open_acceptance_gate, daemon=True).start()
                        else:
                            print(f"Skipping point allocation. Bin is full ({fill_pct}%).")

        # Natively Display Warning if the machine is full
        if is_full:
            cv2.putText(frame, f"BIN FULL ({fill_pct}%) - DISABLED", (30, 60), cv2.FONT_HERSHEY_SIMPLEX, 1.0, (0, 0, 255), 3)

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