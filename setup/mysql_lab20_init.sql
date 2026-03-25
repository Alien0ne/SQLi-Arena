-- =========================
-- SQLi-Arena: MySQL Lab 20
-- WAF Bypass: GBK Wide Byte Injection
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab20;
USE sqli_arena_mysql_lab20;

ALTER DATABASE sqli_arena_mysql_lab20 CHARACTER SET gbk;

DROP TABLE IF EXISTS secret_data;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL
) CHARACTER SET gbk;

CREATE TABLE secret_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    secret VARCHAR(100) NOT NULL
) CHARACTER SET gbk;

INSERT INTO users (username, email) VALUES
('admin',   'admin@corp.local'),
('alice',   'alice@corp.local'),
('bob',     'bob@corp.local'),
('charlie', 'charlie@corp.local'),
('dave',    'dave@corp.local');

INSERT INTO secret_data (secret) VALUES
('FLAG{my_w1d3_byt3_gbk}');
