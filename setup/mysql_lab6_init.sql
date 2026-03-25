-- =========================
-- SQLi-Arena: MySQL Lab 6
-- Error-Based: Floor + GROUP BY (Double Query)
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab6;
USE sqli_arena_mysql_lab6;

DROP TABLE IF EXISTS vault;
DROP TABLE IF EXISTS accounts;

CREATE TABLE accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holder_name VARCHAR(100) NOT NULL,
    account_type VARCHAR(50) NOT NULL,
    balance DECIMAL(12,2) NOT NULL
);

CREATE TABLE vault (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vault_code VARCHAR(100) NOT NULL
);

INSERT INTO accounts (holder_name, account_type, balance) VALUES
('Alice Johnson',   'savings',      15420.50),
('Bob Williams',    'checking',      8230.75),
('Charlie Davis',   'savings',      42100.00),
('David Brown',     'checking',      3150.25),
('Eve Martinez',    'investment',   98500.00);

INSERT INTO vault (vault_code) VALUES
('FLAG{my_fl00r_r4nd_d0ubl3}');
