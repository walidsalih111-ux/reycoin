#!/usr/bin/env python3
import time
from gpiozero import DigitalInputDevice

# Initialize the LDR on GPIO 22
# We set pull_up=False because your 10k ohm resistor acts as the physical pull-down
ldr = DigitalInputDevice(22, pull_up=False)

print("=========================================")
print("     RECYCOIN Chute LDR Test Script      ")
print("=========================================")
print("Monitoring GPIO 22. Press Ctrl+C to stop.\n")

try:
    # Track the previous state to avoid flooding the terminal with repetitive text
    last_state = None

    while True:
        # ldr.is_active is True when the pin reads HIGH (3.3V / Light)
        current_state = ldr.is_active
        
        if current_state != last_state:
            if current_state:
                print("[ STATUS ] Chute is CLEAR  -> Light detected.")
            else:
                print("[ STATUS ] BOTTLE DETECTED -> Chute is blocked!")
            
            # Update the last known state
            last_state = current_state
            
        # Fast sampling rate for responsive object detection
        time.sleep(0.1)

except KeyboardInterrupt:
    print("\n[ INFO ] Test script stopped by user. Exiting cleanly.")