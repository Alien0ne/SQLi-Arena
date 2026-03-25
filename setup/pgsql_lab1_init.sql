-- =========================
-- SQLi-Arena: PostgreSQL Lab 1
-- UNION -- Basic String Injection
-- =========================
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab1;
CREATE DATABASE sqli_arena_pgsql_lab1;
\c sqli_arena_pgsql_lab1

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL
);

INSERT INTO users (username, password, email) VALUES
('admin', 'FLAG{pg_un10n_b4s1c_str1ng}', 'admin@sqli-arena.local'),
('jdoe', 'p@ssw0rd123', 'jdoe@sqli-arena.local'),
('alice', 'alice2024!', 'alice@sqli-arena.local'),
('bob', 'bobSecure#1', 'bob@sqli-arena.local'),
('charlie', 'charL13!!', 'charlie@sqli-arena.local');
