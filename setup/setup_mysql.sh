#!/bin/bash

echo "[*] SQLi-Arena MySQL setup starting..."

if [[ $EUID -ne 0 ]]; then
    echo "[!] Please run this script as root (use sudo)"
    exit 1
fi

mysql <<'EOF'
-- Clean old user
DROP USER IF EXISTS 'sqli_arena'@'localhost';
FLUSH PRIVILEGES;

-- Create lab user
CREATE USER 'sqli_arena'@'localhost' IDENTIFIED BY 'sqli_arena_2026';

-- Grant access to all arena databases
GRANT ALL PRIVILEGES ON `sqli_arena_%`.* TO 'sqli_arena'@'localhost';

FLUSH PRIVILEGES;
EOF

echo "[+] MySQL user 'sqli_arena' created"
echo "[+] Permissions granted on sqli_arena_* databases"

# Initialize all lab databases
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
for sql_file in "$SCRIPT_DIR"/mysql_lab*_init.sql; do
    if [[ -f "$sql_file" ]]; then
        echo "[*] Running $(basename "$sql_file")..."
        mysql < "$sql_file"
    fi
done

echo "[✓] SQLi-Arena MySQL setup complete"
