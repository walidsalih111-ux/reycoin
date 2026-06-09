#!/usr/bin/env python3
"""
MAX7219 LED Controller Driver & Test Suite for Raspberry Pi 5
--------------------------------------------------------------
Pins used (as requested):
  - DIN (Data In) = GPIO 21 (Physical Pin 40)
  - CS (Load / Chip Select) = GPIO 20 (Physical Pin 38)
  - CLK (Clock) = GPIO 16 (Physical Pin 36)

Other required connections:
  - VCC -> 5V (Physical Pin 2 or 4) [MAX7219 requires 5V to run reliably]
  - GND -> GND (Physical Pin 39, 34, 30, etc.)

Prerequisites on Raspberry Pi 5 (Bookworm OS):
  1. Create a virtual environment:
     python3 -m venv env
     source env/bin/activate
  2. Install gpiozero and gpiod:
     pip install gpiozero gpiod

Run the script:
  python3 /var/www/reycoin/test/led.py
"""

import time
import sys
from gpiozero import OutputDevice

# --- PIN CONFIGURATION ---
PIN_DIN = 21  # GPIO 21 (Physical Pin 40)
PIN_CS  = 20  # GPIO 20 (Physical Pin 38)
PIN_CLK = 16  # GPIO 16 (Physical Pin 36)

# --- FONT DEFINITIONS FOR 8x8 MATRIX SCROLLING ---
# Simple 8x8 font map for uppercase A-Z, 0-9, space, and some punctuation.
FONT_8x8 = {
    ' ': [0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00],
    'A': [0x18, 0x24, 0x42, 0x42, 0x7E, 0x42, 0x42, 0x42],
    'B': [0x7C, 0x22, 0x22, 0x3C, 0x22, 0x22, 0x22, 0x7C],
    'C': [0x3C, 0x42, 0x40, 0x40, 0x40, 0x40, 0x42, 0x3C],
    'D': [0x78, 0x24, 0x22, 0x22, 0x22, 0x22, 0x24, 0x78],
    'E': [0x7E, 0x40, 0x40, 0x78, 0x40, 0x40, 0x40, 0x7E],
    'F': [0x7E, 0x40, 0x40, 0x78, 0x40, 0x40, 0x40, 0x40],
    'G': [0x3C, 0x42, 0x40, 0x40, 0x4E, 0x42, 0x42, 0x3C],
    'H': [0x42, 0x42, 0x42, 0x7E, 0x42, 0x42, 0x42, 0x42],
    'I': [0x3E, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x3E],
    'J': [0x1F, 0x04, 0x04, 0x04, 0x04, 0x04, 0x24, 0x18],
    'K': [0x42, 0x44, 0x48, 0x70, 0x48, 0x44, 0x42, 0x42],
    'L': [0x40, 0x40, 0x40, 0x40, 0x40, 0x40, 0x40, 0x7E],
    'M': [0x42, 0x66, 0x5A, 0x42, 0x42, 0x42, 0x42, 0x42],
    'N': [0x42, 0x62, 0x52, 0x4A, 0x46, 0x42, 0x42, 0x42],
    'O': [0x3C, 0x42, 0x42, 0x42, 0x42, 0x42, 0x42, 0x3C],
    'P': [0x7C, 0x42, 0x42, 0x7C, 0x40, 0x40, 0x40, 0x40],
    'Q': [0x3C, 0x42, 0x42, 0x42, 0x42, 0x4A, 0x44, 0x3A],
    'R': [0x7C, 0x42, 0x42, 0x7C, 0x48, 0x44, 0x42, 0x42],
    'S': [0x3E, 0x40, 0x40, 0x3C, 0x02, 0x02, 0x02, 0x7C],
    'T': [0x7E, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08],
    'U': [0x42, 0x42, 0x42, 0x42, 0x42, 0x42, 0x42, 0x3C],
    'V': [0x42, 0x42, 0x42, 0x42, 0x42, 0x24, 0x24, 0x18],
    'W': [0x42, 0x42, 0x42, 0x42, 0x42, 0x5A, 0x66, 0x42],
    'X': [0x42, 0x42, 0x24, 0x18, 0x18, 0x24, 0x42, 0x42],
    'Y': [0x42, 0x42, 0x24, 0x18, 0x08, 0x08, 0x08, 0x08],
    'Z': [0x7E, 0x02, 0x04, 0x08, 0x10, 0x20, 0x40, 0x7E],
    '0': [0x3C, 0x42, 0x46, 0x4A, 0x52, 0x62, 0x42, 0x3C],
    '1': [0x18, 0x28, 0x08, 0x08, 0x08, 0x08, 0x08, 0x3E],
    '2': [0x3C, 0x42, 0x02, 0x04, 0x18, 0x20, 0x40, 0x7E],
    '3': [0x3C, 0x42, 0x02, 0x1C, 0x02, 0x02, 0x42, 0x3C],
    '4': [0x08, 0x18, 0x28, 0x48, 0x7E, 0x08, 0x08, 0x08],
    '5': [0x7E, 0x40, 0x40, 0x7C, 0x02, 0x02, 0x02, 0x7C],
    '6': [0x3C, 0x40, 0x40, 0x7C, 0x42, 0x42, 0x42, 0x3C],
    '7': [0x7E, 0x02, 0x04, 0x08, 0x10, 0x20, 0x20, 0x20],
    '8': [0x3C, 0x42, 0x42, 0x3C, 0x42, 0x42, 0x42, 0x3C],
    '9': [0x3C, 0x42, 0x42, 0x3E, 0x02, 0x02, 0x42, 0x3C],
    '!': [0x08, 0x08, 0x08, 0x08, 0x08, 0x00, 0x08, 0x00],
    '-': [0x00, 0x00, 0x00, 0x3E, 0x00, 0x00, 0x00, 0x00],
    '+': [0x00, 0x08, 0x08, 0x3E, 0x08, 0x08, 0x00, 0x00],
}


class MAX7219:
    """Class to control a MAX7219 LED driver chip using bit-banged SPI on Pi 5."""
    
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
        """Initializes the GPIO outputs and configures the MAX7219."""
        self.din_pin = din_pin
        self.cs_pin = cs_pin
        self.clk_pin = clk_pin
        
        # Initialize GPIO pins using gpiozero
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

    def write_reg(self, register, data):
        """Sends a 16-bit packet: 8-bit register address + 8-bit data."""
        self.cs.off()  # Pull CS Low to enable chip select
        self._send_byte(register)
        self._send_byte(data)
        self.cs.on()   # Pull CS High to latch data into registers

    def init_device(self):
        """Configures the MAX7219 register presets."""
        self.write_reg(self.REG_SHUTDOWN, 0x01)     # Wake up from shutdown mode
        self.write_reg(self.REG_DISPLAY_TEST, 0x00) # Disable hardware display test
        self.write_reg(self.REG_DECODE_MODE, 0x00)  # Standard non-decode mode (Matrix setup)
        self.write_reg(self.REG_SCAN_LIMIT, 0x07)   # Enable all 8 digits/rows (0-7)
        self.set_brightness(3)                      # Set medium-low brightness (0-15)
        self.clear()

    def set_brightness(self, level):
        """Sets brightness level from 0 (lowest) to 15 (highest)."""
        level = max(0, min(15, level))
        self.write_reg(self.REG_INTENSITY, level)

    def set_decode_mode(self, code_b_decode=False):
        """Sets decode modes: False = raw matrix/segment control, True = Code-B decoder."""
        val = 0xFF if code_b_decode else 0x00
        self.write_reg(self.REG_DECODE_MODE, val)

    def set_display_test(self, enable=False):
        """Enables/disables hardware test mode (lights up every single LED)."""
        val = 0x01 if enable else 0x00
        self.write_reg(self.REG_DISPLAY_TEST, val)

    def clear(self):
        """Clears all registers (turns off all LEDs/digits)."""
        for reg in range(1, 9):
            self.write_reg(reg, 0x00)

    def write_row(self, row, byte_val):
        """Writes data to a single row (1-8)."""
        if 1 <= row <= 8:
            self.write_reg(row, byte_val)

    def write_matrix(self, row_array):
        """Updates all 8 rows of a matrix from an array of 8 byte values."""
        for i, val in enumerate(row_array[:8], start=1):
            self.write_reg(i, val)

    def close(self):
        """Gracefully closes GPIO lines and clears display."""
        self.clear()
        self.din.close()
        self.cs.close()
        self.clk.close()


# --- PRECONFIGURED ANIMATION & TEXT TESTS ---

def test_hardware_display_test(device):
    """Flashes all LEDs on and off."""
    print("\n--- Running Display Test (All LEDs ON) ---")
    device.set_display_test(True)
    time.sleep(2)
    device.set_display_test(False)
    print("Display Test completed.")

def test_matrix_sweep(device):
    """Draws scanning sweeps across the matrix."""
    print("\n--- Running Row and Column Sweep ---")
    device.clear()
    
    # 1. Row Sweep
    for i in range(1, 9):
        device.write_row(i, 0xFF)
        time.sleep(0.12)
        device.write_row(i, 0x00)
    
    # 2. Diagonal Cascade
    for step in range(8):
        frame = []
        for row in range(8):
            val = 1 << ((row + step) % 8)
            frame.append(val)
        device.write_matrix(frame)
        time.sleep(0.12)
    device.clear()

def test_matrix_art(device):
    """Displays a simple smiling face and heart on an 8x8 matrix."""
    print("\n--- Running 8x8 Matrix Art Test ---")
    
    smiley = [
        0b00111100,  # Row 1
        0b01000010,  # Row 2
        0b10100101,  # Row 3 (Eyes)
        0b10000001,  # Row 4
        0b10100101,  # Row 5 (Mouth)
        0b10011001,  # Row 6
        0b01000010,  # Row 7
        0b00111100   # Row 8
    ]
    
    heart = [
        0b00000000,
        0b01100110,
        0b11111111,
        0b11111111,
        0b01111110,
        0b00111100,
        0b00011000,
        0b00000000
    ]

    print("Showing Smiley Face...")
    device.write_matrix(smiley)
    time.sleep(2)

    print("Showing Heart...")
    device.write_matrix(heart)
    time.sleep(2)
    device.clear()

def test_scrolling_text(device, text="PI 5 HI", delay=0.08):
    """Scrolls text horizontally across an 8x8 matrix."""
    print(f"\n--- Scrolling Matrix Text: '{text}' ---")
    device.clear()
    
    # Generate full visual bit column map for the string
    full_buffer = []
    for char in text.upper():
        # Get character matrix grid (array of 8 rows, where each byte is a row)
        char_matrix = FONT_8x8.get(char, FONT_8x8[' '])
        
        # We need to transpose the row data to columns to slide horizontally.
        for col_idx in range(8):
            col_byte = 0
            for row_idx in range(8):
                # Isolate the bit representing this column index inside the row byte
                bit = (char_matrix[row_idx] >> (7 - col_idx)) & 1
                col_byte |= (bit << row_idx)
            full_buffer.append(col_byte)
            
        # Add 1 empty column space between characters
        full_buffer.append(0x00)

    # Pad start and end so text scrolls in and out elegantly
    padding = [0x00] * 8
    scroll_data = padding + full_buffer + padding

    # Scroll the display window
    for start in range(len(scroll_data) - 7):
        # Extract the current 8 columns to show
        window_cols = scroll_data[start:start+8]
        
        # Transpose back into 8 row bytes to write to the MAX7219 registers
        frame_rows = [0] * 8
        for r in range(8):
            row_byte = 0
            for c in range(8):
                bit = (window_cols[c] >> r) & 1
                row_byte |= (bit << (7 - c))
            frame_rows[r] = row_byte
            
        device.write_matrix(frame_rows)
        time.sleep(delay)
        
    device.clear()

def test_seven_segment(device):
    """Runs a 7-segment display counter (best for 8-digit displays)."""
    print("\n--- Running 7-Segment Code-B Character Test ---")
    
    device.set_decode_mode(code_b_decode=True)
    device.clear()

    print("Counting 0-9 on each digit...")
    for digit in range(1, 9):
        for num in range(10):
            device.write_reg(digit, num)
            time.sleep(0.04)
        time.sleep(0.08)
    
    # Code B Map: 0-9=0-9, 10=-, 11=H, 12=E, 13=L, 14=P, 15=Blank
    # Display "HELP  26" (Digit 8 is leftmost, Digit 1 is rightmost)
    message = [6, 2, 15, 15, 14, 13, 12, 11] # Mapped left to right: "H E L P _ _ 2 6"
    
    print("Displaying text: HELP  26")
    for i, code in enumerate(message, start=1):
        device.write_reg(i, code)
    time.sleep(3.0)
    
    device.set_decode_mode(code_b_decode=False)
    device.clear()

def test_brightness(device):
    """Cycles through the 16 brightness levels of the chip."""
    print("\n--- Running Brightness Sweep Test ---")
    device.write_matrix([0xFF] * 8) # Turn on all LEDs
        
    for level in range(16):
        print(f"Brightness level: {level}/15")
        device.set_brightness(level)
        time.sleep(0.2)
        
    device.set_brightness(3) # Reset to default safe brightness
    device.clear()


# --- MAIN MENU EXECUTION ---
if __name__ == "__main__":
    device = None
    try:
        # Instantiate Driver with configured constants
        device = MAX7219(din_pin=PIN_DIN, cs_pin=PIN_CS, clk_pin=PIN_CLK)
        
        while True:
            print("\n======================================")
            print("   MAX7219 Raspberry Pi 5 Test Tool   ")
            print("======================================")
            print("1. All-On Display Test (Universal)")
            print("2. LED Sweep Animation (Best for Matrix)")
            print("3. Draw Smiley & Heart (Best for Matrix)")
            print("4. Scroll Text Marquee (Best for Matrix)")
            print("5. Digit Counter & Message (Best for 7-Segment)")
            print("6. Brightness Sweep Test (Universal)")
            print("7. Run All Tests Sequentially")
            print("8. Exit")
            print("======================================")
            
            choice = input("Select an option (1-8): ").strip()
            
            if choice == '1':
                test_hardware_display_test(device)
            elif choice == '2':
                test_matrix_sweep(device)
            elif choice == '3':
                test_matrix_art(device)
            elif choice == '4':
                user_text = input("Enter text to scroll (letters A-Z, numbers, space): ").strip()
                if not user_text:
                    user_text = "PI 5 POWER"
                test_scrolling_text(device, user_text)
            elif choice == '5':
                test_seven_segment(device)
            elif choice == '6':
                test_brightness(device)
            elif choice == '7':
                test_hardware_display_test(device)
                test_matrix_sweep(device)
                test_matrix_art(device)
                test_scrolling_text(device, "HELLO RASPBERRY PI 5")
                test_seven_segment(device)
                test_brightness(device)
                print("\nAll tests finished! Display cleared.")
            elif choice == '8' or choice.lower() == 'exit':
                print("Clearing display and closing GPIO resources. Goodbye!")
                break
            else:
                print("Invalid choice, please select 1 to 8.")
                
    except KeyboardInterrupt:
        print("\nTest interrupted by user. Exiting safely...")
    finally:
        if device:
            device.close()