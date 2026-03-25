-- Lab 2: UNION - Dollar-Quoting Bypass
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab2;
CREATE DATABASE sqli_arena_pgsql_lab2 OWNER sqli_arena;
\c sqli_arena_pgsql_lab2

CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    price NUMERIC(10,2) NOT NULL,
    secret_code TEXT
);

INSERT INTO products (name, price, secret_code) VALUES
('Wireless Mouse', 29.99, 'FLAG{pg_d0ll4r_qu0t3_byp4ss}'),
('Mechanical Keyboard', 89.99, 'PROD-KB-001'),
('USB-C Hub', 45.50, 'PROD-HUB-002'),
('Monitor Stand', 34.00, 'PROD-MS-003'),
('Webcam HD', 59.95, 'PROD-WC-004');

GRANT ALL ON ALL TABLES IN SCHEMA public TO sqli_arena;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;
