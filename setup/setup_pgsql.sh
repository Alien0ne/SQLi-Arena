#!/bin/bash

echo "[*] SQLi-Arena PostgreSQL setup starting..."

if [[ $EUID -ne 0 ]]; then
    echo "[!] Please run this script as root (use sudo)"
    exit 1
fi

# Create lab user if not exists
sudo -u postgres psql -c "DROP ROLE IF EXISTS sqli_arena;" 2>/dev/null
sudo -u postgres psql -c "CREATE ROLE sqli_arena WITH LOGIN PASSWORD 'sqli_arena_2026' CREATEDB;"

echo "[+] PostgreSQL user 'sqli_arena' created"

# Initialize all lab databases
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
for sql_file in "$SCRIPT_DIR"/pgsql_lab*_init.sql; do
    if [[ -f "$sql_file" ]]; then
        echo "[*] Running $(basename "$sql_file")..."
        sudo -u postgres psql -f "$sql_file"
    fi
done

# Grant access to all arena databases
for db in $(sudo -u postgres psql -t -c "SELECT datname FROM pg_database WHERE datname LIKE 'sqli_arena_pgsql_%';" | tr -d ' '); do
    if [[ -n "$db" ]]; then
        sudo -u postgres psql -d "$db" -c "GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO sqli_arena;"
        sudo -u postgres psql -d "$db" -c "GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;"
    fi
done

echo "[+] Permissions granted on sqli_arena_pgsql_* databases"
echo "[done] SQLi-Arena PostgreSQL setup complete"
