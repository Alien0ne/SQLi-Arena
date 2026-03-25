-- =========================
-- SQLi-Arena: PostgreSQL Lab 3
-- Error -- CAST Type Mismatch
-- =========================
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab3;
CREATE DATABASE sqli_arena_pgsql_lab3;
\c sqli_arena_pgsql_lab3

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL,
    role VARCHAR(50) NOT NULL
);

INSERT INTO users (username, password, role) VALUES
('admin', 'FLAG{pg_c4st_typ3_3rr0r}', 'administrator'),
('editor', 'ed1t0rPass!', 'editor'),
('viewer', 'v13w0nly#', 'viewer'),
('moderator', 'M0dPa$$99', 'moderator');
