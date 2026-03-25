#!/bin/bash
# SQLi-Arena -- Stop all Docker containers
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo "[*] Stopping all SQLi-Arena containers..."
cd "$PROJECT_DIR"
docker compose down

echo "[+] All containers stopped."
echo "    Data volumes preserved. Use 'docker compose down -v' to remove data."
