#!/bin/bash
# SQLi-Arena -- Initialize all Oracle lab databases (users/schemas)
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SYS_PASS="SqliArena2026"
CONTAINER="sqli-arena-oracle"

echo "[*] Setting up Oracle lab schemas..."

for i in $(seq 1 14); do
    INIT_FILE="$SCRIPT_DIR/oracle_lab${i}_init.sql"
    ORA_USER="sqli_arena_oracle_lab${i}"

    if [ -f "$INIT_FILE" ]; then
        echo "[*] Initializing Oracle lab $i (user: $ORA_USER)..."

        # Create user/schema if not exists, grant privileges
        docker exec -i $CONTAINER sqlplus -S "system/$SYS_PASS@//localhost:1521/XE" <<EOF
WHENEVER SQLERROR CONTINUE
BEGIN
    EXECUTE IMMEDIATE 'DROP USER ${ORA_USER} CASCADE';
EXCEPTION WHEN OTHERS THEN NULL;
END;
/
CREATE USER ${ORA_USER} IDENTIFIED BY sqli_arena_2026
    DEFAULT TABLESPACE USERS
    QUOTA UNLIMITED ON USERS;
GRANT CONNECT, RESOURCE TO ${ORA_USER};
GRANT CREATE SESSION TO ${ORA_USER};
GRANT CREATE TABLE TO ${ORA_USER};
GRANT CREATE SEQUENCE TO ${ORA_USER};
GRANT CREATE PROCEDURE TO ${ORA_USER};
GRANT CREATE VIEW TO ${ORA_USER};
EXIT;
EOF

        # Run the lab init SQL as the lab user
        docker exec -i $CONTAINER sqlplus -S "${ORA_USER}/sqli_arena_2026@//localhost:1521/XE" < "$INIT_FILE" 2>&1 | grep -v "^$" || true
    else
        echo "[-] Warning: $INIT_FILE not found, skipping lab $i"
    fi
done

echo ""
echo "[+] Oracle setup complete! 14 lab schemas initialized."
