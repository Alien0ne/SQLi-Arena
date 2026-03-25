-- ================================
-- SQLi-Arena: MariaDB Lab 2
-- CONNECT Engine -- Remote Tables
-- ================================

CREATE DATABASE IF NOT EXISTS sqli_arena_mariadb_lab2;
USE sqli_arena_mariadb_lab2;

DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS engine_secrets;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL
);

CREATE TABLE engine_secrets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    secret_value VARCHAR(100) NOT NULL
);

INSERT INTO products (name, price) VALUES
('MariaDB Enterprise License', 4999.99),
('CONNECT Engine Plugin', 299.99),
('Spider Engine Module', 499.99),
('Galera Cluster Pack', 1999.99),
('MaxScale Proxy', 799.99);

INSERT INTO engine_secrets (secret_value) VALUES
('FLAG{ma_c0nn3ct_3ng1n3_csv}');
