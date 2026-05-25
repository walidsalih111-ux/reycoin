# test_servo.py
# Optimized smooth sweep test for two SG90 Servos on Raspberry Pi 5 (GPIO 17 & 18).
# Sweeps smoothly between 0 and 90 degrees taking 1 second, with a 2-second pause.

from gpiozero import Servo
from time import sleep

# --- PIN CONFIGURATION ---
# BCM GPIO numbering.
# GPIO 17 corresponds to PHYSICAL PIN 11.
# GPIO 18 corresponds to PHYSICAL PIN 12.
SERVO1_PIN = 17
SERVO2_PIN = 18

# Safely adjusted pulse widths to prevent physical limits stalling, grinding, and buzzing.
MIN_PULSE = 0.0006  # 0.6 ms
MAX_PULSE = 0.0023  # 2.3 ms

servo1 = Servo(SERVO1_PIN, min_pulse_width=MIN_PULSE, max_pulse_width=MAX_PULSE)
servo2 = Servo(SERVO2_PIN, min_pulse_width=MIN_PULSE, max_pulse_width=MAX_PULSE)

def set_angle(angle):
    """
    Translates a 0 to 180 degree angle to the corresponding -1.0 to 1.0 value for gpiozero.
    """
    # Clamp angle strictly between 0 and 180 (for hardware safety)
    angle = max(0.0, min(180.0, angle))
    
    # Map 0-180 degrees -> -1.0 to 1.0
    servo_value = (angle / 90.0) - 1.0
    
    # Update both servos at the exact same instant
    servo1.value = servo_value
    servo2.value = servo_value

def test_loop():
    print(f"Starting Synchronized Sweep Test on GPIO {SERVO1_PIN} & {SERVO2_PIN}")
    print("Sweeping smoothly 0° -> 90° (1s), pausing (2s), then returning (1s).")
    print("Press CTRL+C to stop.")
    
    # --- Smooth Timing Configurations ---
    SWEEP_TIME = 1.0       # Rotation duration in seconds (changed from 1.5s to 1.0s)
    STEP_DELAY = 0.045     # 45ms delay per step to align with 50Hz refresh rate (prevents jitter)
    PAUSE_TIME = 2.0       # Waiting duration at each end state (changed to 2.0s)
    
    TARGET_ANGLE = 90.0    # Restricted movement limit
    TOTAL_STEPS = int(SWEEP_TIME / STEP_DELAY)  # ~22 steps for a 1.0s transition
    STEP_SIZE = TARGET_ANGLE / TOTAL_STEPS      # ~4.09 degrees per step
    
    try:
        # Secure starting position at 0 degrees safely
        set_angle(0)
        sleep(PAUSE_TIME)
        
        while True:
            # --- Sweep Forward: 0 to 90 degrees ---
            print("Sweeping: 0 -> 90 degrees (1.0s)")
            for step in range(TOTAL_STEPS + 1):
                angle = step * STEP_SIZE
                set_angle(angle)
                sleep(STEP_DELAY)
                
            print(f"Holding position at 90 degrees for {PAUSE_TIME} seconds...")
            sleep(PAUSE_TIME)  # Wait for 2 seconds before rotating back
            
            # --- Sweep Backward: 90 to 0 degrees ---
            print("Sweeping: 90 -> 0 degrees (1.0s)")
            for step in range(TOTAL_STEPS + 1):
                angle = TARGET_ANGLE - (step * STEP_SIZE)
                set_angle(angle)
                sleep(STEP_DELAY)
                
            print(f"Holding position at 0 degrees for {PAUSE_TIME} seconds...")
            sleep(PAUSE_TIME)  # Wait for 2 seconds before rotating forward again
            
    except KeyboardInterrupt:
        print("\nTest stopped by user.")
    finally:
        # Release the servos to kill holding current and prevent standing jitter/humming
        servo1.detach()
        servo2.detach()
        print("Servos successfully powered down and detached.")

if __name__ == "__main__":
    test_loop()