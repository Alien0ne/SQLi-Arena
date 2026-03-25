-- =========================
-- SQLi-Arena: PostgreSQL Lab 5
-- Blind Time -- pg_sleep()
-- =========================
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab5;
CREATE DATABASE sqli_arena_pgsql_lab5;
\c sqli_arena_pgsql_lab5

CREATE TABLE sessions (
    id SERIAL PRIMARY KEY,
    token VARCHAR(200) NOT NULL
);

CREATE TABLE admin_tokens (
    id SERIAL PRIMARY KEY,
    token VARCHAR(200) NOT NULL
);

INSERT INTO sessions (token) VALUES
('sess_abc123def456'),
('sess_xyz789ghi012'),
('sess_mno345pqr678');

INSERT INTO admin_tokens (token) VALUES
('FLAG{pg_sl33p_t1m3_bl1nd}');
