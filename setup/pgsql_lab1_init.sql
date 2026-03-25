-- Lab 1: UNION - Basic String Injection
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab1;
CREATE DATABASE sqli_arena_pgsql_lab1 OWNER sqli_arena;
\c sqli_arena_pgsql_lab1

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username TEXT NOT NULL,
    password TEXT NOT NULL,
    email TEXT NOT NULL
);

INSERT INTO users (username, password, email) VALUES
('jdoe', 'p@ssw0rd123', 'jdoe@sqli-arena.local'),
('alice', 'alice2024!', 'alice@sqli-arena.local'),
('bob', 'bobSecure#1', 'bob@sqli-arena.local'),
('charlie', 'charL13!!', 'charlie@sqli-arena.local'),
('admin', 'FLAG{pg_un10n_c4st_typ3}', 'admin@sqli-arena.local');

GRANT ALL ON ALL TABLES IN SCHEMA public TO sqli_arena;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;
