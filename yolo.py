import cv2
import time
import threading
import numpy as np
from flask import Flask, jsonify
from flask_cors import CORS # type: ignore
from ultralytics import YOLO # type: ignore
from gpiozero import Servo, DigitalInputDevice, DistanceSensor # ---> Integrated Hardware Control

# ==========================================
# 1. INITIALIZE FLASK & YOLO MODEL
# ==========================================
app = Flask(__name__)
CORS(app)

model = YOLO('yolov8n.pt') 
BOTTLE_CLASS_ID = 39 

# ==========================================
# 2. HARDWARE INITIALIZATION (Servos, IR, & Ultrasonic)
# ==========================================

# A. Double Acceptance Gate Servos (GPIO 17 & 18)
# Safely adjusted pulse widths to prevent physical limits stalling, grinding, and buzzing.
MIN_PULSE = 0.0006  # 0.6 ms
MAX_PULSE = 0.0023  # 2.3 ms

try:
    servo1 = Servo(17, min_pulse_width=MIN_PULSE, max_pulse_width=MAX_PULSE)
    servo2 = Servo(18, min_pulse_width=MIN_PULSE, max_pulse_width=MAX_PULSE)
    
    # Default State: Acceptance Gate Closed (90 degrees). 
    # Translating to gpiozero range (-1.0 to 1.0): (90.0 / 90.0) - 1.0 = 0.0
    servo1.value = 0.0
    servo2.value = 0.0
    time.sleep(0.5)
    servo1.detach()     # Cut control signal to stop idle jitter/humming
    servo2.detach()
    print("✅ Hardware: Servos (GPIO 17 & 18) Initialized. Default closed (90°).")
except Exception as e:
    print("⚠️ WARNING: Servos initialization failed. Running without hardware servos.", e)
    servo1 = None
    servo2 = None

# B. Closing Gate IR Safety Sensor (GPIO 4)
try:
    # Most common IR obstacle modules use an 'active-low' configuration (0 when blocked, 1 when clear)
    ir_sensor = DigitalInputDevice(4, pull_up=True)
    print("✅ Hardware: IR Sensor (GPIO 4) Initialized. Default pulled-up.")
except Exception as e:
    print("⚠️ WARNING: IR Safety Sensor initialization failed. Running without safety sensor.", e)
    ir_sensor = None

# C. Initialize Ultrasonic Sensor (Bin Level on GPIO 23 & 24)
try:
    sensor = DistanceSensor(echo=24, trigger=23)
    print("✅ Hardware: Ultrasonic Sensor Initialized.")
except Exception as e:
    print("⚠️ WARNING: Ultrasonic init failed.", e)
    sensor = None

# Physical Bin Configuration
BIN_HEIGHT_CM = 106.68 # 3.5 ft total height
MAX_FILL_CM = 15.0     # Clearance from top sensor to be considered 100% full

# Smooth sweep configurations (aligned with 50Hz refresh rate)
SWEEP_TIME = 1.0       # Rotation duration in seconds
STEP_DELAY = 0.045     # 45ms delay per step (prevents jitter)
TARGET_ANGLE = 90.0    # Closing angle limit
TOTAL_STEPS = int(SWEEP_TIME / STEP_DELAY)  # ~22 steps for a 1.0s transition
STEP_SIZE = TARGET_ANGLE / TOTAL_STEPS      # ~4.09 degrees per step

def get_bin_status():
    """Calculates the fill percentage based on ultrasonic distance reading."""
    if sensor:
        try:
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

def initialize_servos_to_closed():
    """Returns both servos to their default 90° (closed) resting positions safely."""
    if servo1 and servo2:
        try:
            print(">> Securing default/resting position: both servos at 90° (Closed)")
            servo1.value = 0.0
            servo2.value = 0.0
            time.sleep(0.5)
            servo1.detach()
            servo2.detach()
        except Exception as e:
            print(f"Servo resting sequence error: {e}")

def process_bottle_sequence(size_cat, points):
    """
    Orchestrates the safety-guarded acceptance loop:
    1. Checks if the IR safety sensor is blocked (hand in the chute).
    2. Waits for safety clearance.
    3. Allocates points & updates queue once cleared.
    4. Smoothly sweeps both servos from 90° to 0° (OPEN).
    5. Holds open for 4 seconds, then smoothly sweeps back from 0° to 90° (CLOSE).
    """
    global gate_busy, detected_queue
    fill_pct, is_full = get_bin_status()
    
    if is_full:
        print(f">> Sequence aborted: BIN IS FULL! ({fill_pct}%)")
        return
        
    try:
        gate_busy = True  # Lock out frame detection calculations during sequence
        
        # --- STEP 1: SAFETY FIRST (IR HAND DETECTION) ---
        if ir_sensor:
            print(">> Checking Chute: Waiting for resident's hand to clear...")
            while ir_sensor.value == 0:
                print("[ OBJECT DETECTED ] ---> Resident's hand is inside the chute! Holding gates closed.")
                time.sleep(0.1)  # Poll snappy but without overloading CPU
            print("[ BEAM CLEAR ] ---> Chute is safe. Initiating acceptance process...")

        # --- STEP 2: ALLOCATE BOTTLE POINTS ---
        detected_queue.append({"size": size_cat, "points": points})
        print(f">> Points Allocated: +{points} pts for {size_cat} PET bottle.")

        # --- STEP 3: SMOOTH SYNCHRONIZED SWEEP (OPEN GATE: 90° -> 0°) ---
        if servo1 and servo2:
            print(">> Actuating Gates: Rotating servos 90° -> 0° smoothly (OPEN)")
            for step in range(TOTAL_STEPS + 1):
                angle = TARGET_ANGLE - (step * STEP_SIZE) # Sweep down to 0 degrees
                servo_value = (angle / 90.0) - 1.0        # Map to gpiozero's -1.0 to 1.0 bounds
                servo1.value = servo_value
                servo2.value = servo_value
                time.sleep(STEP_DELAY)
                
            print(">> Gates open. Holding for 4.0 seconds for item slide-down...")
            time.sleep(4.0)               # Keep acceptance gate open for exactly 4 seconds
            
            # --- STEP 4: SMOOTH SYNCHRONIZED SWEEP (CLOSE GATE: 0° -> 90°) ---
            print(">> Actuating Gates: Rotating servos 0° -> 90° smoothly (CLOSE)")
            for step in range(TOTAL_STEPS + 1):
                angle = step * STEP_SIZE                  # Sweep up to 90 degrees
                servo_value = (angle / 90.0) - 1.0
                servo1.value = servo_value
                servo2.value = servo_value
                time.sleep(STEP_DELAY)
                
            # Detach to kill the holding current to stop hums and extend the motor life span
            servo1.detach()
            servo2.detach()
            print(">> Gates successfully closed and powered down.")
        else:
            # Simulation mode wait
            print(">> [SIMULATOR] Simulating 5.0 seconds gate hold...")
            time.sleep(5.0)
            
    except Exception as e:
        print(f"Gate sequencing error: {e}")
    finally:
        gate_busy = False  # Re-enable model inference for subsequent bottles

# ==========================================
# 3. STATE & CONFIGURATION
# ==========================================
is_camera_active = False
gate_busy = False  # Flag to block frame detection calculations while acceptance gate moves
detected_queue = []
last_detection_time = 0
COOLDOWN_SECONDS = 5.0  # Cooldown adjusted to match active processing bounds

THRESHOLD_250ML_MAX = 180   
THRESHOLD_500ML_MAX = 250   
THRESHOLD_1000ML_MAX = 380  

def estimate_size_and_points(width, height):
    """Returns size label and corresponding points based on bounding box dimension mapping"""
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
    
    # Secure and ensure both servos are set at 90° (closed) resting positions on launch
    threading.Thread(target=initialize_servos_to_closed, daemon=True).start()
    return jsonify({"status": "started"})

@app.route('/stop')
def stop_camera():
    global is_camera_active
    is_camera_active = False
    
    # Return both servos back to 90° (closed) resting positions when the session terminates
    threading.Thread(target=initialize_servos_to_closed, daemon=True).start()
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

        fill_pct, is_full = get_bin_status()

        # -------------------------------------------------------------
        # BYPASS LOOP: If acceptance gate is actively moving, pause YOLOv8 
        # inferences to prevent CPU spikes from jittering the servo.
        # -------------------------------------------------------------
        if gate_busy:
            cv2.rectangle(frame, (10, 15), (630, 85), (0, 0, 0), -1)
            cv2.rectangle(frame, (10, 15), (630, 85), (0, 255, 0), 2)
            
            # Show a specialized safety message if hand triggers sensor
            if ir_sensor and ir_sensor.value == 0:
                cv2.putText(frame, "PLEASE REMOVE HAND FROM CHUTE!", (35, 57), 
                            cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 255), 2)
            else:
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
                            last_detection_time = current_time
                            
                            # Start the sequence: Safety checks -> Points Allocation -> Gate Sweeps
                            threading.Thread(
                                target=process_bottle_sequence, 
                                args=(size_cat, points), 
                                daemon=True
                            ).start()
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