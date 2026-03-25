#!/bin/bash

echo "[*] SQLi-Arena MariaDB setup starting..."

if [[ $EUID -ne 0 ]]; then
    echo "[!] Please run this script as root (use sudo)"
    exit 1
fi

# The sqli_arena user should already exist from MySQL setup
# Just run MariaDB-specific init files
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
for sql_file in "$SCRIPT_DIR"/mariadb_lab*_init.sql; do
    if [[ -f "$sql_file" ]]; then
        echo "[*] Running $(basename "$sql_file")..."
        mysql < "$sql_file"
    fi
done

echo "[done] SQLi-Arena MariaDB setup complete"
