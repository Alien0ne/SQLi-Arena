#!/bin/bash
# SQLi-Arena -- Start all Docker containers
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo "[*] Starting all SQLi-Arena Docker containers..."
cd "$PROJECT_DIR"
docker compose up -d

# Wait for MongoDB
echo "[*] Waiting for MongoDB to be ready..."
for i in $(seq 1 30); do
    if docker exec sqli-arena-mongodb mongosh --username sqli_arena --password sqli_arena_2026 --authenticationDatabase admin --eval "db.adminCommand('ping')" --quiet &>/dev/null; then
        echo "[+] MongoDB is ready!"
        break
    fi
    [ $i -eq 30 ] && echo "[-] MongoDB timeout" && exit 1
    sleep 2
done

# Wait for Redis
echo "[*] Waiting for Redis to be ready..."
for i in $(seq 1 20); do
    if docker exec sqli-arena-redis redis-cli -a sqli_arena_2026 --no-auth-warning ping 2>/dev/null | grep -q PONG; then
        echo "[+] Redis is ready!"
        break
    fi
    [ $i -eq 20 ] && echo "[-] Redis timeout" && exit 1
    sleep 2
done

# Wait for MSSQL
echo "[*] Waiting for MSSQL to be ready..."
for i in $(seq 1 60); do
    if docker exec sqli-arena-mssql /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "SqliArena2026!" -C -Q "SELECT 1" -b &>/dev/null; then
        echo "[+] MSSQL is ready!"
        break
    fi
    [ $i -eq 60 ] && echo "[-] MSSQL timeout" && exit 1
    sleep 2
done

# Wait for MSSQL Internal (Server B for lab 13 linked server)
echo "[*] Waiting for MSSQL Internal (Server B) to be ready..."
for i in $(seq 1 60); do
    if docker exec sqli-arena-mssql-internal /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "Internal2026!" -C -Q "SELECT 1" -b &>/dev/null; then
        echo "[+] MSSQL Internal is ready!"
        break
    fi
    [ $i -eq 60 ] && echo "[-] MSSQL Internal timeout" && exit 1
    sleep 2
done

# Wait for Oracle
echo "[*] Waiting for Oracle to be ready..."
for i in $(seq 1 90); do
    if docker exec sqli-arena-oracle healthcheck.sh &>/dev/null; then
        echo "[+] Oracle is ready!"
        break
    fi
    [ $i -eq 90 ] && echo "[-] Oracle timeout" && exit 1
    sleep 2
done

# Wait for HQL backend
echo "[*] Waiting for HQL backend to be ready..."
for i in $(seq 1 60); do
    if curl -sf http://localhost:8081/actuator/health &>/dev/null; then
        echo "[+] HQL backend is ready!"
        break
    fi
    [ $i -eq 60 ] && echo "[-] HQL backend timeout" && exit 1
    sleep 2
done

# Wait for GraphQL backend
echo "[*] Waiting for GraphQL backend to be ready..."
for i in $(seq 1 30); do
    if curl -sf http://localhost:4000/health &>/dev/null; then
        echo "[+] GraphQL backend is ready!"
        break
    fi
    [ $i -eq 30 ] && echo "[-] GraphQL backend timeout" && exit 1
    sleep 2
done

echo ""
echo "[+] All containers are running!"
echo "    MongoDB:        localhost:27017"
echo "    Redis:          localhost:6379"
echo "    MSSQL (A):      localhost:1433"
echo "    MSSQL (B):      localhost:1434  (internal server for linked server lab)"
echo "    Oracle:         localhost:1521"
echo "    HQL API:        localhost:8081"
echo "    GraphQL:        localhost:4000"
echo ""
echo "[*] Next steps:"
echo "    If running standalone, initialize lab databases:"
echo "      bash setup/setup_mongodb.sh"
echo "      bash setup/setup_redis.sh"
echo "      bash setup/setup_mssql.sh"
echo "      bash setup/setup_oracle.sh"
echo ""
echo "    Or run the master setup to do everything at once:"
echo "      sudo bash setup.sh"
