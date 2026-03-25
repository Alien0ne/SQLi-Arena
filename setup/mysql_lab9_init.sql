-- =========================
-- SQLi-Arena: MySQL Lab 9
-- Blind Boolean: SUBSTRING + IF
-- =========================

DROP DATABASE IF EXISTS sqli_arena_mysql_lab9;
CREATE DATABASE sqli_arena_mysql_lab9;
USE sqli_arena_mysql_lab9;

DROP TABLE IF EXISTS members;
DROP TABLE IF EXISTS secrets;

CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    membership_type VARCHAR(30) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE secrets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flag_value VARCHAR(100) NOT NULL
);

INSERT INTO members (username, membership_type, is_active) VALUES
('admin',    'platinum', TRUE),
('jsmith',   'gold',     TRUE),
('klee',     'silver',   TRUE),
('mnguyen',  'bronze',   TRUE),
('pgarcia',  'gold',     TRUE),
('rwilson',  'silver',   FALSE),
('tchen',    'bronze',   FALSE);

INSERT INTO secrets (flag_value) VALUES
('FLAG{bl1nd_b00l_substr1ng}');
