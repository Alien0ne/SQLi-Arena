-- =========================
-- SQLi-Arena: MySQL Lab 5
-- Error-Based: ExtractValue / UpdateXML
-- =========================

DROP DATABASE IF EXISTS sqli_arena_mysql_lab5;
CREATE DATABASE sqli_arena_mysql_lab5;
USE sqli_arena_mysql_lab5;

DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role VARCHAR(20) DEFAULT 'user'
);

INSERT INTO users (username, password, email, role) VALUES
('alice',   'alice_sunny_42',                   'alice@example.com',        'user'),
('bob',     'b0bSecure!99',                     'bob@example.com',          'user'),
('charlie', 'ch4rlie_thunder',                  'charlie@example.com',      'user'),
('david',   'david_pass_2026',                  'david@example.com',        'user'),
('eve',     'eVe_qu4ntum',                      'eve@example.com',          'user'),
('admin',   'FLAG{3xtr4ctv4lu3_x0rth_3rr0r}',  'admin@sqli-arena.local',   'admin');
