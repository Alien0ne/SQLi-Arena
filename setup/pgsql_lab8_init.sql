-- =========================
-- SQLi-Arena: PostgreSQL Lab 8
-- File Write -- COPY TO / lo_export
-- =========================
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab8;
CREATE DATABASE sqli_arena_pgsql_lab8;
\c sqli_arena_pgsql_lab8

CREATE TABLE feedback (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE secret_data (
    id SERIAL PRIMARY KEY,
    secret_value VARCHAR(200) NOT NULL
);

INSERT INTO feedback (username, message) VALUES
('user1', 'Great platform for learning SQL injection techniques!'),
('user2', 'The PostgreSQL labs are very well structured.'),
('user3', 'Would love to see more advanced challenges.');

INSERT INTO secret_data (secret_value) VALUES
('FLAG{pg_f1l3_wr1t3_c0py}');
