import time
from gpiozero import DigitalInputDevice

# Initialize the IR sensor on GPIO 4
# Most common IR obstacle modules use an 'active-low' configuration. 
# This means they output a 0 (LOW) when blocked and a 1 (HIGH) when clear.
ir_sensor = DigitalInputDevice(4, pull_up=True)

print("=========================================")
print("      IR Sensor Live Testing Script      ")
print("=========================================")
print("Pass your hand in front of the sensor to test.")
print("Press Ctrl+C to stop the test.\n")

try:
    # Track the previous state to keep terminal outputs clean
    last_state = None

    while True:
        # Read the current digital raw value (0 or 1)
        current_state = ir_sensor.value

        if current_state != last_state:
            if current_state == 0:
                print("[ OBJECT DETECTED ] ---> Sensor beam is broken!")
            else:
                print("[   BEAM CLEAR    ] ---> Path is wide open.")
            
            # Update the tracker
            last_state = current_state
        
        # Poll every 50 milliseconds for snappy, real-time responses
        time.sleep(0.05)

except KeyboardInterrupt:
    print("\nTesting session terminated. Happy building!")