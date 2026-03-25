#!/bin/bash
# SQLi-Arena -- Initialize all MongoDB lab collections
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
CONTAINER="sqli-arena-mongodb"
MONGO_USER="sqli_arena"
MONGO_PASS="sqli_arena_2026"

echo "[*] Setting up MongoDB lab collections..."

# Wait for MongoDB to be ready
echo "[*] Waiting for MongoDB to be ready..."
for i in $(seq 1 30); do
    if docker exec $CONTAINER mongosh --username "$MONGO_USER" --password "$MONGO_PASS" --authenticationDatabase admin --eval "db.adminCommand('ping')" --quiet 2>/dev/null; then
        echo "[+] MongoDB is ready."
        break
    fi
    if [ $i -eq 30 ]; then
        echo "[-] MongoDB not ready after 30 attempts. Aborting."
        exit 1
    fi
    echo "    Waiting... ($i/30)"
    sleep 2
done

# Initialize each lab
for i in $(seq 1 8); do
    INIT_FILE="$SCRIPT_DIR/mongodb_lab${i}_init.js"
    if [ -f "$INIT_FILE" ]; then
        echo "[*] Initializing MongoDB lab $i..."
        # Strip the "use ..." line since we specify the per-lab DB on the command line
        # db.php expects: sqli_arena_mongodb_lab{N}
        DB_NAME="sqli_arena_mongodb_lab${i}"
        docker cp "$INIT_FILE" $CONTAINER:/tmp/lab_init.js
        docker exec $CONTAINER bash -c "sed '/^use /d' /tmp/lab_init.js > /tmp/lab_clean.js"
        docker exec $CONTAINER mongosh --username "$MONGO_USER" --password "$MONGO_PASS" --authenticationDatabase admin "$DB_NAME" --file /tmp/lab_clean.js 2>&1 | grep -E "Lab|initialized|inserted|acknowledged" || true
    else
        echo "[-] Warning: $INIT_FILE not found, skipping lab $i"
    fi
done

echo ""
echo "[+] MongoDB setup complete! 8 lab collections initialized."
