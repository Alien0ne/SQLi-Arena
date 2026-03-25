-- =========================
-- SQLi-Arena: PostgreSQL Lab 6
-- Stacked Queries -- Multi-Statement
-- =========================
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab6;
CREATE DATABASE sqli_arena_pgsql_lab6;
\c sqli_arena_pgsql_lab6

CREATE TABLE notes (
    id SERIAL PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL
);

CREATE TABLE flag_store (
    id SERIAL PRIMARY KEY,
    flag_text VARCHAR(200) NOT NULL
);

INSERT INTO notes (title, content) VALUES
('Meeting Notes', 'Discuss project timeline and deliverables for Q2.'),
('Shopping List', 'Milk, eggs, bread, coffee beans.'),
('Bug Report', 'Login page crashes when password contains special characters.'),
('Ideas', 'Build a PostgreSQL injection training platform.');

INSERT INTO flag_store (flag_text) VALUES
('FLAG{pg_st4ck3d_mult1_qu3ry}');
