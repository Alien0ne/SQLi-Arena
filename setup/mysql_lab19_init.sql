-- =========================
-- SQLi-Arena: MySQL Lab 19
-- WAF Bypass: Keyword Blacklist
-- =========================

DROP DATABASE IF EXISTS sqli_arena_mysql_lab19;
CREATE DATABASE sqli_arena_mysql_lab19;
USE sqli_arena_mysql_lab19;

DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS flags;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL,
    role VARCHAR(50) NOT NULL
);

CREATE TABLE flags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flag_value VARCHAR(100) NOT NULL
);

INSERT INTO users (username, password, role) VALUES
('admin',   'supersecret',  'administrator'),
('john',    'john123',      'user'),
('jane',    'jane456',      'user'),
('mike',    'mike789',      'moderator'),
('sarah',   'sarah000',     'user');

INSERT INTO flags (flag_value) VALUES
('FLAG{w4f_byp4ss_k3yw0rd}');
