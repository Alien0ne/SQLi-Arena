-- Lab 11: OOB - dblink + DNS Exfiltration
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab11;
CREATE DATABASE sqli_arena_pgsql_lab11 OWNER sqli_arena;
\c sqli_arena_pgsql_lab11

CREATE TABLE inventory (
    id SERIAL PRIMARY KEY,
    item_name TEXT NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 0
);

INSERT INTO inventory (item_name, quantity) VALUES
('Wireless Mouse', 142),
('USB-C Hub', 87),
('Mechanical Keyboard', 63),
('27" Monitor', 31),
('Webcam HD', 95);

CREATE TABLE vault (
    id SERIAL PRIMARY KEY,
    vault_secret TEXT NOT NULL
);

INSERT INTO vault (vault_secret) VALUES
('FLAG{pg_dbl1nk_dns_3xf1l}');

GRANT ALL ON ALL TABLES IN SCHEMA public TO sqli_arena;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;
