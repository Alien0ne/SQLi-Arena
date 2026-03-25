-- ================================
-- SQLi-Arena: MariaDB Lab 5
-- Sequence Object Injection
-- ================================

DROP DATABASE IF EXISTS sqli_arena_mariadb_lab5;
CREATE DATABASE sqli_arena_mariadb_lab5;
USE sqli_arena_mariadb_lab5;

DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS sequence_vault;

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_ref VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL
);

CREATE TABLE sequence_vault (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vault_key VARCHAR(100) NOT NULL
);

-- Create a MariaDB sequence for order numbering
DROP SEQUENCE IF EXISTS order_seq;
CREATE SEQUENCE order_seq START WITH 1000 INCREMENT BY 1;

INSERT INTO orders (order_ref, amount) VALUES
('ORD-1000', 149.99),
('ORD-1001', 299.50),
('ORD-1002', 89.00),
('ORD-1003', 1250.00),
('ORD-1004', 34.99),
('ORD-1005', 599.99);

INSERT INTO sequence_vault (vault_key) VALUES
('FLAG{ma_s3qu3nc3_0bj_1nj}');
