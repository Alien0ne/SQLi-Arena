-- =========================
-- SQLi-Arena: PostgreSQL Lab 9
-- RCE -- COPY TO PROGRAM
-- =========================
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab9;
CREATE DATABASE sqli_arena_pgsql_lab9;
\c sqli_arena_pgsql_lab9

CREATE TABLE documents (
    id SERIAL PRIMARY KEY,
    filename VARCHAR(200) NOT NULL,
    content TEXT NOT NULL
);

CREATE TABLE admin_secrets (
    id SERIAL PRIMARY KEY,
    secret_value VARCHAR(200) NOT NULL
);

INSERT INTO documents (filename, content) VALUES
('report_q1.pdf', 'Quarterly financial report for Q1 2026.'),
('employee_list.csv', 'Name, Department, Role -- exported from HR system.'),
('server_config.txt', 'PostgreSQL 16.2 running on Ubuntu 22.04 LTS.'),
('backup_log.txt', 'Last full backup completed at 2026-03-22 03:00 UTC.');

INSERT INTO admin_secrets (secret_value) VALUES
('FLAG{pg_c0py_t0_pr0gr4m_rc3}');
