-- =========================
-- SQLi-Arena: MySQL Lab 5
-- Error-Based: ExtractValue / UpdateXML
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab5;
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
('guest',   'guest123',                         'guest@example.com',        'user'),
('alice',   'al1ce_p4ssw0rd',                   'alice@example.com',        'user'),
('bob',     'b0bSecure!99',                     'bob@example.com',          'user'),
('charlie', 'ch4rlie_thunder',                  'charlie@example.com',      'moderator'),
('admin',   'FLAG{my_3rr0r_b4s3d_3xtr4ct}',    'admin@sqli-arena.local',   'admin');
