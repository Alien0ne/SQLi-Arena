#!/bin/bash
# ============================================================
# SQLi-Arena -- Full Installation Script
# Clone, run this, and everything is ready.
#
# Usage: sudo bash install.sh
# ============================================================
set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SETUP_DIR="$SCRIPT_DIR/setup"
WEBROOT="/var/www/html/SQLi-Arena"
HOSTNAME_ALIAS="sqli-arena.local"

echo ""
echo -e "${CYAN}${BOLD}  ╔═══════════════════════════════════════════╗${NC}"
echo -e "${CYAN}${BOLD}  ║          SQLi-Arena  Installer            ║${NC}"
echo -e "${CYAN}${BOLD}  ║     SQL Injection Training Platform       ║${NC}"
echo -e "${CYAN}${BOLD}  ╚═══════════════════════════════════════════╝${NC}"
echo ""

# ============================================================
# 0. Root check
# ============================================================
if [[ $EUID -ne 0 ]]; then
    echo -e "${RED}[!] Please run as root: sudo bash install.sh${NC}"
    exit 1
fi

# Detect the real user (not root) for ownership
REAL_USER="${SUDO_USER:-$(whoami)}"

# ============================================================
# Step 1: Install system packages
# ============================================================
echo -e "${YELLOW}[1/10] Installing system packages...${NC}"

export DEBIAN_FRONTEND=noninteractive

# Determine PHP version available
PHP_VER=$(apt-cache show php 2>/dev/null | grep -oP 'Depends:.*php\K[0-9]+\.[0-9]+' | head -1)
if [[ -z "$PHP_VER" ]]; then
    PHP_VER="8.2"
fi

PACKAGES=(
    apache2
    libapache2-mod-php
    "php${PHP_VER}"
    "php${PHP_VER}-cli"
    "php${PHP_VER}-mysql"
    "php${PHP_VER}-pgsql"
    "php${PHP_VER}-sqlite3"
    "php${PHP_VER}-curl"
    "php${PHP_VER}-mbstring"
    "php${PHP_VER}-xml"
    "php${PHP_VER}-dev"
    php-pear
    mysql-server
    postgresql
    sqlite3
    curl
    docker.io
    docker-compose-plugin
)

echo -e "  [*] Updating package list..."
apt-get update -qq 2>&1 | tail -1

echo -e "  [*] Installing packages (this may take a few minutes)..."
for pkg in "${PACKAGES[@]}"; do
    if dpkg -l "$pkg" 2>/dev/null | grep -q "^ii"; then
        echo -e "  ${GREEN}[+]${NC} $pkg already installed"
    else
        echo -e "  [*] Installing $pkg..."
        apt-get install -y -qq "$pkg" 2>&1 | tail -1 || echo -e "  ${YELLOW}[!]${NC} Could not install $pkg (non-fatal)"
    fi
done

echo -e "  ${GREEN}All system packages installed.${NC}"

# ============================================================
# Step 2: Install optional PHP extensions (PECL)
# ============================================================
echo ""
echo -e "${YELLOW}[2/10] Installing PHP extensions for all engines...${NC}"

PHP_VER_DETECTED=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null)
echo -e "  ${GREEN}[+]${NC} PHP version: $PHP_VER_DETECTED"

install_pecl_ext() {
    local ext="$1"
    if php -m 2>/dev/null | grep -qi "^${ext}$"; then
        echo -e "  ${GREEN}[+]${NC} php-$ext already installed"
        return 0
    fi
    echo -e "  [*] Installing php-$ext via pecl..."
    pecl install "$ext" 2>&1 | tail -3 || { echo -e "  ${YELLOW}[!]${NC} pecl install $ext failed (non-fatal)"; return 0; }
    for ini_dir in /etc/php/${PHP_VER_DETECTED}/cli/conf.d /etc/php/${PHP_VER_DETECTED}/apache2/conf.d /etc/php/${PHP_VER_DETECTED}/fpm/conf.d; do
        if [[ -d "$ini_dir" ]]; then
            echo "extension=${ext}.so" > "$ini_dir/30-${ext}.ini"
        fi
    done
    echo -e "  ${GREEN}[+]${NC} php-$ext installed"
}

# MongoDB driver
install_pecl_ext mongodb

# Redis driver
install_pecl_ext redis

# MSSQL PDO driver
if ! php -m 2>/dev/null | grep -qi "pdo_sqlsrv"; then
    echo -e "  [*] Installing MSSQL drivers (sqlsrv + pdo_sqlsrv)..."
    pecl install sqlsrv 2>&1 | tail -3 || true
    pecl install pdo_sqlsrv 2>&1 | tail -3 || true
    for ini_dir in /etc/php/${PHP_VER_DETECTED}/cli/conf.d /etc/php/${PHP_VER_DETECTED}/apache2/conf.d; do
        if [[ -d "$ini_dir" ]]; then
            echo "extension=sqlsrv.so" > "$ini_dir/30-sqlsrv.ini" 2>/dev/null || true
            echo "extension=pdo_sqlsrv.so" > "$ini_dir/30-pdo_sqlsrv.ini" 2>/dev/null || true
        fi
    done
    echo -e "  ${GREEN}[+]${NC} MSSQL drivers installed"
else
    echo -e "  ${GREEN}[+]${NC} php-pdo_sqlsrv already installed"
fi

# OCI8 for Oracle (optional, requires Oracle Instant Client)
if ! php -m 2>/dev/null | grep -qi "oci8"; then
    echo -e "  ${YELLOW}[!]${NC} OCI8 not available. Oracle labs require Oracle Instant Client."
    echo -e "      See: https://www.php.net/manual/en/oci8.installation.php"
else
    echo -e "  ${GREEN}[+]${NC} php-oci8 already installed"
fi

echo -e "  ${GREEN}PHP extensions ready.${NC}"

# ============================================================
# Step 3: Start and configure system services
# ============================================================
echo ""
echo -e "${YELLOW}[3/10] Starting system services...${NC}"

# MySQL
if systemctl is-active --quiet mysql 2>/dev/null || systemctl is-active --quiet mariadb 2>/dev/null; then
    echo -e "  ${GREEN}[+]${NC} MySQL/MariaDB already running"
else
    systemctl start mysql 2>/dev/null || systemctl start mariadb 2>/dev/null || echo -e "  ${YELLOW}[!]${NC} Could not start MySQL"
    systemctl enable mysql 2>/dev/null || systemctl enable mariadb 2>/dev/null || true
    echo -e "  ${GREEN}[+]${NC} MySQL started"
fi

# PostgreSQL
if systemctl is-active --quiet postgresql 2>/dev/null; then
    echo -e "  ${GREEN}[+]${NC} PostgreSQL already running"
else
    systemctl start postgresql 2>/dev/null || echo -e "  ${YELLOW}[!]${NC} Could not start PostgreSQL"
    systemctl enable postgresql 2>/dev/null || true
    echo -e "  ${GREEN}[+]${NC} PostgreSQL started"
fi

# Docker
if systemctl is-active --quiet docker 2>/dev/null; then
    echo -e "  ${GREEN}[+]${NC} Docker already running"
else
    systemctl start docker 2>/dev/null || echo -e "  ${YELLOW}[!]${NC} Could not start Docker"
    systemctl enable docker 2>/dev/null || true
    echo -e "  ${GREEN}[+]${NC} Docker started"
fi

# Apache (start later after config)
echo -e "  ${GREEN}System services ready.${NC}"

# ============================================================
# Step 4: Configure Apache
# ============================================================
echo ""
echo -e "${YELLOW}[4/10] Configuring Apache...${NC}"

# Enable mod_rewrite
if command -v a2enmod &>/dev/null; then
    a2enmod rewrite &>/dev/null || true
    echo -e "  ${GREEN}[+]${NC} mod_rewrite enabled"
fi

# Set AllowOverride All for /var/www/
APACHE_CONF="/etc/apache2/apache2.conf"
if [[ -f "$APACHE_CONF" ]]; then
    if grep -A5 '<Directory /var/www/>' "$APACHE_CONF" | grep -q 'AllowOverride None'; then
        sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' "$APACHE_CONF"
        echo -e "  ${GREEN}[+]${NC} AllowOverride set to All"
    else
        echo -e "  ${GREEN}[+]${NC} AllowOverride already configured"
    fi
fi

# Start Apache
systemctl restart apache2 2>/dev/null || service apache2 restart 2>/dev/null || true
systemctl enable apache2 2>/dev/null || true
echo -e "  ${GREEN}Apache configured and running.${NC}"

# ============================================================
# Step 5: Start Docker containers
# ============================================================
echo ""
echo -e "${YELLOW}[5/10] Starting Docker containers (MSSQL, Oracle, MongoDB, Redis, HQL, GraphQL)...${NC}"

cd "$SCRIPT_DIR"

# Use docker compose (v2) or docker-compose (v1)
if docker compose version &>/dev/null; then
    COMPOSE_CMD="docker compose"
else
    COMPOSE_CMD="docker-compose"
fi

$COMPOSE_CMD up -d --build 2>&1 | grep -E "Created|Started|Building|Pulling|running|done" || true
echo -e "  ${GREEN}Docker containers starting.${NC}"

# ============================================================
# Step 6: Wait for containerized services to be healthy
# ============================================================
echo ""
echo -e "${YELLOW}[6/10] Waiting for services to become healthy...${NC}"

wait_for() {
    local name="$1" check_cmd="$2" max_attempts="$3"
    printf "  [*] Waiting for %-12s" "$name..."
    for i in $(seq 1 $max_attempts); do
        if eval "$check_cmd" &>/dev/null 2>&1; then
            echo -e " ${GREEN}ready${NC}"
            return 0
        fi
        sleep 3
    done
    echo -e " ${YELLOW}timeout (may still be starting)${NC}"
    return 0
}

wait_for "MySQL"     "mysql -u root -e 'SELECT 1' 2>/dev/null || mysqladmin ping -u root 2>/dev/null" 20
wait_for "PostgreSQL" "su - postgres -c 'psql -c \"SELECT 1\"' 2>/dev/null" 20
wait_for "MongoDB"   "docker exec sqli-arena-mongodb mongosh --username sqli_arena --password sqli_arena_2026 --authenticationDatabase admin --eval 'db.adminCommand(\"ping\")' --quiet" 30
wait_for "Redis"     "docker exec sqli-arena-redis redis-cli -a sqli_arena_2026 --no-auth-warning ping | grep -q PONG" 20
wait_for "MSSQL"     "docker exec sqli-arena-mssql /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P 'SqliArena2026!' -C -Q 'SELECT 1' -b -o /dev/null" 60
wait_for "Oracle"    "docker exec sqli-arena-oracle healthcheck.sh" 90
wait_for "HQL API"   "curl -sf http://localhost:8081/actuator/health" 60
wait_for "GraphQL"   "curl -sf http://localhost:4000/health" 30

echo -e "  ${GREEN}All services ready.${NC}"

# ============================================================
# Step 7: Initialize all lab databases
# ============================================================
echo ""
echo -e "${YELLOW}[7/10] Initializing 108 lab databases across 10 engines...${NC}"

run_setup() {
    local name="$1" script="$2"
    printf "  [*] %-14s" "$name..."
    if [[ -f "$script" ]]; then
        bash "$script" 2>&1 | tail -1
        echo -e " ${GREEN}done${NC}"
    else
        echo -e " ${YELLOW}script not found${NC}"
    fi
}

run_setup "MySQL (20)"      "$SETUP_DIR/setup_mysql.sh"
run_setup "PostgreSQL (15)" "$SETUP_DIR/setup_pgsql.sh"
run_setup "SQLite (10)"     "$SETUP_DIR/setup_sqlite.sh"
run_setup "MariaDB (8)"     "$SETUP_DIR/setup_mariadb.sh"
run_setup "MSSQL (18)"      "$SETUP_DIR/setup_mssql.sh"
run_setup "Oracle (14)"     "$SETUP_DIR/setup_oracle.sh"
run_setup "MongoDB (8)"     "$SETUP_DIR/setup_mongodb.sh"
run_setup "Redis (5)"       "$SETUP_DIR/setup_redis.sh"

echo -e "  ${GREEN}All lab databases initialized.${NC}"

# ============================================================
# Step 8: Deploy to web root
# ============================================================
echo ""
echo -e "${YELLOW}[8/10] Deploying to web root...${NC}"

# Remove old deployment
rm -rf "$WEBROOT"

# Copy project
cp -r "$SCRIPT_DIR" "$WEBROOT"

# Create data directories
mkdir -p "$WEBROOT/data/sqlite" "$WEBROOT/data/tasks"

# Set permissions
WEBUSER=$(ps aux | grep -E 'apache2|httpd' | grep -v grep | head -1 | awk '{print $1}')
WEBUSER="${WEBUSER:-www-data}"

chown -R "$WEBUSER:$WEBUSER" "$WEBROOT" 2>/dev/null || chown -R "$REAL_USER:$REAL_USER" "$WEBROOT"
chmod -R 755 "$WEBROOT"
chmod -R 777 "$WEBROOT/data"

echo -e "  ${GREEN}[+]${NC} Deployed to $WEBROOT"

# ============================================================
# Step 9: Configure hostname for Burp Suite
# ============================================================
echo ""
echo -e "${YELLOW}[9/10] Configuring hostname for Burp Suite proxy...${NC}"

if grep -q "$HOSTNAME_ALIAS" /etc/hosts 2>/dev/null; then
    echo -e "  ${GREEN}[+]${NC} $HOSTNAME_ALIAS already in /etc/hosts"
else
    echo "127.0.0.1 $HOSTNAME_ALIAS" >> /etc/hosts
    echo -e "  ${GREEN}[+]${NC} Added $HOSTNAME_ALIAS to /etc/hosts"
fi

# Restart Apache to pick up everything
systemctl restart apache2 2>/dev/null || service apache2 restart 2>/dev/null || true

# ============================================================
# Step 10: Verify installation
# ============================================================
echo ""
echo -e "${YELLOW}[10/10] Verifying installation...${NC}"

ERRORS=0

# Check web app
if curl -sf -o /dev/null "http://localhost/SQLi-Arena/" 2>/dev/null; then
    echo -e "  ${GREEN}[+]${NC} Web application is accessible"
else
    echo -e "  ${RED}[!]${NC} Web application not reachable"
    ERRORS=$((ERRORS + 1))
fi

# Check a lab loads
if curl -sf "http://localhost/SQLi-Arena/mysql/lab1" 2>/dev/null | grep -qi "lab 1"; then
    echo -e "  ${GREEN}[+]${NC} Lab pages are working"
else
    echo -e "  ${YELLOW}[!]${NC} Lab pages may not be loading (check mod_rewrite)"
fi

# Check Docker containers
RUNNING=$(docker ps --filter "name=sqli-arena" --format '{{.Names}}' 2>/dev/null | wc -l)
echo -e "  ${GREEN}[+]${NC} $RUNNING Docker containers running"

# ============================================================
# Done!
# ============================================================
echo ""
echo -e "${GREEN}${BOLD}  ╔═══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}${BOLD}  ║     SQLi-Arena Installation Complete!     ║${NC}"
echo -e "${GREEN}${BOLD}  ╚═══════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${BOLD}10 Database Engines | 108 Labs | All Live${NC}"
echo ""
echo -e "  ${BOLD}Access the lab:${NC}"
echo -e "    ${CYAN}http://localhost/SQLi-Arena/${NC}"
echo ""
echo -e "  ${BOLD}With Burp Suite proxy:${NC}"
echo -e "    ${CYAN}http://$HOSTNAME_ALIAS/SQLi-Arena/${NC}"
echo -e "    Proxy listener: ${CYAN}127.0.0.1:8080${NC}"
echo ""
echo -e "  ${BOLD}Services:${NC}"
echo -e "    MySQL/MariaDB   ${CYAN}localhost:3306${NC}"
echo -e "    PostgreSQL      ${CYAN}localhost:5432${NC}"
echo -e "    MSSQL           ${CYAN}localhost:1433${NC}"
echo -e "    Oracle          ${CYAN}localhost:1521${NC}"
echo -e "    MongoDB         ${CYAN}localhost:27017${NC}"
echo -e "    Redis           ${CYAN}localhost:6379${NC}"
echo -e "    HQL API         ${CYAN}localhost:8081${NC}"
echo -e "    GraphQL API     ${CYAN}localhost:4000${NC}"
echo ""
echo -e "  ${BOLD}Management:${NC}"
echo -e "    Control Panel:    ${CYAN}http://localhost/SQLi-Arena/admin${NC}"
echo -e "    Stop containers:  ${CYAN}bash setup/docker_stop.sh${NC}"
echo -e "    Start containers: ${CYAN}bash setup/docker_start.sh${NC}"
echo -e "    Full cleanup:     ${CYAN}sudo bash setup/cleanup.sh${NC}"
echo ""
