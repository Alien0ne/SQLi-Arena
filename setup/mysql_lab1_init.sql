-- =========================
-- SQLi-Arena: MySQL Lab 1
-- Basic String Injection
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab1;
USE sqli_arena_mysql_lab1;

DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(100)
);

INSERT INTO users (username, password, email) VALUES
('alice',   'alice_sunny_42',               'alice@example.com'),
('bob',     'b0bSecure!99',                 'bob@example.com'),
('charlie', 'ch4rlie_thunder',              'charlie@example.com'),
('david',   'david_pass_2026',              'david@example.com'),
('admin',   'FLAG{my_un10n_b4s1c_str1ng}',  'admin@sqli-arena.local');
