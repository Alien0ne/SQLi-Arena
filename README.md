<p align="center">
  <img src="screenshots/homepage-dark.png" alt="SQLi-Arena" width="100%">
</p>

# SQLi-Arena

A self-hosted SQL injection training platform with **108 labs** across **10 database engines**. Practice everything from basic UNION injection to blind extraction, WAF bypasses, NoSQL operator injection, and second-order attacks.

Built for pentesters, bug bounty hunters, and security students who want hands-on practice against real databases — not simulations.

## Supported Databases

| Engine | Labs | Type | Port |
|--------|------|------|------|
| MySQL 8.0+ | 20 | Native | 3306 |
| PostgreSQL 16+ | 15 | Native | 5432 |
| SQLite 3.x | 10 | Native | file |
| MariaDB 11+ | 8 | Native | 3306 |
| MS SQL Server 2022 | 18 | Docker | 1433 |
| Oracle 21c | 14 | Docker | 1521 |
| MongoDB 7+ | 8 | Docker | 27017 |
| Redis 7+ | 5 | Docker | 6379 |
| HQL (Hibernate 6+) | 5 | Docker | 8081 |
| GraphQL | 5 | Docker | 4000 |

## Screenshots

<p align="center">
  <img src="screenshots/labs-mysql-dark.png" alt="Lab Listing" width="48%">
  <img src="screenshots/lab-blackbox-dark.png" alt="Black-box Lab" width="48%">
</p>
<p align="center">
  <img src="screenshots/lab-whitebox-dark.png" alt="White-box Lab" width="48%">
  <img src="screenshots/lab-solution-dark.png" alt="Solution Walkthrough" width="48%">
</p>
<p align="center">
  <img src="screenshots/learning-path-dark.png" alt="Learning Path" width="48%">
  <img src="screenshots/learning-path-inside-dark.png" alt="Learning Path Phase" width="48%">
</p>
<p align="center">
  <img src="screenshots/attack-types-dark.png" alt="Attack Types" width="48%">
  <img src="screenshots/cheatsheet-dark.png" alt="Cheatsheet" width="48%">
</p>
<p align="center">
  <img src="screenshots/control-panel-dark.png" alt="Control Panel" width="48%">
</p>

## Features

- **108 labs** across 10 database engines with real vulnerable queries
- **Black-box and white-box modes** for each lab
- **Solution walkthroughs** with step-by-step exploitation guides
- **14-phase learning path** from first injection to RCE
- **Attack types reference** with theory, payloads, and linked labs
- **Built-in cheatsheet** with payloads for all engines
- **Progress tracking** with per-lab solved state
- **Individual lab reset** to restore databases to default
- **Dark and light themes**
- **Burp Suite integration** via `sqli-arena.local` hostname
- **Control panel** with engine status, one-click reset, and log terminal
- **Clean URLs** (`/mysql/lab1`, `/learning-path`, `/attack-types/union`)
- **Docker Compose** for containerized engines (MSSQL, Oracle, MongoDB, Redis, HQL, GraphQL)

## Quick Start

```bash
git clone https://github.com/Alien0ne/SQLi-Arena.git
cd SQLi-Arena
sudo bash install.sh
```

The installer handles everything: system packages, PHP extensions (including OCI8 and SQLSRV), Apache configuration, Docker containers, database initialization across all 10 engines, and web deployment.

Once complete:

```
http://localhost/SQLi-Arena/
http://sqli-arena.local/SQLi-Arena/   # For Burp Suite proxy capture
```

## Requirements

- **OS:** Kali Linux, Ubuntu 22.04+, or Debian 12+
- **RAM:** 4 GB minimum, 8 GB recommended
- **Disk:** 5 GB free (Docker images)
- **Ports:** 3306, 5432, 1433, 1434, 1521, 27017, 6379, 4000, 8081

> All software dependencies (Apache, PHP, Docker, database clients) are installed automatically by the install script.

## Services

| Service | Host | Port |
|---------|------|------|
| MySQL / MariaDB | localhost | 3306 |
| PostgreSQL | localhost | 5432 |
| MSSQL (primary) | localhost | 1433 |
| MSSQL (linked server) | localhost | 1434 |
| Oracle XE | localhost | 1521 |
| MongoDB | localhost | 27017 |
| Redis | localhost | 6379 |
| HQL API | localhost | 8081 |
| GraphQL API | localhost | 4000 |

## Management

```bash
sudo bash setup.sh                # Re-initialize all databases
bash setup/docker_start.sh        # Start Docker containers
bash setup/docker_stop.sh         # Stop Docker containers
sudo bash setup/cleanup.sh        # Full cleanup (removes everything)
```

Or use the **Control Panel** at `http://localhost/SQLi-Arena/control-panel`.

## Burp Suite Integration

The installer adds `sqli-arena.local` to `/etc/hosts`. Browsers bypass proxy for `localhost`, so use this hostname to capture all traffic in Burp:

1. Set Burp proxy listener on `127.0.0.1:8080`
2. Configure browser proxy to `127.0.0.1:8080`
3. Browse to `http://sqli-arena.local/SQLi-Arena/`

## Project Structure

```
SQLi-Arena/
├── public/              # Web root
│   ├── index.php        # Homepage
│   ├── lab.php          # Lab runner (black/white/solution)
│   ├── labs.php         # Lab listing per engine
│   ├── learning-path.php
│   ├── attack-types.php
│   ├── cheatsheet.php
│   ├── control-panel.php
│   └── assets/          # CSS, JS
├── labs/                # 10 engine subdirectories
├── includes/            # Config, header, footer, helpers
├── setup/               # DB init scripts, Docker scripts, cleanup
├── docker/              # Dockerfiles for HQL & GraphQL
├── docker-compose.yml
├── install.sh           # End-to-end installer
└── setup.sh             # Database re-initialization
```

## Contributing

1. Fork the repo
2. Create a branch (`git checkout -b feature/new-lab`)
3. Add lab files in `labs/{engine}/` and init scripts in `setup/`
4. Test both black-box and white-box modes
5. Submit a PR

## Inspiration

Inspired by [sqli-labs](https://github.com/Audi-1/sqli-labs) by Audi-1 — the original MySQL-based SQL injection learning platform.

## Disclaimer

This repository is for **educational and authorized testing purposes only**.
Use only in labs or environments you are explicitly permitted to assess.

## Legal Disclaimer

THIS SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED.
IN NO EVENT SHALL THE AUTHORS OR CONTRIBUTORS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY ARISING FROM THE USE OF THIS REPOSITORY.

## Author

- [LinkedIn](https://www.linkedin.com/in/narasimhatiruveedula/)
- [TryHackMe (AlienOne)](https://tryhackme.com/p/AlienOne)
- [GitHub (Alien0ne)](https://github.com/Alien0ne)
- [Website](https://www.alienone.in/)

## License

MIT License — see [LICENSE](LICENSE).
