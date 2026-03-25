-- =========================
-- SQLi-Arena: MySQL Lab 15
-- ORDER BY / GROUP BY Injection
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab15;
USE sqli_arena_mysql_lab15;

DROP TABLE IF EXISTS promo_codes;
DROP TABLE IF EXISTS products;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    rating DECIMAL(3,1) NOT NULL
);

CREATE TABLE promo_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL,
    discount INT NOT NULL
);

INSERT INTO products (name, price, category, rating) VALUES
('Wireless Mouse',       29.99,  'Electronics',  4.5),
('Mechanical Keyboard',  89.99,  'Electronics',  4.8),
('USB-C Hub',            45.00,  'Electronics',  4.2),
('Standing Desk',       349.99,  'Furniture',    4.7),
('Ergonomic Chair',     499.99,  'Furniture',    4.9),
('LED Desk Lamp',        34.50,  'Furniture',    4.1),
('Noise-Cancel Headset',159.00,  'Audio',        4.6),
('Portable Speaker',     69.99,  'Audio',        4.3);

INSERT INTO promo_codes (code, discount) VALUES
('FLAG{my_0rd3r_by_1nj3ct10n}', 99);
