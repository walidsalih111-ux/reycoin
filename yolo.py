import cv2
import time
import threading
from flask import Flask, Response, jsonify
from flask_cors import CORS
from ultralytics import YOLO

# ==========================================
# 1. INITIALIZE FLASK & YOLO MODEL
# ==========================================
app = Flask(__name__)
CORS(app) # Allow frontend to interact directly if needed

model = YOLO('yolov8n.pt') 
BOTTLE_CLASS_ID = 39 

# ==========================================
# 2. STATE & CONFIGURATION
# ==========================================
is_camera_active = False
cap = None
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
# 3. VIDEO STREAM GENERATOR
# ==========================================
def generate_frames():
    global cap, is_camera_active, detected_queue, last_detection_time
    
    while True:
        if not is_camera_active:
            time.sleep(0.5)
            continue
            
        if cap is None or not cap.isOpened():
            cap = cv2.VideoCapture(0) # Open camera
            
        success, frame = cap.read()
        if not success:
            time.sleep(0.1)
            continue

        # Run YOLO inference
        results = model(frame, classes=[BOTTLE_CLASS_ID], verbose=False)

        for result in results:
            for box in result.boxes:
                conf = float(box.conf[0])
                if conf > 0.60: # 60% confidence threshold
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

        # Encode frame to JPEG and yield to web stream
        ret, buffer = cv2.imencode('.jpg', frame)
        if ret:
            frame_bytes = buffer.tobytes()
            yield (b'--frame\r\n'
                   b'Content-Type: image/jpeg\r\n\r\n' + frame_bytes + b'\r\n')

# ==========================================
# 4. FLASK API ENDPOINTS
# ==========================================
@app.route('/video_feed')
def video_feed():
    """Live MJPEG Stream Endpoint"""
    return Response(generate_frames(), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/start')
def start_camera():
    """Triggered by PHP when modal opens"""
    global is_camera_active
    is_camera_active = True
    return jsonify({"status": "started"})

@app.route('/stop')
def stop_camera():
    """Triggered by PHP when modal closes to save CPU"""
    global is_camera_active, cap
    is_camera_active = False
    if cap is not None:
        cap.release()
        cap = None
    return jsonify({"status": "stopped"})

@app.route('/poll')
def poll_detection():
    """Polled by PHP to check if a bottle was successfully detected"""
    global detected_queue
    if len(detected_queue) > 0:
        data = detected_queue.pop(0) # Remove from queue and return
        return jsonify({"status": "success", "size": data["size"], "points": data["points"]})
    return jsonify({"status": "empty"})

if __name__ == '__main__':
    # Run server locally on port 5000. 
    # Important: Run this file continuously in the background using `python yolo.py &`
    app.run(host='0.0.0.0', port=5000, threaded=True)