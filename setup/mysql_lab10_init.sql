-- =========================
-- SQLi-Arena: MySQL Lab 10
-- Blind Boolean: REGEXP / LIKE
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab10;
USE sqli_arena_mysql_lab10;

DROP TABLE IF EXISTS warehouse_codes;
DROP TABLE IF EXISTS inventory;

CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(20) NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    quantity INT DEFAULT 0,
    in_stock TINYINT DEFAULT 1
);

CREATE TABLE warehouse_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL
);

INSERT INTO inventory (sku, item_name, quantity, in_stock) VALUES
('SKU001', 'Industrial Router',      150, 1),
('SKU002', 'Managed Switch 24-port',  75, 1),
('SKU003', 'Fiber Patch Cable',       200, 1),
('SKU004', 'Server Rack 42U',          0, 0),
('SKU005', 'UPS Backup 1500VA',       12, 1);

INSERT INTO warehouse_codes (code) VALUES
('FLAG{my_r3g3x_bl1nd_p4tt3rn}');
