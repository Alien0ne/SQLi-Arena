#!/bin/bash
# ============================================================
# SQLi-Arena -- Master Setup Script
# Sets up all 10 database engines, deploys to web root,
# and configures networking for Burp Suite proxy capture.
#
# Usage: sudo bash setup.sh
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
echo -e "${CYAN}${BOLD}================================================${NC}"
echo -e "${CYAN}${BOLD}  SQLi-Arena -- Master Setup${NC}"
echo -e "${CYAN}${BOLD}================================================${NC}"
echo ""

# ----------------------------------------
# 0. Root check
# ----------------------------------------
if [[ $EUID -ne 0 ]]; then
    echo -e "${RED}[!] Please run as root: sudo bash setup.sh${NC}"
    exit 1
fi

# ----------------------------------------
# 1. Check prerequisites
# ----------------------------------------
echo -e "${YELLOW}[1/7] Checking prerequisites...${NC}"

MISSING=()
command -v apache2 &>/dev/null || command -v httpd &>/dev/null || MISSING+=("apache2")
command -v php &>/dev/null || MISSING+=("php")
command -v mysql &>/dev/null || MISSING+=("mysql-client")
command -v psql &>/dev/null || MISSING+=("postgresql-client")
command -v sqlite3 &>/dev/null || MISSING+=("sqlite3")
command -v docker &>/dev/null || MISSING+=("docker")
command -v curl &>/dev/null || MISSING+=("curl")

if [[ ${#MISSING[@]} -gt 0 ]]; then
    echo -e "${RED}[!] Missing required tools: ${MISSING[*]}${NC}"
    echo "    Install them first, then re-run this script."
    exit 1
fi

# Check PHP extensions
php -m 2>/dev/null | grep -qi mysqli || MISSING+=("php-mysqli")
php -m 2>/dev/null | grep -qi pgsql || MISSING+=("php-pgsql")
php -m 2>/dev/null | grep -qi sqlite3 || MISSING+=("php-sqlite3")

if [[ ${#MISSING[@]} -gt 0 ]]; then
    echo -e "${RED}[!] Missing PHP extensions: ${MISSING[*]}${NC}"
    echo "    Install with: sudo apt install ${MISSING[*]}"
    exit 1
fi

echo -e "      ${GREEN}All prerequisites met.${NC}"

# ----------------------------------------
# 2. Set up local databases (MySQL, PostgreSQL, SQLite)
# ----------------------------------------
echo ""
echo -e "${YELLOW}[2/7] Setting up local databases...${NC}"

echo -e "      ${CYAN}[MySQL]${NC}"
bash "$SETUP_DIR/setup_mysql.sh"

echo -e "      ${CYAN}[PostgreSQL]${NC}"
bash "$SETUP_DIR/setup_pgsql.sh"

echo -e "      ${CYAN}[SQLite]${NC}"
bash "$SETUP_DIR/setup_sqlite.sh"

echo -e "      ${CYAN}[MariaDB]${NC}"
bash "$SETUP_DIR/setup_mariadb.sh"

echo -e "      ${GREEN}Local databases ready.${NC}"

# ----------------------------------------
# 3. Start Docker containers
# ----------------------------------------
echo ""
echo -e "${YELLOW}[3/7] Starting Docker containers (MSSQL, MSSQL-Internal, Oracle, MongoDB, Redis, HQL, GraphQL)...${NC}"

bash "$SETUP_DIR/docker_start.sh"

# ----------------------------------------
# 4. Set up containerized databases
# ----------------------------------------
echo ""
echo -e "${YELLOW}[4/7] Initializing containerized databases...${NC}"

echo -e "      ${CYAN}[MSSQL]${NC}"
bash "$SETUP_DIR/setup_mssql.sh"

echo -e "      ${CYAN}[Oracle]${NC}"
bash "$SETUP_DIR/setup_oracle.sh"

echo -e "      ${CYAN}[MongoDB]${NC}"
bash "$SETUP_DIR/setup_mongodb.sh"

echo -e "      ${CYAN}[Redis]${NC}"
bash "$SETUP_DIR/setup_redis.sh"

echo -e "      ${GREEN}Containerized databases ready.${NC}"

# ----------------------------------------
# 5. Deploy to web root
# ----------------------------------------
echo ""
echo -e "${YELLOW}[5/7] Deploying to web root...${NC}"

rm -rf "$WEBROOT"
cp -r "$SCRIPT_DIR" "$WEBROOT"

# Set permissions
WEBUSER=$(ps aux | grep -E 'apache2|httpd' | grep -v grep | head -1 | awk '{print $1}')
if [[ -z "$WEBUSER" ]]; then
    WEBUSER="www-data"
fi

chown -R "$WEBUSER:$WEBUSER" "$WEBROOT" 2>/dev/null || chown -R kali:kali "$WEBROOT"
chmod -R 755 "$WEBROOT"
chmod -R 777 "$WEBROOT/data" 2>/dev/null || true

# Enable Apache mod_rewrite if not already enabled
if command -v a2enmod &>/dev/null; then
    a2enmod rewrite &>/dev/null || true

    # Ensure AllowOverride is set for /var/www/html
    APACHE_CONF="/etc/apache2/apache2.conf"
    if [[ -f "$APACHE_CONF" ]] && ! grep -q 'AllowOverride All' "$APACHE_CONF" 2>/dev/null; then
        # Check the /var/www/html directory block
        if grep -A5 '<Directory /var/www/>' "$APACHE_CONF" | grep -q 'AllowOverride None'; then
            sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' "$APACHE_CONF"
            echo "      Updated Apache AllowOverride to All"
        fi
    fi

    systemctl restart apache2 2>/dev/null || service apache2 restart 2>/dev/null || true
fi

echo -e "      ${GREEN}Deployed to $WEBROOT${NC}"

# ----------------------------------------
# 6. Add hosts entry for Burp Suite proxy
# ----------------------------------------
echo ""
echo -e "${YELLOW}[6/7] Configuring hostname for Burp Suite proxy...${NC}"

if grep -q "$HOSTNAME_ALIAS" /etc/hosts 2>/dev/null; then
    echo -e "      ${GREEN}$HOSTNAME_ALIAS already in /etc/hosts${NC}"
else
    echo "127.0.0.1 $HOSTNAME_ALIAS" >> /etc/hosts
    echo -e "      ${GREEN}Added $HOSTNAME_ALIAS to /etc/hosts${NC}"
fi

# ----------------------------------------
# 7. Verify deployment
# ----------------------------------------
echo ""
echo -e "${YELLOW}[7/7] Verifying deployment...${NC}"

# Check Apache is running
if curl -sf -o /dev/null "http://localhost/SQLi-Arena/" 2>/dev/null; then
    echo -e "      ${GREEN}Web application is accessible!${NC}"
else
    echo -e "      ${YELLOW}Warning: Could not reach http://localhost/SQLi-Arena/${NC}"
    echo "      Make sure Apache is running: sudo systemctl start apache2"
fi

# ----------------------------------------
# Done!
# ----------------------------------------
echo ""
echo -e "${CYAN}${BOLD}================================================${NC}"
echo -e "${GREEN}${BOLD}  SQLi-Arena Setup Complete!${NC}"
echo -e "${CYAN}${BOLD}================================================${NC}"
echo ""
echo -e "  ${BOLD}Access the lab:${NC}"
echo ""
echo -e "    ${CYAN}http://$HOSTNAME_ALIAS/SQLi-Arena/${NC}"
echo ""
echo -e "  ${BOLD}Why use ${CYAN}$HOSTNAME_ALIAS${NC} instead of localhost?${NC}"
echo -e "  Browsers bypass the proxy for localhost/127.0.0.1."
echo -e "  Using ${CYAN}$HOSTNAME_ALIAS${NC} ensures Burp Suite captures all traffic."
echo ""
echo -e "  ${BOLD}Burp Suite setup:${NC}"
echo -e "    1. Proxy listener on ${CYAN}127.0.0.1:8080${NC}"
echo -e "    2. Browser proxy set to ${CYAN}127.0.0.1:8080${NC}"
echo -e "    3. Browse to ${CYAN}http://$HOSTNAME_ALIAS/SQLi-Arena/${NC}"
echo ""
echo -e "  ${BOLD}Also accessible at:${NC}"
echo -e "    http://localhost/SQLi-Arena/"
LOCAL_IP=$(hostname -I 2>/dev/null | awk '{print $1}')
if [[ -n "$LOCAL_IP" ]]; then
    echo -e "    http://$LOCAL_IP/SQLi-Arena/"
fi
echo ""
echo -e "  ${BOLD}Management:${NC}"
echo -e "    Stop containers:  bash setup/docker_stop.sh"
echo -e "    Reset databases:  visit Admin page in web UI"
echo -e "    Full cleanup:     sudo bash setup/cleanup.sh"
echo ""
