-- Lab 9: RCE - COPY TO PROGRAM
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab9;
CREATE DATABASE sqli_arena_pgsql_lab9 OWNER sqli_arena;
\c sqli_arena_pgsql_lab9

CREATE TABLE documents (
    id SERIAL PRIMARY KEY,
    filename TEXT NOT NULL,
    content TEXT NOT NULL
);

INSERT INTO documents (filename, content) VALUES
('quarterly_report_q3.pdf', 'Revenue summary and projections for Q3 2025.'),
('annual_report_2024.pdf', 'Full year financial statements and audit notes.'),
('incident_report_jan.docx', 'Security incident post-mortem from January.'),
('server_inventory.xlsx', 'Hardware inventory across all data centers.'),
('deployment_guide.md', 'Step-by-step production deployment instructions.');

CREATE TABLE admin_secrets (
    id SERIAL PRIMARY KEY,
    secret_value TEXT NOT NULL
);

INSERT INTO admin_secrets (secret_value) VALUES
('FLAG{pg_c0py_t0_pr0gr4m}');

GRANT ALL ON ALL TABLES IN SCHEMA public TO sqli_arena;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;
