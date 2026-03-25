#!/bin/bash
# SQLi-Arena -- Initialize all Redis lab data
set -e

CONTAINER="sqli-arena-redis"
REDIS_PASS="sqli_arena_2026"
REDIS_CLI="redis-cli -a $REDIS_PASS --no-auth-warning"

echo "[*] Setting up Redis lab data..."

# Wait for Redis to be ready
echo "[*] Waiting for Redis to be ready..."
for i in $(seq 1 20); do
    if docker exec $CONTAINER redis-cli -a "$REDIS_PASS" --no-auth-warning ping 2>/dev/null | grep -q PONG; then
        echo "[+] Redis is ready."
        break
    fi
    if [ $i -eq 20 ]; then
        echo "[-] Redis not ready after 20 attempts. Aborting."
        exit 1
    fi
    echo "    Waiting... ($i/20)"
    sleep 2
done

# Flush existing lab data
echo "[*] Flushing existing lab data..."
docker exec $CONTAINER $REDIS_CLI FLUSHDB 2>/dev/null || true

# ===================== Lab 1: CRLF Protocol Injection =====================
echo "[*] Initializing Redis lab 1..."
docker exec $CONTAINER $REDIS_CLI MSET \
    "lab1:user:1001" '{"username":"alice","email":"alice@corp.io","role":"user"}' \
    "lab1:user:1002" '{"username":"bob","email":"bob@corp.io","role":"admin"}' \
    "lab1:session:abc123" '{"user":"alice","expires":"2026-12-31"}' \
    "lab1:config:app_name" "RedisKV Store v2.1" \
    "lab1:secret_key" "FLAG{rd_crlf_pr0t0c0l_1nj}" \
    "lab1:config:max_connections" "100" \
    "lab1:config:timeout" "30" \
    2>/dev/null

# ===================== Lab 2: Lua EVAL Injection =====================
echo "[*] Initializing Redis lab 2..."
docker exec $CONTAINER $REDIS_CLI MSET \
    "lab2:counter:visits" "4821" \
    "lab2:counter:logins" "312" \
    "lab2:analytics:daily" '{"page_views":1234,"unique":567,"bounce_rate":"32%"}' \
    "lab2:flag_store" "FLAG{rd_lu4_3v4l_1nj3ct}" \
    "lab2:config:lua_enabled" "true" \
    "lab2:rate_limit:api" "100" \
    2>/dev/null

# ===================== Lab 3: CONFIG SET File Write =====================
echo "[*] Initializing Redis lab 3..."
docker exec $CONTAINER $REDIS_CLI MSET \
    "lab3:admin:settings" '{"theme":"dark","lang":"en"}' \
    "lab3:admin:users_count" "47" \
    "lab3:backup:last_run" "2026-03-20T10:30:00Z" \
    "lab3:flag_data" "FLAG{rd_c0nf1g_s3t_wr1t3}" \
    2>/dev/null

# ===================== Lab 4: SLAVEOF Data Exfiltration =====================
echo "[*] Initializing Redis lab 4..."
docker exec $CONTAINER $REDIS_CLI MSET \
    "lab4:cache:user:1001" '{"username":"alice","email":"alice@corp.io"}' \
    "lab4:cache:user:1002" '{"username":"bob","email":"bob@corp.io"}' \
    "lab4:cache:user:1003" '{"username":"charlie","email":"charlie@corp.io"}' \
    "lab4:internal:api_key" "sk-prod-a8f3b2c1d4e5" \
    "lab4:internal:flag" "FLAG{rd_sl4v30f_3xf1l}" \
    "lab4:metrics:requests" "89421" \
    "lab4:metrics:errors" "127" \
    "lab4:metrics:latency_ms" "42" \
    2>/dev/null

# ===================== Lab 5: MODULE LOAD RCE =====================
echo "[*] Initializing Redis lab 5..."
docker exec $CONTAINER $REDIS_CLI MSET \
    "lab5:modules:loaded" '["redisearch","redisgraph"]' \
    "lab5:system:version" "Redis 7.0.15" \
    "lab5:system:os" "Linux 6.1.0" \
    "lab5:flag_vault" "FLAG{rd_m0dul3_l04d_rc3}" \
    "lab5:system:uptime" "864000" \
    2>/dev/null

echo ""
echo "[+] Redis setup complete! 5 labs initialized."
