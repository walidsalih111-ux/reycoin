import time
from gpiozero import AngularServo

# BCM Pin 6 configuration
SERVO_PIN = 17

# Most MG99R/MG996R servos map a full 180-degree rotation to pulse widths
# between 0.5ms (500us) and 2.5ms (2500us). 
# If your servo chatters at the extreme ends, narrow these boundaries slightly (e.g., 0.001 to 0.002).
MIN_PULSE = 0.0005
MAX_PULSE = 0.0025

print("Initializing MG99R Servo on GPIO 6...")
servo = AngularServo(
    SERVO_PIN, 
    min_angle=0, 
    max_angle=180, 
    min_pulse_width=MIN_PULSE, 
    max_pulse_width=MAX_PULSE
)

try:
    print("Starting loop. Press Ctrl+C to terminate safely.")
    while True:
        print("Moving to 180 degrees...")
        servo.angle = 180
        time.sleep(3)

        print("Moving to 90 degrees...")
        servo.angle = 90
        time.sleep(3)

except KeyboardInterrupt:
    print("\nTest intercepted by user. Safely resetting servo to center...")
    servo.angle = 180
    time.sleep(3)
    
    # Detach the servo signal to prevent ongoing jitter/holding current
    servo.close()
    print("Cleanup complete.")
