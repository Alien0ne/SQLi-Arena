-- =========================
-- SQLi-Arena: PostgreSQL Lab 14
-- Privilege Escalation -- ALTER ROLE
-- =========================
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab14;
CREATE DATABASE sqli_arena_pgsql_lab14;
\c sqli_arena_pgsql_lab14

CREATE TABLE admin_logs (
    id SERIAL PRIMARY KEY,
    action VARCHAR(200) NOT NULL,
    details TEXT NOT NULL,
    log_time TIMESTAMP DEFAULT NOW()
);

CREATE TABLE restricted_data (
    id SERIAL PRIMARY KEY,
    secret_value VARCHAR(200) NOT NULL
);

INSERT INTO admin_logs (action, details) VALUES
('LOGIN', 'Admin user logged in from 192.168.1.100'),
('CONFIG_CHANGE', 'Updated max_connections from 100 to 200'),
('BACKUP', 'Full database backup initiated by admin'),
('USER_CREATE', 'New user analyst_01 created with read-only role'),
('POLICY_UPDATE', 'Password policy updated: min 12 chars, require special');

INSERT INTO restricted_data (secret_value) VALUES
('FLAG{pg_4lt3r_r0l3_pr1v3sc}');
