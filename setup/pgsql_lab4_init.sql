-- Lab 4: Blind Boolean - CASE + SUBSTRING
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab4;
CREATE DATABASE sqli_arena_pgsql_lab4 OWNER sqli_arena;
\c sqli_arena_pgsql_lab4

CREATE TABLE members (
    id SERIAL PRIMARY KEY,
    username TEXT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT true
);

INSERT INTO members (username, is_active) VALUES
('alice', true),
('bob', true),
('charlie', false),
('diana', true),
('eve', false);

CREATE TABLE secrets (
    id SERIAL PRIMARY KEY,
    secret_value TEXT NOT NULL
);

INSERT INTO secrets (secret_value) VALUES
('FLAG{pg_b00l_bl1nd_substr}');

GRANT ALL ON ALL TABLES IN SCHEMA public TO sqli_arena;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;
