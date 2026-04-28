#!/usr/bin/env python3
"""
Local HTTP bridge: CRT-591-M001 on RS-232 → dispense one keycard after check-in.

Install:  pip install pyserial flask
Run:      set CRT591_PORT=COM3 && python scripts/crt591_dispenser_server.py

Protocol: CRT_591_M001_Protocol.pdf / DLL spec — BCC = XOR of bytes STX..ETX inclusive.
Default baud 38400 (per DLL spec).

Dispense sequence (typical hopper → gate):
  1) INIT  CM=30 PM=33  (initialize, do not move card if already inside)
  2) MOVE  CM=32 PM=31  (feed from stacker toward IC position)
  3) MOVE  CM=32 PM=39  (eject out of gate to guest)
Adjust in dispense_sequence() if your installation differs.
"""

from __future__ import annotations

import os
import time

try:
    import serial
except ImportError:
    serial = None  # type: ignore

from flask import Flask, jsonify, request

app = Flask(__name__)


@app.after_request
def _cors(resp):
    resp.headers["Access-Control-Allow-Origin"] = "*"
    resp.headers["Access-Control-Allow-Methods"] = "GET, POST, OPTIONS"
    resp.headers["Access-Control-Allow-Headers"] = "Content-Type"
    return resp

STX = 0xF2
ETX = 0x03
CMT = 0x43


def build_frame(addr: int, cm: int, pm: int, data: bytes = b"") -> bytes:
    addr &= 0x0F
    body = bytes([CMT, cm & 0xFF, pm & 0xFF]) + data
    lh = (len(body) >> 8) & 0xFF
    ll = len(body) & 0xFF
    frame = bytes([STX, addr, lh, ll]) + body + bytes([ETX])
    bcc = 0
    for b in frame:
        bcc ^= b
    return frame + bytes([bcc & 0xFF])


def dispense_sequence(ser) -> None:
    addr = int(os.environ.get("CRT591_ADDR", "0"), 0)
    delay = float(os.environ.get("CRT591_STEP_DELAY", "0.45"))
    pre_eject_hold = max(0.0, float(os.environ.get("CRT591_PRE_EJECT_HOLD", "0")))
    # Some units need extra "out of gate" pulses to fully eject the card.
    eject_boost = max(0, int(os.environ.get("CRT591_EJECT_BOOST", "0")))
    eject_settle = float(os.environ.get("CRT591_EJECT_SETTLE", "0.25"))
    pre_frames = [
        build_frame(addr, 0x30, 0x33),  # initialize
        build_frame(addr, 0x32, 0x31),  # stacker → IC path
    ]
    for raw in pre_frames:
        ser.write(raw)
        ser.flush()
        time.sleep(delay)
        if ser.in_waiting:
            ser.read(ser.in_waiting)

    if pre_eject_hold > 0:
        time.sleep(pre_eject_hold)

    ser.write(build_frame(addr, 0x32, 0x39))  # out of gate
    ser.flush()
    time.sleep(delay)
    if ser.in_waiting:
        ser.read(ser.in_waiting)

    # Repeat eject command for weak rollers / sticky cards.
    for _ in range(eject_boost):
        ser.write(build_frame(addr, 0x32, 0x39))
        ser.flush()
        time.sleep(eject_settle)
        if ser.in_waiting:
            ser.read(ser.in_waiting)


def retract_sequence(ser) -> None:
    addr = int(os.environ.get("CRT591_ADDR", "0"), 0)
    allow_wait = float(os.environ.get("CRT591_ALLOW_IN_WAIT", "4.0"))
    step_delay = float(os.environ.get("CRT591_RETRACT_DELAY", "0.5"))

    # 1) Enable entry at the same gate, wait user to insert card.
    ser.write(build_frame(addr, 0x33, 0x30))
    ser.flush()
    time.sleep(allow_wait)
    if ser.in_waiting:
        ser.read(ser.in_waiting)

    # 2) Pull card inward, then close entry mode.
    for cm, pm in ((0x32, 0x33), (0x33, 0x31)):
        ser.write(build_frame(addr, cm, pm))
        ser.flush()
        time.sleep(step_delay)
        if ser.in_waiting:
            ser.read(ser.in_waiting)


@app.route("/health", methods=["GET"])
def health():
    return jsonify(ok=True, serial=serial is not None)


@app.route("/dispense", methods=["POST", "GET", "OPTIONS"])
def dispense():
    if request.method == "OPTIONS":
        return ("", 204)
    if serial is None:
        return jsonify(ok=False, error="pyserial not installed"), 500
    port = os.environ.get("CRT591_PORT", "COM3")
    baud = int(os.environ.get("CRT591_BAUD", "38400"))
    try:
        ser = serial.Serial(port, baudrate=baud, timeout=2)
    except Exception as e:
        return jsonify(ok=False, error=str(e), port=port), 503
    try:
        dispense_sequence(ser)
        return jsonify(ok=True, port=port)
    except Exception as e:
        return jsonify(ok=False, error=str(e)), 500
    finally:
        try:
            ser.close()
        except Exception:
            pass


@app.route("/retract", methods=["POST", "GET", "OPTIONS"])
def retract():
    if request.method == "OPTIONS":
        return ("", 204)
    if serial is None:
        return jsonify(ok=False, error="pyserial not installed"), 500
    port = os.environ.get("CRT591_PORT", "COM3")
    baud = int(os.environ.get("CRT591_BAUD", "38400"))
    try:
        ser = serial.Serial(port, baudrate=baud, timeout=2)
    except Exception as e:
        return jsonify(ok=False, error=str(e), port=port), 503
    try:
        retract_sequence(ser)
        return jsonify(ok=True, port=port, mode="retract")
    except Exception as e:
        return jsonify(ok=False, error=str(e)), 500
    finally:
        try:
            ser.close()
        except Exception:
            pass


if __name__ == "__main__":
    host = os.environ.get("CRT591_HTTP_HOST", "127.0.0.1")
    port = int(os.environ.get("CRT591_HTTP_PORT", "59101"))
    print(
        f"CRT591 bridge http://{host}:{port}/dispense "
        f"(retract: /retract, COM={os.environ.get('CRT591_PORT', 'COM3')})"
    )
    app.run(host=host, port=port, threaded=False)
