-- =========================
-- SQLi-Arena: MySQL Lab 18
-- Second-Order Injection
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab18;
USE sqli_arena_mysql_lab18;

DROP TABLE IF EXISTS secrets;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    bio TEXT
);

CREATE TABLE secrets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flag_text VARCHAR(100) NOT NULL
);

INSERT INTO users (username, password, bio) VALUES
('alice',   'alice123',     'Hi, I am Alice. I love cats.'),
('bob',     'bob456',       'Bob here. Security enthusiast.'),
('charlie', 'charlie789',  'Charlie -- just passing through.');

INSERT INTO secrets (flag_text) VALUES
('FLAG{my_s3c0nd_0rd3r_1nj}');
