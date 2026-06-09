from gpiozero import DistanceSensor
from time import sleep

# Define your BCM pins
TRIG = 23
ECHO = 24

print("Distance Measurement In Progress")
sensor = DistanceSensor(echo=ECHO, trigger=TRIG)

print("Waiting For Sensor To Settle")
sleep(2)

while True:
    # gpiozero returns distance in meters, so we multiply by 100 for cm
    distance_cm = round(sensor.distance * 100, 2)
    print("Distance:", distance_cm, "cm")
    sleep(1) # Add a small delay so it doesn't flood your terminal
