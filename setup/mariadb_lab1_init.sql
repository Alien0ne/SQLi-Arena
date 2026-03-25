-- ================================
-- SQLi-Arena: MariaDB Lab 1
-- UNION -- MySQL-Compatible Basics
-- ================================

CREATE DATABASE IF NOT EXISTS sqli_arena_mariadb_lab1;
USE sqli_arena_mariadb_lab1;

DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(100)
);

INSERT INTO users (username, password, email) VALUES
('alice',   'alice_maria_42',              'alice@example.com'),
('bob',     'b0bMar1a!99',                'bob@example.com'),
('charlie', 'ch4rlie_thunder',             'charlie@example.com'),
('david',   'david_pass_2026',             'david@example.com'),
('admin',   'FLAG{ma_un10n_w1r3_c0mp4t}', 'admin@sqli-arena.local');
