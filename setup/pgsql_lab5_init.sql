-- Lab 5: Blind Time - pg_sleep()
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab5;
CREATE DATABASE sqli_arena_pgsql_lab5 OWNER sqli_arena;
\c sqli_arena_pgsql_lab5

CREATE TABLE sessions (
    id SERIAL PRIMARY KEY,
    token TEXT NOT NULL,
    user_id INTEGER NOT NULL
);

INSERT INTO sessions (token, user_id) VALUES
('sess_abc123def456', 1),
('sess_xyz789ghi012', 2),
('sess_mno345pqr678', 3);

CREATE TABLE admin_tokens (
    id SERIAL PRIMARY KEY,
    token TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

INSERT INTO admin_tokens (token) VALUES
('FLAG{pg_sl33p_t1m3_bl1nd}');

GRANT ALL ON ALL TABLES IN SCHEMA public TO sqli_arena;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;
