-- =========================
-- SQLi-Arena: MySQL Lab 20
-- WAF Bypass: GBK Wide Byte
-- =========================

DROP DATABASE IF EXISTS sqli_arena_mysql_lab20;
CREATE DATABASE sqli_arena_mysql_lab20;
USE sqli_arena_mysql_lab20;

DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS secret_data;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL
) CHARACTER SET gbk;

CREATE TABLE secret_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    secret VARCHAR(100) NOT NULL
) CHARACTER SET gbk;

INSERT INTO users (username, password, email) VALUES
('admin',   'adm1nP@ss',   'admin@corp.local'),
('alice',   'alice123',     'alice@corp.local'),
('bob',     'bob456',       'bob@corp.local'),
('charlie', 'charlie789',   'charlie@corp.local'),
('dave',    'dave000',      'dave@corp.local');

INSERT INTO secret_data (secret) VALUES
('FLAG{w1d3_byt3_gbk_3sc4p3}');
