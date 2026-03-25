-- =========================
-- SQLi-Arena: MySQL Lab 9
-- Blind Boolean: SUBSTRING + IF
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab9;
USE sqli_arena_mysql_lab9;

DROP TABLE IF EXISTS secrets;
DROP TABLE IF EXISTS members;

CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    full_name VARCHAR(100),
    is_active TINYINT DEFAULT 1
);

CREATE TABLE secrets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flag_value VARCHAR(100) NOT NULL
);

INSERT INTO members (username, full_name, is_active) VALUES
('admin',    'Administrator',    1),
('alice',    'Alice Martin',     1),
('bob',      'Bob Turner',       1),
('charlie',  'Charlie Davis',    0),
('diana',    'Diana Ross',       1);

INSERT INTO secrets (flag_value) VALUES
('FLAG{my_b00l_bl1nd_substr}');
