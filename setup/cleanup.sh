#!/bin/bash
# ============================================================
# SQLi-Arena -- Full Cleanup Script
# Reverses everything install.sh does.
# Removes databases, users, Docker containers/volumes/images,
# web deployment, hosts entry, and optionally system packages.
#
# Usage: sudo bash setup/cleanup.sh
# ============================================================

# Don't use set -e — cleanup steps should continue even if individual ones fail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

WEBROOT="/var/www/html/SQLi-Arena"
HOSTNAME_ALIAS="sqli-arena.local"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ARENA_DIR="$(dirname "$SCRIPT_DIR")"

echo ""
echo -e "${RED}${BOLD}  ╔═══════════════════════════════════════════╗${NC}"
echo -e "${RED}${BOLD}  ║        SQLi-Arena  Full Cleanup           ║${NC}"
echo -e "${RED}${BOLD}  ╚═══════════════════════════════════════════╝${NC}"
echo ""
echo -e "  This will remove ALL SQLi-Arena data, databases,"
echo -e "  Docker containers, and web deployment."
echo ""

if [[ $EUID -ne 0 ]]; then
    echo -e "${RED}[!] Please run as root: sudo bash setup/cleanup.sh${NC}"
    exit 1
fi

read -p "  Are you sure you want to proceed? [y/N] " confirm
if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
    echo -e "\n  ${YELLOW}Cleanup cancelled.${NC}"
    exit 0
fi

echo ""

# ============================================================
# 1. Stop and remove Docker containers
# ============================================================
echo -e "${YELLOW}[1/8] Stopping Docker containers...${NC}"

if command -v docker &>/dev/null; then
    # Try docker compose first
    if [[ -f "$ARENA_DIR/docker-compose.yml" ]]; then
        cd "$ARENA_DIR"
        if docker compose version &>/dev/null; then
            docker compose down -v 2>&1 | grep -v "^$" || true
        elif command -v docker-compose &>/dev/null; then
            docker-compose down -v 2>&1 | grep -v "^$" || true
        fi
        echo -e "  ${GREEN}[+]${NC} Docker Compose services stopped and volumes removed"
    fi

    # Also catch any orphaned containers
    for container in sqli-arena-mssql sqli-arena-mssql-internal sqli-arena-oracle sqli-arena-mongodb sqli-arena-redis sqli-arena-hql sqli-arena-graphql; do
        if docker ps -a --format '{{.Names}}' | grep -q "^${container}$"; then
            docker rm -f "$container" 2>/dev/null || true
            echo -e "  ${GREEN}[+]${NC} Removed container: $container"
        fi
    done
else
    echo -e "  ${YELLOW}[!]${NC} Docker not installed, skipping"
fi

# ============================================================
# 2. Remove Docker volumes
# ============================================================
echo ""
echo -e "${YELLOW}[2/8] Removing Docker volumes...${NC}"

if command -v docker &>/dev/null; then
    for vol in $(docker volume ls --format '{{.Name}}' 2>/dev/null | grep -E "sqli-arena|mssql-data|mssql-internal-data|oracle-data|mongodb-data|redis-data"); do
        docker volume rm "$vol" 2>/dev/null || true
        echo -e "  ${GREEN}[+]${NC} Removed volume: $vol"
    done

    # Also remove project-prefixed volumes (docker compose names them with project prefix)
    PROJECT_NAME=$(basename "$ARENA_DIR" | tr '[:upper:]' '[:lower:]' | tr -cd '[:alnum:]_-')
    for vol in $(docker volume ls --format '{{.Name}}' 2>/dev/null | grep "^${PROJECT_NAME}"); do
        docker volume rm "$vol" 2>/dev/null || true
        echo -e "  ${GREEN}[+]${NC} Removed volume: $vol"
    done

    echo -e "  ${GREEN}Docker volumes cleaned.${NC}"
else
    echo -e "  ${YELLOW}[!]${NC} Docker not installed, skipping"
fi

# ============================================================
# 3. Remove Docker images
# ============================================================
echo ""
echo -e "${YELLOW}[3/8] Removing Docker images...${NC}"

if command -v docker &>/dev/null; then
    read -p "  Remove Docker images (MSSQL, Oracle, MongoDB, Redis)? [y/N] " rm_images
    if [[ "$rm_images" =~ ^[Yy]$ ]]; then
        for img in "mcr.microsoft.com/mssql/server:2022-latest" "gvenzl/oracle-xe:21-slim" "mongo:7" "redis:7-alpine"; do
            docker rmi "$img" 2>/dev/null && echo -e "  ${GREEN}[+]${NC} Removed image: $img" || true
        done
        # Remove locally built images (HQL, GraphQL)
        for img in $(docker images --format '{{.Repository}}:{{.Tag}}' 2>/dev/null | grep -i "sqli-arena"); do
            docker rmi "$img" 2>/dev/null && echo -e "  ${GREEN}[+]${NC} Removed image: $img" || true
        done
        echo -e "  ${GREEN}Docker images removed.${NC}"
    else
        echo -e "  Kept Docker images."
    fi
else
    echo -e "  ${YELLOW}[!]${NC} Docker not installed, skipping"
fi

# ============================================================
# 4. Drop MySQL/MariaDB databases and users
# ============================================================
echo ""
echo -e "${YELLOW}[4/8] Cleaning MySQL/MariaDB...${NC}"

if command -v mysql &>/dev/null; then
    # Drop all sqli_arena_* databases
    MYSQL_DBS=$(mysql -N -e "SHOW DATABASES LIKE 'sqli_arena_%';" 2>/dev/null || true)
    if [[ -n "$MYSQL_DBS" ]]; then
        for db in $MYSQL_DBS; do
            mysql -e "DROP DATABASE IF EXISTS \`$db\`;" 2>/dev/null || true
            echo -e "  ${GREEN}[+]${NC} Dropped: $db"
        done
    else
        echo -e "  No sqli_arena_* databases found."
    fi

    # Drop user
    mysql -e "DROP USER IF EXISTS 'sqli_arena'@'localhost'; FLUSH PRIVILEGES;" 2>/dev/null || true
    echo -e "  ${GREEN}[+]${NC} Removed MySQL user: sqli_arena@localhost"
else
    echo -e "  ${YELLOW}[!]${NC} MySQL client not found, skipping"
fi

# ============================================================
# 5. Drop PostgreSQL databases and user
# ============================================================
echo ""
echo -e "${YELLOW}[5/8] Cleaning PostgreSQL...${NC}"

if command -v psql &>/dev/null; then
    # Use su instead of sudo to avoid use_pty issues when called from web UI
    PG_DBS=$(su -s /bin/sh postgres -c "psql -t -A -c \"SELECT datname FROM pg_database WHERE datname LIKE 'sqli_arena_%';\"" 2>/dev/null || true)
    if [[ -n "$PG_DBS" ]]; then
        for db in $PG_DBS; do
            su -s /bin/sh postgres -c "psql -c \"DROP DATABASE IF EXISTS \\\"$db\\\";\"" 2>/dev/null || true
            echo -e "  ${GREEN}[+]${NC} Dropped: $db"
        done
    else
        echo -e "  No sqli_arena_* PostgreSQL databases found."
    fi

    su -s /bin/sh postgres -c "psql -c \"DROP USER IF EXISTS sqli_arena;\"" 2>/dev/null || true
    echo -e "  ${GREEN}[+]${NC} Removed PostgreSQL user: sqli_arena"
else
    echo -e "  ${YELLOW}[!]${NC} psql not found, skipping"
fi

# ============================================================
# 6. Remove SQLite files and data directory
# ============================================================
echo ""
echo -e "${YELLOW}[6/8] Removing SQLite and data files...${NC}"

for dir in "$ARENA_DIR/data" "$WEBROOT/data"; do
    if [[ -d "$dir" ]]; then
        rm -rf "$dir"
        echo -e "  ${GREEN}[+]${NC} Removed: $dir"
    fi
done

# ============================================================
# 7. Remove web deployment and hosts entry
# ============================================================
echo ""
echo -e "${YELLOW}[7/8] Removing web deployment...${NC}"

# Remove hosts entry
if grep -q "sqli-arena" /etc/hosts 2>/dev/null; then
    if sed -i '/sqli-arena/d' /etc/hosts 2>/dev/null; then
        echo -e "  ${GREEN}[+]${NC} Removed $HOSTNAME_ALIAS from /etc/hosts"
    else
        echo -e "  ${YELLOW}[!]${NC} Could not modify /etc/hosts (read-only filesystem?)"
    fi
fi

# Remove setuid cleanup helper
if [[ -f /usr/local/bin/sqli-arena-cleanup ]]; then
    if rm -f /usr/local/bin/sqli-arena-cleanup 2>/dev/null; then
        echo -e "  ${GREEN}[+]${NC} Removed setuid cleanup helper"
    else
        echo -e "  ${YELLOW}[!]${NC} Could not remove /usr/local/bin/sqli-arena-cleanup (read-only filesystem?)"
    fi
fi

# Remove sudoers rule
if [[ -f /etc/sudoers.d/sqli-arena-cleanup ]]; then
    if rm -f /etc/sudoers.d/sqli-arena-cleanup 2>/dev/null; then
        echo -e "  ${GREEN}[+]${NC} Removed sudoers rule"
    else
        echo -e "  ${YELLOW}[!]${NC} Could not remove sudoers rule (read-only filesystem?)"
    fi
fi

# Revert Apache AllowOverride to default
APACHE_CONF="/etc/apache2/apache2.conf"
if [[ -f "$APACHE_CONF" ]]; then
    if grep -A5 '<Directory /var/www/>' "$APACHE_CONF" | grep -q 'AllowOverride All'; then
        if sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride All/AllowOverride None/' "$APACHE_CONF" 2>/dev/null; then
            # Do NOT restart Apache here — when called from the web UI, restarting
            # kills the PHP process before it can send the cleanup response.
            echo -e "  ${GREEN}[+]${NC} Reverted Apache AllowOverride to None (restart Apache manually to apply)"
        else
            echo -e "  ${YELLOW}[!]${NC} Could not modify Apache config (read-only filesystem?)"
        fi
    fi
fi

# Remove webroot LAST — this script lives inside $WEBROOT, so deleting it
# earlier kills the script mid-execution when called via popen() from the
# setuid helper binary
if [[ -d "$WEBROOT" ]]; then
    rm -rf "$WEBROOT"
    echo -e "  ${GREEN}[+]${NC} Removed: $WEBROOT"
else
    echo -e "  $WEBROOT not found, skipping."
fi

# ============================================================
# 8. Optionally uninstall system packages
# ============================================================
echo ""
echo -e "${YELLOW}[8/8] Uninstall system packages?${NC}"
echo ""
echo -e "  ${YELLOW}Warning:${NC} This removes MySQL, PostgreSQL, and Docker."
echo -e "  Only do this if no other applications depend on them."
echo ""

read -p "  Uninstall system packages installed by SQLi-Arena? [y/N] " rm_packages
if [[ "$rm_packages" =~ ^[Yy]$ ]]; then
    echo -e "  [*] Removing packages..."

    # Stop services first
    systemctl stop mysql 2>/dev/null || true
    systemctl stop postgresql 2>/dev/null || true

    # Determine PHP version
    PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null || echo "8.2")

    PACKAGES_TO_REMOVE=(
        "php${PHP_VER}-mysql"
        "php${PHP_VER}-pgsql"
        "php${PHP_VER}-sqlite3"
        "php${PHP_VER}-dev"
        php-pear
    )

    # Only remove database servers if user confirms
    read -p "  Also remove database servers (MySQL, PostgreSQL)? [y/N] " rm_dbs
    if [[ "$rm_dbs" =~ ^[Yy]$ ]]; then
        PACKAGES_TO_REMOVE+=(mysql-server postgresql)
    fi

    read -p "  Also remove Docker? [y/N] " rm_docker
    if [[ "$rm_docker" =~ ^[Yy]$ ]]; then
        PACKAGES_TO_REMOVE+=(docker.io)
    fi

    apt-get remove -y "${PACKAGES_TO_REMOVE[@]}" 2>&1 | tail -3 || true
    apt-get autoremove -y 2>&1 | tail -1 || true

    # Remove PECL extensions
    pecl uninstall mongodb 2>/dev/null || true
    pecl uninstall redis 2>/dev/null || true
    pecl uninstall sqlsrv 2>/dev/null || true
    pecl uninstall pdo_sqlsrv 2>/dev/null || true

    # Remove PECL ini files
    find /etc/php/ -name "30-mongodb.ini" -delete 2>/dev/null || true
    find /etc/php/ -name "30-redis.ini" -delete 2>/dev/null || true
    find /etc/php/ -name "30-sqlsrv.ini" -delete 2>/dev/null || true
    find /etc/php/ -name "30-pdo_sqlsrv.ini" -delete 2>/dev/null || true

    echo -e "  ${GREEN}[+]${NC} Packages removed"
else
    echo -e "  Kept system packages."
fi

# ============================================================
# Done
# ============================================================
echo ""
echo -e "${GREEN}${BOLD}  ╔═══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}${BOLD}  ║       SQLi-Arena Cleanup Complete         ║${NC}"
echo -e "${GREEN}${BOLD}  ╚═══════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${BOLD}What was removed:${NC}"
echo -e "    - All sqli_arena_* MySQL databases and user"
echo -e "    - All sqli_arena_* PostgreSQL databases and user"
echo -e "    - All SQLite data files"
echo -e "    - Docker containers, volumes (and images if selected)"
echo -e "    - Web deployment at $WEBROOT"
echo -e "    - Hostname entry ($HOSTNAME_ALIAS)"
if [[ "$rm_packages" =~ ^[Yy]$ ]]; then
echo -e "    - System packages (as selected above)"
fi
echo ""
echo -e "  ${BOLD}To reinstall:${NC}"
echo -e "    cd SQLi-Arena && sudo bash install.sh"
echo ""
