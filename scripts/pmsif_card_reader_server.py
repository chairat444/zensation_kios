#!/usr/bin/env python3
"""
HTTP bridge for Xeeder Hotel Lock Interface V1.723 (PMSif TCP).

Requires ILockInterfaceOffline.exe (or PMSif encoder service) listening on TCP.
Default from ILockSoft.ini: OfflineLockInterfacePort=8000.

Protocol (from PMSif_TCP_Demo + live probe):
  Request:  STX (0x02) + ASCII command + ETX (0x03)
  Response: STX + ASCII body + ETX

Read guest card: SEND 00000E (same as PMSif_TCP_Demo.exe Read card).
  Response e.g. 000000|M15A693C3|VD|R201|T04|D202605191554|O202605221800|Nempty|
Write check-in:  00000I|R{room}|T{type}|D{checkin}|O{checkout}|...
Check-out card:  00000B

Install:  pip install flask
Run:      set PMSIF_HOST=192.168.1.84 && python scripts/pmsif_card_reader_server.py
"""

from __future__ import annotations

import os
import socket
import time

from flask import Flask, jsonify, request

app = Flask(__name__)

STX = int(os.environ.get("PMSIF_STX", "2"), 0)
ETX = int(os.environ.get("PMSIF_ETX", "3"), 0)

# rl* message order in EncodeMsg.ini [1033] (1-based index -> 00000N)
ERROR_MESSAGES = {
    1: "Ok",
    2: "No card!",
    3: "No encoder found",
    4: "Invalid card",
    5: "Card type error",
    6: "Card read/write error",
    7: "Port not open",
    8: "Read data card ok",
    9: "Invalid parameter",
    10: "Operation not support",
    11: "Other error",
    12: "Port is in using",
    13: "Communication error",
    14: "Card is not empty, revoke it first",
    15: "Invalid password",
    16: "Operating failed",
    17: "Unknown error",
    18: "Card count over limit",
    19: "Invalid room number",
}


def frame(payload: str) -> bytes:
    return bytes([STX]) + payload.encode("ascii") + bytes([ETX])


def unframe(raw: bytes) -> str:
    if len(raw) >= 2 and raw[0] == STX and raw[-1] == ETX:
        return raw[1:-1].decode("ascii", errors="replace")
    return raw.decode("ascii", errors="replace")


def tcp_exchange(cmd: str, host: str, port: int, timeout: float, read_wait: float) -> str:
    """Send one framed command; wait up to read_wait seconds for the first response chunk."""
    sock = socket.socket()
    sock.settimeout(timeout)
    deadline = time.monotonic() + read_wait
    try:
        sock.connect((host, port))
        sock.sendall(frame(cmd))
        chunks: list[bytes] = []
        while time.monotonic() < deadline:
            sock.settimeout(max(0.2, deadline - time.monotonic()))
            try:
                part = sock.recv(8192)
            except socket.timeout:
                if chunks:
                    break
                continue
            if not part:
                break
            chunks.append(part)
            # Most encoder replies arrive in one STX..ETX frame.
            raw = b"".join(chunks)
            if len(raw) >= 2 and raw[-1] == ETX:
                break
        return unframe(b"".join(chunks))
    finally:
        try:
            sock.close()
        except OSError:
            pass


def ping_encoder(host: str, port: int) -> dict:
    """Quick connectivity check (invalid command should return 000008)."""
    try:
        body = tcp_exchange("", host, port, timeout=3.0, read_wait=1.0)
        return {"reachable": True, "probe_response": body}
    except OSError as exc:
        return {"reachable": False, "error": str(exc)}


def parse_error_code(body: str) -> tuple[int | None, str | None]:
    # Six-digit error only when the whole body is exactly that code (not 000000|... read payload).
    if len(body) == 6 and body.isdigit() and not body.startswith("000000"):
        code = int(body)
        if code == 1:
            return code, ERROR_MESSAGES.get(1)
        msg = ERROR_MESSAGES.get(code)
        return code, msg or f"Encoder error {body}"
    return None, None


def parse_card_payload(body: str) -> dict:
    """Parse PMSif pipe response (V1.723 read + legacy write formats)."""
    result: dict = {
        "raw": body,
        "status": None,
        "card_type": None,
        "machine_id": None,
        "room": None,
        "guest_type": None,
        "guest_name": None,
        "check_in": None,
        "check_out": None,
        "common_doors": None,
        "elevators": None,
        "is_empty": False,
        "is_checkout": False,
    }

    if body == "00000E":
        result["is_empty"] = True
        result["card_type"] = "empty"
        return result

    if body == "00000B":
        result["is_checkout"] = True
        result["card_type"] = "checkout"
        return result

    if "|" not in body and body.startswith("00000"):
        result["card_type"] = body
        return result

    parts = [p for p in body.split("|") if p]
    if parts:
        result["status"] = parts[0]
        result["card_type"] = parts[0]

    for part in parts[1:]:
        if part == "VD":
            result["vd"] = True
            continue
        if len(part) < 2:
            continue
        tag, value = part[0], part[1:]
        if tag == "M":
            result["machine_id"] = value
        elif tag == "R":
            result["room"] = value
        elif tag == "T":
            result["guest_type"] = value
        elif tag == "D":
            result["check_in"] = value
        elif tag == "O":
            result["check_out"] = value
        elif tag == "C":
            result["common_doors"] = value
        elif tag == "L":
            result["elevators"] = value
        elif tag == "N":
            result["guest_name"] = value
            if value.lower() == "empty":
                result["is_empty"] = True

    return result


def _format_guest_type(gt: str | None) -> str | None:
    if not gt:
        return None
    s = str(gt).strip()
    if not s:
        return None
    return s if s.upper().startswith("T") else f"T{s}"


def build_checkin_command(
    room: str,
    guest_type: str | None = None,
    check_in: str | None = None,
    check_out: str | None = None,
    common_doors: str | None = None,
    elevators: str | None = None,
) -> str:
    """Build 00000I|R...|T...|D...|O... check-in write command per V1.723 spec."""
    parts = ["00000I", f"R{room.strip()}"]
    gt = _format_guest_type(guest_type)
    if gt:
        parts.append(gt)
    if check_in:
        parts.append(f"D{check_in.strip()}")
    if check_out:
        parts.append(f"O{check_out.strip()}")
    if common_doors:
        parts.append(f"C{common_doors.strip()}")
    if elevators:
        parts.append(f"L{elevators.strip()}")
    return "|".join(parts)


def build_write_command(parsed: dict) -> str | None:
    """Reconstruct check-in write string from read fields (excludes M, VD, N)."""
    if parsed.get("is_empty") or parsed.get("is_checkout"):
        return None
    if not parsed.get("room"):
        return None
    return build_checkin_command(
        room=str(parsed["room"]),
        guest_type=parsed.get("guest_type"),
        check_in=parsed.get("check_in"),
        check_out=parsed.get("check_out"),
        common_doors=parsed.get("common_doors"),
        elevators=parsed.get("elevators"),
    )


def is_write_success(body: str) -> bool:
    return body in ("000000", "000001")


def encoder_host_port() -> tuple[str, int, float, float]:
    host = os.environ.get("PMSIF_HOST", "127.0.0.1")
    port = int(os.environ.get("PMSIF_PORT", "8000"))
    timeout = float(os.environ.get("PMSIF_TIMEOUT", "15"))
    read_wait = float(os.environ.get("PMSIF_WRITE_WAIT", os.environ.get("PMSIF_READ_WAIT", "5")))
    return host, port, timeout, read_wait


def write_command(cmd: str) -> dict:
    host, port, timeout, wait = encoder_host_port()
    ping = ping_encoder(host, port)
    if not ping.get("reachable"):
        return {
            "ok": False,
            "error": "connection_failed",
            "message": f"Cannot reach lock interface at {host}:{port}.",
            "host": host,
            "port": port,
            "ping": ping,
        }

    body = tcp_exchange(cmd, host, port, timeout, wait)
    if body == "":
        return {
            "ok": False,
            "error": "no_response",
            "message": "No response from encoder. Place a card on the encoder before writing.",
            "command": cmd,
            "host": host,
            "port": port,
        }

    if is_write_success(body):
        return {
            "ok": True,
            "message": "Card written successfully.",
            "command": cmd,
            "raw": body,
            "host": host,
            "port": port,
        }

    err_code, err_msg = parse_error_code(body)
    if err_code is not None:
        return {
            "ok": False,
            "error": "encoder_error",
            "error_code": body,
            "message": err_msg,
            "command": cmd,
            "host": host,
            "port": port,
        }

    return {
        "ok": False,
        "error": "unexpected_response",
        "message": f"Unexpected encoder response: {body}",
        "command": cmd,
        "raw": body,
        "host": host,
        "port": port,
    }


def write_checkin(payload: dict) -> dict:
    room = (payload.get("room") or "").strip()
    if not room:
        return {"ok": False, "error": "validation", "message": "room is required"}

    check_in = (payload.get("check_in") or "").strip()
    check_out = (payload.get("check_out") or "").strip()
    if not check_in or not check_out:
        return {
            "ok": False,
            "error": "validation",
            "message": "check_in and check_out are required (format YmdHi e.g. 202506140733)",
        }

    cmd = build_checkin_command(
        room=room,
        guest_type=payload.get("guest_type"),
        check_in=check_in,
        check_out=check_out,
        common_doors=payload.get("common_doors"),
        elevators=payload.get("elevators"),
    )
    result = write_command(cmd)
    result["mode"] = "checkin"
    return result


def write_checkout() -> dict:
    result = write_command("00000B")
    result["mode"] = "checkout"
    return result


def read_commands() -> list[str]:
    raw = os.environ.get("PMSIF_READ_CMDS") or os.environ.get("PMSIF_READ_CMD", "00000E")
    return [c.strip() for c in raw.split(",") if c.strip()]


def read_card() -> dict:
    host = os.environ.get("PMSIF_HOST", "127.0.0.1")
    port = int(os.environ.get("PMSIF_PORT", "8000"))
    timeout = float(os.environ.get("PMSIF_TIMEOUT", "25"))
    read_wait = float(os.environ.get("PMSIF_READ_WAIT", "10"))
    commands = read_commands()

    ping = ping_encoder(host, port)
    if not ping.get("reachable"):
        return {
            "ok": False,
            "error": "connection_failed",
            "message": "Cannot reach lock interface at {}:{}. Start ILockInterfaceOffline.exe.".format(host, port),
            "host": host,
            "port": port,
            "ping": ping,
        }

    last_cmd = commands[-1]
    last_body = ""
    for cmd in commands:
        last_cmd = cmd
        last_body = tcp_exchange(cmd, host, port, timeout, read_wait)
        if last_body:
            break

    if last_body == "":
        return {
            "ok": False,
            "error": "no_response",
            "message": (
                "Encoder accepted the command but returned no data. "
                "Place a guest card on the encoder, wait 2 seconds, then try again. "
                "Also check USB/COM cable and that ILockInterfaceOffline shows the encoder as connected."
            ),
            "host": host,
            "port": port,
            "commands_tried": commands,
            "command": last_cmd,
            "ping": ping,
            "hints": [
                "PMSIF_HOST must be this PC's LAN IP (not 127.0.0.1) when ILock binds to 0.0.0.0:8000",
                "Put the card on the encoder before clicking read",
                "Try PMSif_TCP_Demo.exe → connect to {}:{} → Read card to verify hardware".format(host, port),
            ],
        }

    err_code, err_msg = parse_error_code(last_body)
    if err_code is not None and err_code != 1:
        return {
            "ok": False,
            "error": "encoder_error",
            "error_code": last_body,
            "message": err_msg,
            "host": host,
            "port": port,
            "command": last_cmd,
            "ping": ping,
        }

    if err_code == 1:
        return {"ok": True, "message": "Ok", "raw": last_body, "parsed": {}, "command": last_cmd}

    parsed = parse_card_payload(last_body)
    write_preview = build_write_command(parsed)
    return {
        "ok": True,
        "raw": last_body,
        "parsed": parsed,
        "write_preview": write_preview,
        "write_fields_note": (
            "R,T,D,O = ต้องมีเมื่อเขียน check-in · C,L = common door/ลิฟต์ (ถ้ามี) · "
            "M,VD,N จากการอ่าน = metadata ไม่ใส่ในคำสั่งเขียน"
        ),
        "host": host,
        "port": port,
        "command": last_cmd,
        "ping": ping,
    }


@app.after_request
def _cors(resp):
    resp.headers["Access-Control-Allow-Origin"] = "*"
    resp.headers["Access-Control-Allow-Methods"] = "GET, POST, OPTIONS"
    resp.headers["Access-Control-Allow-Headers"] = "Content-Type"
    return resp


@app.route("/health", methods=["GET"])
def health():
    host = os.environ.get("PMSIF_HOST", "127.0.0.1")
    port = int(os.environ.get("PMSIF_PORT", "8000"))
    return jsonify(
        ok=True,
        host=host,
        port=port,
        read_commands=read_commands(),
        encoder_ping=ping_encoder(host, port),
    )


@app.route("/read", methods=["POST", "GET", "OPTIONS"])
def read_endpoint():
    if request.method == "OPTIONS":
        return ("", 204)
    try:
        return jsonify(read_card())
    except ConnectionRefusedError:
        return jsonify(
            ok=False,
            error="connection_refused",
            message="Cannot connect to lock interface. Is ILockInterfaceOffline.exe running?",
        ), 503
    except socket.timeout:
        return jsonify(ok=False, error="timeout", message="TCP timeout talking to encoder"), 504
    except OSError as exc:
        return jsonify(ok=False, error="os_error", message=str(exc)), 500


@app.route("/write", methods=["POST", "OPTIONS"])
def write_endpoint():
    """Write guest card. JSON: room, guest_type, check_in, check_out, common_doors?, elevators?"""
    if request.method == "OPTIONS":
        return ("", 204)
    payload = request.get_json(silent=True) or {}
    mode = (payload.get("mode") or "checkin").strip().lower()
    try:
        if mode == "checkout":
            return jsonify(write_checkout())
        return jsonify(write_checkin(payload))
    except ConnectionRefusedError:
        return jsonify(
            ok=False,
            error="connection_refused",
            message="Cannot connect to lock interface.",
        ), 503
    except socket.timeout:
        return jsonify(ok=False, error="timeout", message="TCP timeout"), 504
    except OSError as exc:
        return jsonify(ok=False, error="os_error", message=str(exc)), 500


@app.route("/send", methods=["POST", "OPTIONS"])
def send_raw():
    """Send arbitrary framed command (staff debugging). Body JSON: {\"command\": \"00000F\"}."""
    if request.method == "OPTIONS":
        return ("", 204)
    payload = request.get_json(silent=True) or {}
    cmd = (payload.get("command") or request.args.get("command") or "").strip()
    if not cmd:
        return jsonify(ok=False, error="missing_command"), 400

    host = os.environ.get("PMSIF_HOST", "127.0.0.1")
    port = int(os.environ.get("PMSIF_PORT", "8000"))
    timeout = float(os.environ.get("PMSIF_TIMEOUT", "12"))
    read_wait = float(os.environ.get("PMSIF_READ_WAIT", "1.5"))

    try:
        body = tcp_exchange(cmd, host, port, timeout, read_wait)
        return jsonify(ok=True, command=cmd, raw=body, parsed=parse_card_payload(body))
    except Exception as exc:
        return jsonify(ok=False, error=str(exc)), 500


if __name__ == "__main__":
    host = os.environ.get("PMSIF_HTTP_HOST", "127.0.0.1")
    port = int(os.environ.get("PMSIF_HTTP_PORT", "58002"))
    print(
        f"PMSif bridge http://{host}:{port}/read · /write "
        f"(encoder {os.environ.get('PMSIF_HOST', '127.0.0.1')}:"
        f"{os.environ.get('PMSIF_PORT', '8000')}, cmd={os.environ.get('PMSIF_READ_CMD', '00000E')})"
    )
    app.run(host=host, port=port, threaded=False)
