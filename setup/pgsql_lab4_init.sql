-- =========================
-- SQLi-Arena: PostgreSQL Lab 4
-- Blind Boolean -- CASE + SUBSTRING
-- =========================
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab4;
CREATE DATABASE sqli_arena_pgsql_lab4;
\c sqli_arena_pgsql_lab4

CREATE TABLE members (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT true
);

CREATE TABLE secrets (
    id SERIAL PRIMARY KEY,
    secret_value VARCHAR(200) NOT NULL
);

INSERT INTO members (username, is_active) VALUES
('alice', true),
('bob', true),
('charlie', false),
('diana', true),
('eve', false);

INSERT INTO secrets (secret_value) VALUES
('FLAG{pg_bl1nd_b00l_c4s3}');
