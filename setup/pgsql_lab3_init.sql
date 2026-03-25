-- Lab 3: Error - CAST Type Mismatch
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab3;
CREATE DATABASE sqli_arena_pgsql_lab3 OWNER sqli_arena;
\c sqli_arena_pgsql_lab3

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username TEXT NOT NULL,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user'
);

INSERT INTO users (username, password, role) VALUES
('admin', 'FLAG{pg_c4st_3rr0r_l34k}', 'admin'),
('editor', 'ed1t0rPass!', 'editor'),
('viewer', 'v13w0nly#', 'viewer'),
('moderator', 'M0dPa$$99', 'moderator');

GRANT ALL ON ALL TABLES IN SCHEMA public TO sqli_arena;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;
