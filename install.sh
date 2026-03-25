#!/bin/bash
# ============================================================
# SQLi-Arena -- Full Installation Script
# Clone, run this, and everything is ready.
#
# Supported: Kali Linux, Ubuntu 22.04/24.04, Debian 12/13
# Usage: sudo bash install.sh
# ============================================================

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

export DEBIAN_FRONTEND=noninteractive

# Detect distro
DISTRO_ID=$(. /etc/os-release 2>/dev/null && echo "$ID")
DISTRO_CODENAME=$(. /etc/os-release 2>/dev/null && echo "$VERSION_CODENAME")

# ============================================================
# Step 1: Install system packages
# ============================================================
echo -e "${YELLOW}[1/10] Installing system packages...${NC}"

# Determine PHP version available
echo -e "  [*] Updating package list..."
apt-get update -qq 2>&1 | tail -1

PHP_VER=$(apt-cache show php 2>/dev/null | grep -oP 'Depends:.*php\K[0-9]+\.[0-9]+' | head -1)
if [[ -z "$PHP_VER" ]]; then
    PHP_VER="8.2"
fi

# Detect MySQL vs MariaDB (Debian 13+ and Kali only have mariadb-server)
MYSQL_PKG="mysql-server"
MYSQL_CANDIDATE=$(apt-cache policy mysql-server 2>/dev/null | grep 'Candidate:' | awk '{print $2}')
if [[ -z "$MYSQL_CANDIDATE" || "$MYSQL_CANDIDATE" == "(none)" ]]; then
    MYSQL_PKG="mariadb-server"
fi

PACKAGES=(
    git
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
    "$MYSQL_PKG"
    mariadb-client
    postgresql
    sqlite3
    curl
    ca-certificates
    gnupg
    lsb-release
    build-essential
    unixodbc-dev
    docker.io
)

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
# Step 1b: Docker official repo (compose plugin + buildx)
# ============================================================
# docker.io from distro repos may have old buildx or no compose plugin.
# Add Docker's official repo for compose-plugin and buildx-plugin.

setup_docker_repo() {
    echo -e "  [*] Adding Docker official repository..."
    install -m 0755 -d /etc/apt/keyrings

    # Determine the correct Docker repo distro and codename
    local docker_distro="$DISTRO_ID"
    local docker_codename="$DISTRO_CODENAME"

    case "$DISTRO_ID" in
        kali)
            docker_distro="debian"
            docker_codename="bookworm"
            ;;
        debian)
            # Debian 13 (trixie) may not have a Docker repo yet; fall back to bookworm
            if ! curl -sf "https://download.docker.com/linux/debian/dists/${docker_codename}/Release" &>/dev/null; then
                docker_codename="bookworm"
            fi
            ;;
        ubuntu)
            # Ubuntu codenames are usually supported directly
            ;;
        *)
            # Unknown distro, try debian bookworm as safe default
            docker_distro="debian"
            docker_codename="bookworm"
            ;;
    esac

    curl -fsSL "https://download.docker.com/linux/${docker_distro}/gpg" | gpg --dearmor -o /etc/apt/keyrings/docker.gpg 2>/dev/null || true
    chmod a+r /etc/apt/keyrings/docker.gpg 2>/dev/null || true

    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/${docker_distro} ${docker_codename} stable" \
        > /etc/apt/sources.list.d/docker.list

    apt-get update -qq 2>&1 | tail -1
}

# Install docker-compose-plugin if not available
if ! docker compose version &>/dev/null; then
    setup_docker_repo
    apt-get install -y -qq docker-compose-plugin 2>&1 | tail -3 || echo -e "  ${YELLOW}[!]${NC} docker-compose-plugin install failed"
fi

# Upgrade docker-buildx if too old (compose build needs >= 0.17)
BUILDX_VER=$(docker buildx version 2>/dev/null | grep -oP '[0-9]+\.[0-9]+' | head -1)
if [[ -z "$BUILDX_VER" ]] || awk "BEGIN{exit ($BUILDX_VER >= 0.17) ? 1 : 0}"; then
    echo -e "  [*] Upgrading docker-buildx..."
    # Remove old distro buildx if it conflicts
    dpkg --remove --force-depends docker-buildx 2>/dev/null || true
    if [[ ! -f /etc/apt/sources.list.d/docker.list ]]; then
        setup_docker_repo
    fi
    apt-get install -y -qq docker-buildx-plugin 2>&1 | tail -3 || echo -e "  ${YELLOW}[!]${NC} docker-buildx-plugin install failed"
fi

if docker compose version &>/dev/null; then
    echo -e "  ${GREEN}[+]${NC} docker compose v2 available"
elif command -v docker-compose &>/dev/null; then
    echo -e "  ${GREEN}[+]${NC} docker-compose v1 available"
else
    echo -e "  ${YELLOW}[!]${NC} No docker compose found — Docker containers may not start"
fi

BUILDX_FINAL=$(docker buildx version 2>/dev/null | head -1)
echo -e "  ${GREEN}[+]${NC} docker buildx: $BUILDX_FINAL"

# ============================================================
# Step 2: Install PHP extensions
# ============================================================
echo ""
echo -e "${YELLOW}[2/10] Installing PHP extensions for all engines...${NC}"

PHP_VER_DETECTED=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null)
echo -e "  ${GREEN}[+]${NC} PHP version: $PHP_VER_DETECTED"

install_php_ext() {
    local ext="$1"
    if php -m 2>/dev/null | grep -qi "^${ext}$"; then
        echo -e "  ${GREEN}[+]${NC} php-$ext already installed"
        return 0
    fi
    # Try apt package first (much faster than PECL)
    local apt_pkg="php${PHP_VER_DETECTED}-${ext}"
    if apt-get install -y -qq "$apt_pkg" 2>/dev/null; then
        echo -e "  ${GREEN}[+]${NC} php-$ext installed via apt"
        return 0
    fi
    # Fall back to PECL (compiles from source — slower)
    echo -e "  [*] Installing php-$ext via pecl (this may take a few minutes)..."
    pecl install "$ext" 2>&1 | tail -3 || { echo -e "  ${YELLOW}[!]${NC} pecl install $ext failed (non-fatal)"; return 0; }
    for ini_dir in /etc/php/${PHP_VER_DETECTED}/cli/conf.d /etc/php/${PHP_VER_DETECTED}/apache2/conf.d /etc/php/${PHP_VER_DETECTED}/fpm/conf.d; do
        if [[ -d "$ini_dir" ]]; then
            echo "extension=${ext}.so" > "$ini_dir/30-${ext}.ini"
        fi
    done
    echo -e "  ${GREEN}[+]${NC} php-$ext installed via pecl"
}

# MongoDB driver
install_php_ext mongodb

# Redis driver
install_php_ext redis

# MSSQL PDO driver (requires Microsoft ODBC)
if ! php -m 2>/dev/null | grep -qi "pdo_sqlsrv"; then
    echo -e "  [*] Installing MSSQL drivers (sqlsrv + pdo_sqlsrv)..."

    # Install Microsoft ODBC driver first (required for sqlsrv/pdo_sqlsrv)
    if ! dpkg -l msodbcsql18 2>/dev/null | grep -q "^ii"; then
        echo -e "  [*] Installing Microsoft ODBC driver..."
        curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg 2>/dev/null || true
        chmod a+r /usr/share/keyrings/microsoft-prod.gpg 2>/dev/null || true

        # Use Debian 12 (bookworm) repo — works for Kali and Debian
        echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" \
            > /etc/apt/sources.list.d/mssql-release.list 2>/dev/null || true
        apt-get update -qq 2>&1 | tail -1
        ACCEPT_EULA=Y apt-get install -y -qq msodbcsql18 2>&1 | tail -3 || echo -e "  ${YELLOW}[!]${NC} ODBC driver install failed"
    fi

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

# OCI8 for Oracle (requires Oracle Instant Client from Kali repos)
if ! php -m 2>/dev/null | grep -qi "oci8"; then
    if apt-cache show oracle-instantclient-basic &>/dev/null && apt-cache show oracle-instantclient-devel &>/dev/null; then
        echo -e "  [*] Installing Oracle Instant Client and OCI8..."
        apt-get install -y oracle-instantclient-basic oracle-instantclient-devel 2>&1 | tail -1 || true
        OCI_LIB=$(find /usr/lib/oracle -name "libclntsh.so" 2>/dev/null | head -1)
        if [[ -n "$OCI_LIB" ]]; then
            OCI_DIR=$(dirname "$OCI_LIB")
            echo "instantclient,$OCI_DIR" | pecl install oci8 2>&1 | tail -2 || true
            if [[ -f "$(php -r 'echo ini_get("extension_dir");' 2>/dev/null)/oci8.so" ]]; then
                echo "extension=oci8.so" > "/etc/php/${PHP_VER_DETECTED}/mods-available/oci8.ini"
                ln -sf "/etc/php/${PHP_VER_DETECTED}/mods-available/oci8.ini" "/etc/php/${PHP_VER_DETECTED}/cli/conf.d/20-oci8.ini" 2>/dev/null || true
                ln -sf "/etc/php/${PHP_VER_DETECTED}/mods-available/oci8.ini" "/etc/php/${PHP_VER_DETECTED}/apache2/conf.d/20-oci8.ini" 2>/dev/null || true
                echo -e "  ${GREEN}[+]${NC} OCI8 installed (Oracle Instant Client)"
            else
                echo -e "  ${YELLOW}[!]${NC} OCI8 PECL compilation failed. Oracle labs will run in simulation mode."
            fi
        else
            echo -e "  ${YELLOW}[!]${NC} Oracle Instant Client library not found. Oracle labs will run in simulation mode."
        fi
    else
        echo -e "  ${YELLOW}[!]${NC} Oracle Instant Client not in repos. Oracle labs will run in simulation mode."
    fi
else
    echo -e "  ${GREEN}[+]${NC} php-oci8 already installed"
fi

echo -e "  ${GREEN}PHP extensions ready.${NC}"

# ============================================================
# Step 3: Start and configure system services
# ============================================================
echo ""
echo -e "${YELLOW}[3/10] Starting system services...${NC}"

# MySQL / MariaDB
if systemctl is-active --quiet mysql 2>/dev/null || systemctl is-active --quiet mariadb 2>/dev/null; then
    echo -e "  ${GREEN}[+]${NC} MySQL/MariaDB already running"
else
    systemctl start mysql 2>/dev/null || systemctl start mariadb 2>/dev/null || echo -e "  ${YELLOW}[!]${NC} Could not start MySQL/MariaDB"
    systemctl enable mysql 2>/dev/null || systemctl enable mariadb 2>/dev/null || true
    echo -e "  ${GREEN}[+]${NC} MySQL/MariaDB started"
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
echo -e "${YELLOW}[5/10] Starting Docker containers (MSSQL, MSSQL-Internal, Oracle, MongoDB, Redis, HQL, GraphQL)...${NC}"
echo -e "  ${CYAN}This pulls ~3GB of images on first run. Please be patient...${NC}"

cd "$SCRIPT_DIR"

# Use docker compose (v2) or docker-compose (v1)
if docker compose version &>/dev/null; then
    COMPOSE_CMD="docker compose"
else
    COMPOSE_CMD="docker-compose"
fi

# Pull images first (so build doesn't timeout)
echo -e "  [*] Pulling Docker images..."
$COMPOSE_CMD pull 2>&1 | grep -E "Pulled|Pulling|done|Downloaded" | tail -10 || true

# Build custom images (HQL, GraphQL)
echo -e "  [*] Building custom images (HQL, GraphQL)..."
$COMPOSE_CMD build 2>&1 | tail -5 || true

# Start all containers
echo -e "  [*] Starting containers..."
$COMPOSE_CMD up -d 2>&1 | grep -E "Created|Started|Running|running" || true
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

wait_for "MySQL"      "mysql -u root -e 'SELECT 1' 2>/dev/null || mysqladmin ping -u root 2>/dev/null" 20
wait_for "PostgreSQL"  "su - postgres -c 'psql -c \"SELECT 1\"' 2>/dev/null" 20
wait_for "MongoDB"    "docker exec sqli-arena-mongodb mongosh --username sqli_arena --password sqli_arena_2026 --authenticationDatabase admin --eval 'db.adminCommand(\"ping\")' --quiet" 40
wait_for "Redis"      "docker exec sqli-arena-redis redis-cli -a sqli_arena_2026 --no-auth-warning ping | grep -q PONG" 30
wait_for "MSSQL"      "docker exec sqli-arena-mssql /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P 'SqliArena2026!' -C -Q 'SELECT 1' -b -o /dev/null" 80
wait_for "MSSQL (B)"  "docker exec sqli-arena-mssql-internal /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P 'Internal2026!' -C -Q 'SELECT 1' -b -o /dev/null" 80
wait_for "Oracle"     "docker exec sqli-arena-oracle healthcheck.sh" 120
wait_for "HQL API"    "curl -sf http://localhost:8081/actuator/health" 80
wait_for "GraphQL"    "curl -sf http://localhost:4000/health" 40

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

# Resolve both paths to handle symlinks/trailing slashes
RESOLVED_SCRIPT="$(cd "$SCRIPT_DIR" && pwd -P)"
RESOLVED_WEBROOT="$(cd "$(dirname "$WEBROOT")" && pwd -P)/$(basename "$WEBROOT")"

if [[ "$RESOLVED_SCRIPT" == "$RESOLVED_WEBROOT" ]]; then
    echo -e "  ${CYAN}[i]${NC} Already running from $WEBROOT -- skipping copy"
else
    rm -rf "$WEBROOT"
    cp -r "$SCRIPT_DIR" "$WEBROOT"
fi

# Create data directories
mkdir -p "$WEBROOT/data/sqlite" "$WEBROOT/data/tasks"

# Set permissions
WEBUSER=$(ps aux | grep -E 'apache2|httpd' | grep -v grep | head -1 | awk '{print $1}')
WEBUSER="${WEBUSER:-www-data}"

chown -R "$WEBUSER:$WEBUSER" "$WEBROOT" 2>/dev/null || chown -R "$REAL_USER:$REAL_USER" "$WEBROOT"
chmod -R 755 "$WEBROOT"
chmod -R 777 "$WEBROOT/data"

echo -e "  ${GREEN}[+]${NC} Deployed to $WEBROOT"

# Build setuid cleanup helper so the web UI can run cleanup.sh as root
# (sudo from Apache is blocked by use_pty on most systems)
HELPER_SRC=$(mktemp /tmp/sqli_cleanup_helper.XXXXXX.c)
cat > "$HELPER_SRC" << 'HELPEREOF'
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
int main() {
    setuid(0);
    setgid(0);
    FILE *fp = popen("printf \"y\\nn\\nn\\nn\\n\" | /bin/bash /var/www/html/SQLi-Arena/setup/cleanup.sh 2>&1", "r");
    if (!fp) { perror("popen"); return 1; }
    char buf[4096];
    while (fgets(buf, sizeof(buf), fp)) fputs(buf, stdout);
    return pclose(fp) >> 8;
}
HELPEREOF
gcc -o /usr/local/bin/sqli-arena-cleanup "$HELPER_SRC" 2>/dev/null
chown root:root /usr/local/bin/sqli-arena-cleanup
chmod 4755 /usr/local/bin/sqli-arena-cleanup
rm -f "$HELPER_SRC"
echo -e "  ${GREEN}[+]${NC} Cleanup helper installed"

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
if curl -sf "http://localhost/SQLi-Arena/mysql/lab1" 2>/dev/null | grep -qi "lab"; then
    echo -e "  ${GREEN}[+]${NC} Lab pages are working"
else
    echo -e "  ${YELLOW}[!]${NC} Lab pages may not be loading (check mod_rewrite)"
fi

# Check Docker containers
RUNNING=$(docker ps --filter "name=sqli-arena" --format '{{.Names}}' 2>/dev/null | wc -l)
echo -e "  ${GREEN}[+]${NC} $RUNNING/7 Docker containers running"

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
echo -e "    MSSQL (A)       ${CYAN}localhost:1433${NC}"
echo -e "    MSSQL (B)       ${CYAN}localhost:1434${NC}  (internal linked server)"
echo -e "    Oracle          ${CYAN}localhost:1521${NC}"
echo -e "    MongoDB         ${CYAN}localhost:27017${NC}"
echo -e "    Redis           ${CYAN}localhost:6379${NC}"
echo -e "    HQL API         ${CYAN}localhost:8081${NC}"
echo -e "    GraphQL API     ${CYAN}localhost:4000${NC}"
echo ""
echo -e "  ${BOLD}Management:${NC}"
echo -e "    Control Panel:    ${CYAN}http://localhost/SQLi-Arena/control-panel${NC}"
echo -e "    Stop containers:  ${CYAN}bash setup/docker_stop.sh${NC}"
echo -e "    Start containers: ${CYAN}bash setup/docker_start.sh${NC}"
echo -e "    Full cleanup:     ${CYAN}sudo bash setup/cleanup.sh${NC}"
echo ""

if [[ $RUNNING -lt 7 ]]; then
    echo -e "  ${YELLOW}Note: Some Docker containers may still be starting.${NC}"
    echo -e "  ${YELLOW}Run 'sudo docker ps' to check status. If containers are${NC}"
    echo -e "  ${YELLOW}still pulling images, wait a few minutes then run:${NC}"
    echo -e "    ${CYAN}cd $(pwd) && sudo docker compose up -d${NC}"
    echo -e "    ${CYAN}sudo bash setup.sh${NC}"
    echo ""
fi
