#!/bin/bash

echo "[*] SQLi-Arena SQLite setup starting..."

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ARENA_DIR="$(dirname "$SCRIPT_DIR")"
SQLITE_DIR="$ARENA_DIR/data/sqlite"

mkdir -p "$SQLITE_DIR"

for sql_file in "$SCRIPT_DIR"/sqlite_lab*_init.sql; do
    if [[ -f "$sql_file" ]]; then
        lab_num=$(echo "$sql_file" | grep -oP 'lab\K\d+')
        db_file="$SQLITE_DIR/lab${lab_num}.db"
        echo "[*] Initializing lab${lab_num} -> $(basename "$db_file")..."
        rm -f "$db_file"
        sqlite3 "$db_file" < "$sql_file"
    fi
done

# Make files writable by web server
chmod 666 "$SQLITE_DIR"/*.db 2>/dev/null
chmod 777 "$SQLITE_DIR"

echo "[done] SQLi-Arena SQLite setup complete"
