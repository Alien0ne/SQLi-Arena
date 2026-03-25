-- =========================
-- SQLi-Arena: MySQL Lab 2
-- Integer-Based Injection (No Quotes)
-- =========================

DROP DATABASE IF EXISTS sqli_arena_mysql_lab2;
CREATE DATABASE sqli_arena_mysql_lab2;
USE sqli_arena_mysql_lab2;

DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS secret_products;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL
);

CREATE TABLE secret_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codename VARCHAR(100) NOT NULL,
    access_key VARCHAR(100) NOT NULL
);

INSERT INTO products (name, price, category) VALUES
('Wireless Mouse',       29.99,  'Electronics'),
('USB-C Hub',            49.95,  'Electronics'),
('Mechanical Keyboard',  89.00,  'Electronics'),
('Standing Desk Mat',    34.50,  'Office'),
('LED Desk Lamp',        22.75,  'Office'),
('Noise-Cancelling Headphones', 199.99, 'Audio');

INSERT INTO secret_products (codename, access_key) VALUES
('Project Nightfall', 'FLAG{int3g3r_inj3ct10n_n0_qu0t3s}');
