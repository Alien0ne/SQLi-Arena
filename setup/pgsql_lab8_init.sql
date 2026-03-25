-- Lab 8: File Write - COPY TO / lo_export
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab8;
CREATE DATABASE sqli_arena_pgsql_lab8 OWNER sqli_arena;
\c sqli_arena_pgsql_lab8

CREATE TABLE feedback (
    id SERIAL PRIMARY KEY,
    username TEXT NOT NULL,
    message TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT NOW()
);

INSERT INTO feedback (username, message) VALUES
('John', 'Great platform for learning SQL injection techniques!'),
('Sarah', 'The PostgreSQL labs are very well structured.'),
('Mike', 'Would love to see more advanced challenges.');

CREATE TABLE secret_data (
    id SERIAL PRIMARY KEY,
    secret_value TEXT NOT NULL
);

INSERT INTO secret_data (secret_value) VALUES
('FLAG{pg_1ns3rt_c4st_3rr0r}');

GRANT ALL ON ALL TABLES IN SCHEMA public TO sqli_arena;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;
