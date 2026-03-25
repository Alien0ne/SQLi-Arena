-- Lab 7: File Read - pg_read_file / COPY
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab7;
CREATE DATABASE sqli_arena_pgsql_lab7 OWNER sqli_arena;
\c sqli_arena_pgsql_lab7

CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL
);

INSERT INTO products (name, description) VALUES
('PostgreSQL Admin Guide', 'Complete reference for PostgreSQL database administration.'),
('SQL Tuning Handbook', 'Performance optimization techniques for SQL queries.'),
('Data Modeling 101', 'Introduction to relational database design patterns.'),
('Backup Strategies', 'Best practices for PostgreSQL backup and recovery.');

CREATE TABLE server_secrets (
    id SERIAL PRIMARY KEY,
    secret_value TEXT NOT NULL
);

INSERT INTO server_secrets (secret_value) VALUES
('FLAG{pg_f1l3_r34d_un10n}');

GRANT ALL ON ALL TABLES IN SCHEMA public TO sqli_arena;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;
