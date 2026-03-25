-- =========================
-- SQLi-Arena: MySQL Lab 19
-- WAF Bypass: Keyword Blacklist
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab19;
USE sqli_arena_mysql_lab19;

DROP TABLE IF EXISTS flags;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    role VARCHAR(50) NOT NULL
);

CREATE TABLE flags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flag_value VARCHAR(100) NOT NULL
);

INSERT INTO users (username, role) VALUES
('admin',   'administrator'),
('john',    'user'),
('jane',    'user'),
('mike',    'moderator'),
('sarah',   'user');

INSERT INTO flags (flag_value) VALUES
('FLAG{my_w4f_byp4ss_n3st3d}');
