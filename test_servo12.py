# test_servo.py
# A simple script to test an SG90 Servo on a Raspberry Pi 5

from gpiozero import Servo
from time import sleep

# --- IMPORTANT PIN NOTE ---
# This uses BCM GPIO numbering. 
# "GPIO 18" corresponds to PHYSICAL PIN 12 on the Pi board.
SERVO_PIN = 18

# SG90 servos typically operate on pulse widths between 0.5ms and 2.4ms.
# Adjusting these prevents the servo from hitting its physical limits and grinding.
my_servo = Servo(SERVO_PIN, min_pulse_width=0.0005, max_pulse_width=0.0024)

def test_loop():
    print(f"Starting Servo Test on GPIO {SERVO_PIN} (Press CTRL+C to stop)")
    
    try:
        while True:
            print("Position: Left (Minimum)")
            my_servo.min()
            sleep(1.5)
            
            print("Position: Center (Mid)")
            my_servo.mid()
            sleep(1.5)
            
            print("Position: Right (Maximum)")
            my_servo.max()
            sleep(1.5)
            
            print("Position: Center (Mid)")
            my_servo.mid()
            sleep(1.5)
            
    except KeyboardInterrupt:
        print("\nTest stopped by user.")
    finally:
        # Detach the servo to stop any idle jittering
        my_servo.detach()
        print("Servo detached and cleaned up.")

if __name__ == "__main__":
    test_loop()