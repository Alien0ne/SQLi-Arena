-- Lab 6: Stacked Queries - Multi-Statement
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab6;
CREATE DATABASE sqli_arena_pgsql_lab6 OWNER sqli_arena;
\c sqli_arena_pgsql_lab6

CREATE TABLE notes (
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    content TEXT NOT NULL
);

INSERT INTO notes (title, content) VALUES
('Meeting Notes', 'Discuss project timeline and deliverables for Q2.'),
('Shopping List', 'Milk, eggs, bread, coffee beans.'),
('Bug Report', 'Login page crashes when password contains special characters.'),
('Ideas', 'Build a PostgreSQL injection training platform.');

CREATE TABLE flag_store (
    id SERIAL PRIMARY KEY,
    flag_text TEXT NOT NULL
);

INSERT INTO flag_store (flag_text) VALUES
('FLAG{pg_st4ck3d_1ns3rt}');

GRANT ALL ON ALL TABLES IN SCHEMA public TO sqli_arena;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;
