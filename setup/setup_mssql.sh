#!/bin/bash
# SQLi-Arena -- Initialize all MSSQL lab databases
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SA_PASS="SqliArena2026!"
CONTAINER="sqli-arena-mssql"
SQLCMD="/opt/mssql-tools18/bin/sqlcmd"

echo "[*] Setting up MSSQL lab databases..."

# Create the sqli_arena login and user
echo "[*] Creating sqli_arena login with sysadmin role..."
docker exec $CONTAINER $SQLCMD -S localhost -U sa -P "$SA_PASS" -C -Q "
IF NOT EXISTS (SELECT * FROM sys.server_principals WHERE name = 'sqli_arena')
    CREATE LOGIN sqli_arena WITH PASSWORD = 'sqli_arena_2026', CHECK_POLICY = OFF;
-- Grant sysadmin for labs that require it (lab7 xp_cmdshell, lab8 sp_OACreate,
-- lab9 sp_execute_external_script, lab10 BULK INSERT, lab13 linked servers, lab14 EXECUTE AS)
IF IS_SRVROLEMEMBER('sysadmin', 'sqli_arena') = 0
    ALTER SERVER ROLE sysadmin ADD MEMBER sqli_arena;
" -b

# Initialize each lab database
for i in $(seq 1 18); do
    INIT_FILE="$SCRIPT_DIR/mssql_lab${i}_init.sql"
    if [ -f "$INIT_FILE" ]; then
        echo "[*] Initializing MSSQL lab $i..."
        docker cp "$INIT_FILE" $CONTAINER:/tmp/lab_init.sql
        docker exec $CONTAINER $SQLCMD -S localhost -U sa -P "$SA_PASS" -C -i /tmp/lab_init.sql -b 2>&1 | grep -v "^$" || true

        # Grant access to sqli_arena user for this lab's database
        DB_NAME="sqli_arena_mssql_lab${i}"
        docker exec $CONTAINER $SQLCMD -S localhost -U sa -P "$SA_PASS" -C -Q "
        USE [$DB_NAME];
        IF NOT EXISTS (SELECT * FROM sys.database_principals WHERE name = 'sqli_arena')
            CREATE USER sqli_arena FOR LOGIN sqli_arena;
        ALTER ROLE db_owner ADD MEMBER sqli_arena;
        " -b 2>&1 | grep -v "^$" || true

        # Lab 13: also grant access to the internal_db (linked server pivot target)
        if [ "$i" -eq 13 ]; then
            docker exec $CONTAINER $SQLCMD -S localhost -U sa -P "$SA_PASS" -C -Q "
            USE [sqli_arena_internal_db];
            IF NOT EXISTS (SELECT * FROM sys.database_principals WHERE name = 'sqli_arena')
                CREATE USER sqli_arena FOR LOGIN sqli_arena;
            ALTER ROLE db_owner ADD MEMBER sqli_arena;
            " -b 2>&1 | grep -v "^$" || true
        fi
    else
        echo "[-] Warning: $INIT_FILE not found, skipping lab $i"
    fi
done

echo ""
echo "[+] MSSQL setup complete! 18 lab databases initialized."
