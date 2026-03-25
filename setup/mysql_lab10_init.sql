-- =========================
-- SQLi-Arena: MySQL Lab 10
-- Blind Boolean: REGEXP / LIKE
-- =========================

DROP DATABASE IF EXISTS sqli_arena_mysql_lab10;
CREATE DATABASE sqli_arena_mysql_lab10;
USE sqli_arena_mysql_lab10;

DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS warehouse_codes;

CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    sku VARCHAR(20) NOT NULL,
    in_stock BOOLEAN DEFAULT TRUE
);

CREATE TABLE warehouse_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL
);

INSERT INTO inventory (item_name, sku, in_stock) VALUES
('Industrial Router',       'SKU001', TRUE),
('Managed Switch 24-port',  'SKU002', TRUE),
('Fiber Patch Cable',       'SKU003', TRUE),
('Server Rack 42U',         'SKU004', FALSE),
('UPS Backup 1500VA',       'SKU005', TRUE),
('KVM Console',             'SKU006', FALSE);

INSERT INTO warehouse_codes (code) VALUES
('FLAG{r3g3xp_l1k3_0r4cl3}');
