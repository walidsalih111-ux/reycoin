import os
import cv2
import time
import uuid
import threading
import numpy as np
from flask import Flask, jsonify, request
from ultralytics import YOLO # type: ignore
from gpiozero import AngularServo, DigitalInputDevice, DistanceSensor, OutputDevice # ---> Added OutputDevice for MAX7219

# ==========================================
# 1. INITIALIZE FLASK & YOLO MODEL
# ==========================================
app = Flask(__name__)

# Load the model from this script's folder so starting it from another directory doesn't try to download it
MODEL_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'yolov8n.pt')
model = YOLO(MODEL_PATH)
BOTTLE_CLASS_ID = 39 

# ==========================================
# 2. MAX7219 4-in-1 LED MATRIX DRIVER CLASS
# ==========================================
class MAX7219:
    """Class to control a MAX7219 4-in-1 LED matrix using bit-banged SPI on Pi 5."""
    
    # Register Address Map
    REG_NOOP        = 0x00
    REG_DIGIT0      = 0x01
    REG_DIGIT1      = 0x02
    REG_DIGIT2      = 0x03
    REG_DIGIT3      = 0x04
    REG_DIGIT4      = 0x05
    REG_DIGIT5      = 0x06
    REG_DIGIT6      = 0x07
    REG_DIGIT7      = 0x08
    REG_DECODE_MODE = 0x09
    REG_INTENSITY   = 0x0A
    REG_SCAN_LIMIT  = 0x0B
    REG_SHUTDOWN    = 0x0C
    REG_DISPLAY_TEST= 0x0F

    def __init__(self, din_pin=21, cs_pin=20, clk_pin=16):
        """Initializes the GPIO outputs and configures the cascaded MAX7219 matrices."""
        self.din_pin = din_pin
        self.cs_pin = cs_pin
        self.clk_pin = clk_pin
        
        # Initialize GPIO pins using gpiozero OutputDevice
        self.din = OutputDevice(self.din_pin)
        self.cs  = OutputDevice(self.cs_pin, active_high=True, initial_value=True)  # CS starts High
        self.clk = OutputDevice(self.clk_pin, active_high=True, initial_value=False) # Clock starts Low
        
        self.init_device()

    def _send_byte(self, byte_val):
        """Bit-bangs 8 bits of data, MSB first."""
        for i in range(8):
            bit = (byte_val >> (7 - i)) & 1
            self.din.value = bit
            self.clk.on()   # Latch bit into the shift register
            self.clk.off()  # Ready for the next bit

    def write_all_devices(self, register, data):
        """Sends a 16-bit packet: 8-bit register address + 8-bit data to all 4 cascaded devices."""
        self.cs.off()  # Pull CS Low to enable chip select
        for _ in range(4):  # Cascade chain of 4 devices
            self._send_byte(register)
            self._send_byte(data)
        self.cs.on()   # Pull CS High to latch data into registers

    def init_device(self):
        """Configures the MAX7219 register presets across all 4 cascaded modules."""
        self.write_all_devices(self.REG_SHUTDOWN, 0x01)     # Wake up from shutdown mode
        self.write_all_devices(self.REG_DISPLAY_TEST, 0x00) # Disable hardware display test
        self.write_all_devices(self.REG_DECODE_MODE, 0x00)  # Standard non-decode mode (Matrix setup)
        self.write_all_devices(self.REG_SCAN_LIMIT, 0x07)   # Enable all 8 digits/rows (0-7)
        self.set_brightness(12)                             # Set high brightness for good illumination (0-15)
        self.clear()

    def set_brightness(self, level):
        """Sets brightness level from 0 (lowest) to 15 (highest)."""
        level = max(0, min(15, level))
        self.write_all_devices(self.REG_INTENSITY, level)

    def clear(self):
        """Clears all registers (turns off all LEDs on all matrices)."""
        for reg in range(1, 9):
            self.write_all_devices(reg, 0x00)

    def turn_on_light(self):
        """Turns all LEDs on all 4 matrices completely ON to serve as a bright illumination light source."""
        for reg in range(1, 9):
            self.write_all_devices(reg, 0xFF)

    def turn_off_light(self):
        """Turns all LEDs on all 4 matrices completely OFF."""
        self.clear()

    def close(self):
        """Gracefully closes GPIO lines and clears display."""
        self.clear()
        self.din.close()
        self.cs.close()
        self.clk.close()


# ==========================================
# 3. HARDWARE INITIALIZATION (Servos, IR, Ultrasonic & MAX7219)
# ==========================================

# A. Double Acceptance Gate Angular Servos (GPIO 17 & 18)
MIN_PULSE = 0.0005  # 500us
MAX_PULSE = 0.0025  # 2500us

try:
    servo1 = AngularServo(
        17, 
        min_angle=0, 
        max_angle=180, 
        min_pulse_width=MIN_PULSE, 
        max_pulse_width=MAX_PULSE
    )
    servo2 = AngularServo(
        18, 
        min_angle=0, 
        max_angle=180, 
        min_pulse_width=MIN_PULSE, 
        max_pulse_width=MAX_PULSE
    )
    
    # Default State: Acceptance Gate Fixed Closed (180°).
    # Signal is kept ACTIVE so the servo physically locks at 180° and cannot be pushed open.
    servo1.angle = 180
    servo2.angle = 180
    time.sleep(0.5)
    print("✅ Hardware: Angular Servos (GPIO 17 & 18) Initialized. Fixed closed at 180° (signal active).")
except Exception as e:
    print("⚠️ WARNING: Angular Servos initialization failed. Running without hardware servos.", e)
    servo1 = None
    servo2 = None

# B. Closing Gate IR Safety Sensor (GPIO 4)
try:
    ir_sensor = DigitalInputDevice(4, pull_up=True)
    print("✅ Hardware: IR Sensor (GPIO 4) Initialized. Default pulled-up.")
except Exception as e:
    print("⚠️ WARNING: IR Safety Sensor initialization failed. Running without safety sensor.", e)
    ir_sensor = None

# C. Ultrasonic Sensor (Bin Level on GPIO 23 & 24)
try:
    # gpiozero caps readings at max_distance (default 1 m), but the bin is 1.07 m deep
    sensor = DistanceSensor(echo=24, trigger=23, max_distance=1.5)
    print("✅ Hardware: Ultrasonic Sensor Initialized.")
except Exception as e:
    print("⚠️ WARNING: Ultrasonic init failed.", e)
    sensor = None

# D. MAX7219 LED Chute Lighting System (GPIO 21, 20, 16)
try:
    led_light = MAX7219(din_pin=21, cs_pin=20, clk_pin=16)
    print("✅ Hardware: MAX7219 LED Chute Lighting System Initialized (DIN:21, CS:20, CLK:16).")
except Exception as e:
    print("⚠️ WARNING: MAX7219 LED Matrix initialization failed. Running without hardware light.", e)
    led_light = None


# Physical Bin Configuration
BIN_HEIGHT_CM = 106.68 # 3.5 ft total height
MAX_FILL_CM = 15.0     # Clearance from top sensor to be considered 100% full

# Smooth sweep configurations (aligned with 50Hz refresh rate)
SWEEP_TIME = 1.0       # Rotation duration in seconds
STEP_DELAY = 0.045     # 45ms delay per step (prevents jitter)

CLOSED_ANGLE = 180.0   # Resting/Closed position angle
OPEN_ANGLE = 90.0      # Active/Open position angle

TOTAL_STEPS = int(SWEEP_TIME / STEP_DELAY)  # ~22 steps for a 1.0s transition
STEP_SIZE = (CLOSED_ANGLE - OPEN_ANGLE) / TOTAL_STEPS      # ~4.09 degrees per step
HAND_CLEAR_TIMEOUT = 15.0  # Give up if the chute stays blocked this long (dirty sensor or stuck object)

# Thread-safe Frame Sharing, Anti-Cheat Status & Light State variables
current_frame = None
current_frame_lock = threading.Lock()
verification_status_msg = ""  # Updates the HDMI monitor overlay in real time
light_is_on = False           # Global track of whether the MAX7219 chute light is currently active
servo_lock = threading.Lock()  # Only one thread may drive the gate servos at a time
queue_lock = threading.Lock()  # Guards detected_queue, shared by Flask and the worker threads

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
    """
    Returns both servos to their default 180° (closed) resting positions and keeps the
    PWM signal ACTIVE so the servo physically locks and cannot drift or be pushed open.
    """
    if servo1 and servo2:
        # Waits for a gate sweep in progress instead of fighting it from another thread
        with servo_lock:
            try:
                print(">> Securing fixed closed position: both servos locked at 180°")
                servo1.angle = CLOSED_ANGLE
                servo2.angle = CLOSED_ANGLE
                time.sleep(0.5)
                # PWM signal intentionally NOT detached — servo stays locked at 180°
            except Exception as e:
                print(f"Servo resting sequence error: {e}")

def process_bottle_sequence(owner):
    """
    Orchestrates the safety-guarded acceptance loop with strong Anti-Cheat confirmation:
    1. Checks if the IR safety sensor is blocked (hand in the chute).
    2. Waits for safety clearance (hand completely removed), up to HAND_CLEAR_TIMEOUT.
    3. Settles the bottle for 0.8 seconds.
    4. Performs spaced Anti-Cheat checks verifying that:
       - The bottle is still present in the camera stream (via thread-safe frame inspections).
       - The user has not re-reached inside the chute to grab it back (no IR disruptions).
    5. Sizes the bottle from those checks and queues its points for `owner` (the resident whose
       session it was detected in) if and only if anti-cheat clears.
    6. Smoothly sweeps servos to open/close gates, depositing the item safely.

    The caller sets gate_busy before starting this thread; it is cleared when the sequence ends.
    """
    global gate_busy, detected_queue, verification_status_msg, session_last_activity, light_is_on

    try:
        fill_pct, is_full = get_bin_status()
        if is_full:
            print(f">> Sequence aborted: BIN IS FULL! ({fill_pct}%)")
            verification_status_msg = "BIN FULL - ABORTED"
            if led_light:
                led_light.turn_off_light()
                light_is_on = False
            return

        # --- STEP 1: SAFETY FIRST (IR HAND DETECTION) ---
        if ir_sensor:
            print(">> Checking Chute: Waiting for resident's hand to clear...")
            wait_started = time.time()
            while ir_sensor.value == 0:
                if time.time() - wait_started > HAND_CLEAR_TIMEOUT:
                    verification_status_msg = "FAILED: CHUTE BLOCKED"
                    print(f">> VERIFICATION FAILED: IR beam blocked for over {HAND_CLEAR_TIMEOUT:.0f}s. Aborting.")
                    if led_light:
                        led_light.turn_off_light()
                        light_is_on = False
                    time.sleep(1.5)
                    return
                verification_status_msg = "HAND IN CHUTE - REMOVE HAND"
                print("[ OBJECT DETECTED ] ---> Resident's hand is inside the chute! Holding gates closed.")
                time.sleep(0.1)  # Poll snappy but without overloading CPU
            print("[ BEAM CLEAR ] ---> Hand removed. Starting anti-cheat verification...")

        # --- STEP 2: SETTLEMENT DELAY ---
        verification_status_msg = "SETTLING BOTTLE... STAND BACK"
        time.sleep(0.8)

        # --- STEP 3: ANTI-CHEAT VISUAL & SENSOR CONFIRMATION ---
        verified_detections = 0
        total_checks = 5
        check_interval = 0.3
        ir_violation = False
        measured_sides = []  # Longest box side (px) of each confirmed sighting, used for sizing

        print(">> Anti-Cheat: Starting visual and sensor confirmation...")
        
        for c in range(total_checks):
            # Instantaneous IR sensor check
            if ir_sensor and ir_sensor.value == 0:
                print("[ ANTI-CHEAT ALERT ] ---> IR beam broken during verification! Hand detected!")
                ir_violation = True
                break
                
            verification_status_msg = f"VERIFYING BOTTLE ({c + 1}/{total_checks})..."
            
            # Retrieve latest webcam frame thread-safely
            frame_to_check = None
            with current_frame_lock:
                if current_frame is not None:
                    frame_to_check = current_frame.copy()
                    
            if frame_to_check is not None:
                # Run lightweight YOLO detection check
                results = model(frame_to_check, classes=[BOTTLE_CLASS_ID], verbose=False, imgsz=320)
                bottle_found_this_check = False
                
                for result in results:
                    for box in result.boxes:
                        conf = float(box.conf[0])
                        if conf > 0.55: # Slightly relaxed threshold for active stream validation
                            w, h = float(box.xywh[0][2]), float(box.xywh[0][3])
                            x1, y1, x2, y2 = map(int, box.xyxy[0])
                            
                            if w > h: 
                                continue # Skip horizontal objects
                                
                            # Crop and analyze the ROI to ensure it is upright
                            h_frame, w_frame = frame_to_check.shape[:2]
                            x1_safe, y1_safe = max(0, x1), max(0, y1)
                            x2_safe, y2_safe = min(w_frame, x2), min(h_frame, y2)
                            roi = frame_to_check[y1_safe:y2_safe, x1_safe:x2_safe]
                            
                            if is_bottle_upright(roi):
                                bottle_found_this_check = True
                                measured_sides.append(max(w, h))
                                break
                                
                if bottle_found_this_check:
                    verified_detections += 1
                    print(f"[ CONFIRMATION {c+1}/{total_checks} ] ---> Bottle visible and stable.")
                else:
                    print(f"[ CONFIRMATION {c+1}/{total_checks} ] ---> Bottle not found in frame.")
            else:
                print(f"[ CONFIRMATION {c+1}/{total_checks} ] ---> Camera frame unavailable.")
                
            time.sleep(check_interval)

        # --- STEP 4: VERIFY EVALUATION ---
        if ir_violation:
            verification_status_msg = "FAILED: CHUTE DISTURBED"
            print(">> VERIFICATION FAILED: User broke IR beam during check. Aborting.")
            if led_light:
                led_light.turn_off_light()
                light_is_on = False
            time.sleep(1.5)
            return
            
        if verified_detections < 3: # Requires at least 60% of checks to confirm stable presence
            verification_status_msg = "FAILED: BOTTLE REMOVED"
            print(f">> VERIFICATION FAILED: Bottle missing/unstable (detected {verified_detections}/{total_checks}). Aborting.")
            if led_light:
                led_light.turn_off_light()
                light_is_on = False
            time.sleep(1.5)
            return

        # Both parameters match! Confirmed legitimate deposit.
        # Size it from the verification frames (bottle at rest, hand out of the chute) rather than the
        # first sighting, where a bottle held close to the lens looks bigger and would earn more points.
        size_cat, points = estimate_size_and_points(float(np.median(measured_sides)))
        verification_status_msg = f"VERIFIED! +{points} PTS"
        with queue_lock:
            detected_queue.append({
                "id": uuid.uuid4().hex,  # Lets api.php credit each bottle exactly once
                "owner": owner,
                "size": size_cat,
                "points": points,
                "ts": time.time()
            })
        if owner == session_owner:
            session_last_activity = time.time()  # Bottles keep the session open
        print(f">> Points Confirmed: +{points} pts for {size_cat} PET bottle (resident {owner}).")
        time.sleep(0.5)

        # --- STEP 5: SMOOTH SYNCHRONIZED SWEEP (OPEN GATE: 180° -> 90°) ---
        if servo1 and servo2:
            with servo_lock:
                verification_status_msg = "OPENING GATES..."
                print(">> Actuating Gates: Rotating servos 180° -> 90° smoothly (OPEN)")

                # --- MANDATORY HARDWARE REQUIREMENT: TURN OFF LIGHT AS SOON AS THE GATE OPENS ---
                if led_light:
                    led_light.turn_off_light()
                    light_is_on = False
                    print("[ LIGHTS OFF ] ---> Acceptance gates opening. Disabling chute lights.")

                for step in range(TOTAL_STEPS + 1):
                    angle = CLOSED_ANGLE - (step * STEP_SIZE) # Sweep down to 90 degrees
                    servo1.angle = angle
                    servo2.angle = angle
                    time.sleep(STEP_DELAY)

                verification_status_msg = "DEPOSITING BOTTLE..."
                print(">> Gates open. Holding for 2.0 seconds for item slide-down...")
                time.sleep(2.0)               # Keep acceptance gate open for exactly 2 seconds

                # --- STEP 6: SMOOTH SYNCHRONIZED SWEEP (CLOSE GATE: 90° -> 180°) ---
                verification_status_msg = "CLOSING GATES..."
                print(">> Actuating Gates: Rotating servos 90° -> 180° smoothly (CLOSE)")
                for step in range(TOTAL_STEPS + 1):
                    angle = OPEN_ANGLE + (step * STEP_SIZE)                  # Sweep up to 180 degrees
                    servo1.angle = angle
                    servo2.angle = angle
                    time.sleep(STEP_DELAY)

                # PWM signal intentionally NOT detached — servos remain locked at 180°
                # until the next bottle insertion begins.
                print(">> Gates closed and locked at 180°. Holding fixed until next bottle.")
        else:
            # Simulation mode wait
            verification_status_msg = "[SIMULATOR] GATES OPENED"
            print(">> [SIMULATOR] Simulating 5.0 seconds gate hold...")
            if led_light:
                led_light.turn_off_light()
                light_is_on = False
            time.sleep(5.0)
            
    except Exception as e:
        print(f"Gate sequencing error: {e}")
    finally:
        verification_status_msg = ""
        gate_busy = False  # Re-enable model inference for subsequent bottles
        if led_light:
            led_light.turn_off_light()  # Safety catch to ensure light is always OFF when session ends
            light_is_on = False

# ==========================================
# 4. STATE & CONFIGURATION
# ==========================================
is_camera_active = False
gate_busy = False  # Flag to block frame detection calculations while acceptance gate moves
detected_queue = []  # Verified bottles waiting for api.php to credit them (removed via /ack)
session_owner = None  # Resident ID (QR code) of the active scanning session, set by /start
session_last_activity = 0  # Time of /start or the last verified bottle
last_detection_time = 0
COOLDOWN_SECONDS = 5.0  # Cooldown adjusted to match active processing bounds
SESSION_IDLE_SECONDS = 70  # Ends a session with no verified bottle for this long (= LOCK_SECONDS in api.php)

THRESHOLD_250ML_MAX = 180   
THRESHOLD_500ML_MAX = 250   
THRESHOLD_1000ML_MAX = 380  

def estimate_size_and_points(longest_side):
    """Returns size label and corresponding points based on the bounding box's longest side (px)"""
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
# 5. FLASK API ENDPOINTS
# ==========================================
def end_session():
    """Stops scanning for new bottles. A bottle already being verified still finishes (see main_gui_loop)."""
    global is_camera_active, session_owner, light_is_on
    is_camera_active = False
    session_owner = None

    # Return both servos back to 180° (closed) resting positions when the session terminates
    threading.Thread(target=initialize_servos_to_closed, daemon=True).start()

    # Turn off the LED lighting completely on session close
    if led_light:
        led_light.turn_off_light()
        light_is_on = False

@app.route('/start')
def start_camera():
    global is_camera_active, session_owner, session_last_activity, light_is_on
    owner = request.args.get('owner', '').strip()
    if not owner:
        return jsonify({"status": "error", "message": "owner is required"}), 400

    # Bottles detected from now on are credited to this resident
    session_owner = owner
    session_last_activity = time.time()
    is_camera_active = True

    # Secure and ensure both servos are set at 180° (closed) resting positions on launch
    threading.Thread(target=initialize_servos_to_closed, daemon=True).start()

    # Make sure light is initially OFF when session starts
    if led_light:
        led_light.turn_off_light()
        light_is_on = False

    return jsonify({"status": "started"})

@app.route('/stop')
def stop_camera():
    # Ignore a late stop from a resident whose session was already handed to someone else
    owner = request.args.get('owner')
    if owner and owner != session_owner:
        return jsonify({"status": "ignored"})
    end_session()
    return jsonify({"status": "stopped"})

@app.route('/poll')
def poll_detection():
    """Lists verified bottles without removing them; api.php calls /ack once they are saved."""
    with queue_lock:
        items = list(detected_queue)
    return jsonify({
        "status": "success",
        "items": items,
        "active": is_camera_active,
        "owner": session_owner,
        "busy": gate_busy
    })

@app.route('/ack')
def ack_detection():
    """Removes bottles that api.php has credited (comma-separated ids)."""
    ids = set(request.args.get('ids', '').split(','))
    with queue_lock:
        detected_queue[:] = [item for item in detected_queue if item["id"] not in ids]
    return jsonify({"status": "success"})

@app.route('/status')
def system_status():
    """Provides real-time hardware status (Fill Level) to the web dashboard"""
    fill_pct, is_full = get_bin_status()
    return jsonify({"status": "success", "fill_percent": fill_pct, "is_full": is_full})

# ==========================================
# 6. OPENCV NATIVE GUI LOOP (Main Thread)
# ==========================================
def stop_idle_session():
    """Ends a session with no verified bottle for SESSION_IDLE_SECONDS (e.g. the resident's phone died)."""
    if is_camera_active and not gate_busy and time.time() - session_last_activity > SESSION_IDLE_SECONDS:
        print(">> Session idle: no bottles for a while. Stopping scanner.")
        end_session()

def main_gui_loop():
    global is_camera_active, detected_queue, last_detection_time, gate_busy, current_frame, verification_status_msg, light_is_on
    cap = None
    window_name = "PET Bottle Scanner"

    print("PET Bottle Scanner ready. Waiting for start command...")

    while True:
        stop_idle_session()

        # Keep the camera running until a bottle that is still being verified finishes,
        # so the anti-cheat checks always see live frames
        if not is_camera_active and not gate_busy:
            if cap is not None:
                cap.release()
                cap = None
                cv2.destroyAllWindows()
            # The lighting check below can re-light the chute just as a session ends; keep it off while idle
            if light_is_on:
                if led_light:
                    led_light.turn_off_light()
                light_is_on = False
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

        # Keep current shared frame continuously updated for worker-thread checks
        with current_frame_lock:
            current_frame = frame.copy()

        # -------------------------------------------------------------
        # AUTOMATIC CHUTE LIGHTING SYSTEM: IR Triggered (Active High/Low)
        # Turns on light only while resident is inserting bottle and gate is closed.
        # -------------------------------------------------------------
        if not gate_busy:
            if ir_sensor and ir_sensor.value == 0:
                if not light_is_on:
                    if led_light:
                        led_light.turn_on_light()
                    light_is_on = True
                    print("[ LIGHTS ON ] ---> Resident is inserting a bottle. Illuminating chute.")
            else:
                if light_is_on:
                    if led_light:
                        led_light.turn_off_light()
                    light_is_on = False
                    print("[ LIGHTS OFF ] ---> Chute cleared. Turning off illumination.")

        fill_pct, is_full = get_bin_status()

        # -------------------------------------------------------------
        # BYPASS OVERLAY: Display beautiful overlay on HDMI screen while 
        # gate acts or active Anti-Cheat verification sequences execute.
        # -------------------------------------------------------------
        if gate_busy:
            # Render elegant status container matching recycoin look and feel
            # Dark background frame header
            cv2.rectangle(frame, (15, 15), (625, 95), (30, 44, 4), -1)   # Recycoin Dark Green background
            cv2.rectangle(frame, (15, 15), (625, 95), (165, 202, 93), 2)  # Recycoin Light Green border
            
            # Show the active status statement dynamically
            status_text = verification_status_msg if verification_status_msg else "PROCESSING..."
            cv2.putText(frame, status_text, (35, 62), 
                        cv2.FONT_HERSHEY_SIMPLEX, 0.75, (255, 255, 255), 2)
            
            # Show live bin telemetry
            bin_text = f"Bin: {fill_pct}%"
            cv2.putText(frame, bin_text, (505, 62), 
                        cv2.FONT_HERSHEY_SIMPLEX, 0.6, (165, 202, 93), 2)
            
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
                    
                    # Live preview only; the credited size is measured again during verification
                    size_cat, points = estimate_size_and_points(max(w, h))

                    cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 255, 0), 2)
                    label = f"PET {size_cat} (+{points}pts)"
                    cv2.putText(frame, label, (x1, y1 - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2)

                    current_time = time.time()
                    owner = session_owner  # Captured now: the bottle stays this resident's even if the session ends
                    if (current_time - last_detection_time) > COOLDOWN_SECONDS and is_camera_active and owner and not gate_busy:
                        # Prevent accumulating points if the machine is full
                        if not is_full:
                            last_detection_time = current_time
                            gate_busy = True  # Set before the thread starts so a second sequence can't begin

                            # Start the sequence: Safety checks -> Points Allocation -> Gate Sweeps
                            threading.Thread(
                                target=process_bottle_sequence,
                                args=(owner,),
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
    try:
        # Localhost only: api.php and display.php on this Pi are the scanner's only clients
        flask_thread = threading.Thread(target=lambda: app.run(host='127.0.0.1', port=5000, debug=False, use_reloader=False))
        flask_thread.daemon = True
        flask_thread.start()

        main_gui_loop()
    finally:
        # Graceful cleanup to ensure hardware pins are released and LEDs are turned off safely on exit
        if led_light:
            led_light.close()
            print(">> MAX7219 Lighting controller gracefully terminated.")