-- =========================
-- SQLi-Arena: PostgreSQL Lab 11
-- OOB -- dblink + DNS Exfiltration
-- =========================
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab11;
CREATE DATABASE sqli_arena_pgsql_lab11;
\c sqli_arena_pgsql_lab11

CREATE TABLE inventory (
    id SERIAL PRIMARY KEY,
    item_name VARCHAR(200) NOT NULL,
    quantity INTEGER NOT NULL
);

CREATE TABLE vault (
    id SERIAL PRIMARY KEY,
    vault_secret VARCHAR(200) NOT NULL
);

INSERT INTO inventory (item_name, quantity) VALUES
('Wireless Mouse', 142),
('USB-C Hub', 87),
('Mechanical Keyboard', 63),
('27" Monitor', 31),
('Webcam HD', 95),
('Ethernet Cable 5m', 210);

INSERT INTO vault (vault_secret) VALUES
('FLAG{pg_dbl1nk_dns_3xf1l}');
