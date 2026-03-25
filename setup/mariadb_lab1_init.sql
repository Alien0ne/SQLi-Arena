-- ================================
-- SQLi-Arena: MariaDB Lab 1
-- UNION -- MySQL-Compatible Basics
-- ================================

DROP DATABASE IF EXISTS sqli_arena_mariadb_lab1;
CREATE DATABASE sqli_arena_mariadb_lab1;
USE sqli_arena_mariadb_lab1;

DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(100)
);

INSERT INTO users (username, password, email) VALUES
('alice',   'alice_maria_42',           'alice@example.com'),
('bob',     'b0bMar1a!99',             'bob@example.com'),
('charlie', 'ch4rlie_thunder',          'charlie@example.com'),
('david',   'david_pass_2026',          'david@example.com'),
('eve',     'eVe_qu4ntum',              'eve@example.com'),
('frank',   'fr4nk_bl4ze',              'frank@example.com'),
('grace',   'gr4ce_st4r',               'grace@example.com'),
('admin',   'FLAG{ma_un10n_mysql_c0mp4t}', 'admin@sqli-arena.local');
