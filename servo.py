from gpiozero import AngularServo
import time

print("Initializing SG90 Servo Test...")

# Make sure your signal wire (orange/yellow) is connected to GPIO 17 (Pin 11)
try:
    servo = AngularServo(17, min_angle=-180, max_angle=180, min_pulse_width=0.0005, max_pulse_width=0.0024)
    print("Servo initialized. Starting loop. Press Ctrl+C to stop.")
    
    while True:
        print("Opening flap (180 degrees)...")
        servo.angle = 180
        time.sleep(4.0)
        
        print("Closing flap (-180 degrees)...")
        servo.angle = -180
        time.sleep(4.0)

except KeyboardInterrupt:
    print("\nTest stopped by user.")
    servo.detach()
except Exception as e:
    print(f"An error occurred: {e}")