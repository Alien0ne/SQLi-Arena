DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS master_secrets;

CREATE TABLE reports (
    id INTEGER PRIMARY KEY,
    report_name TEXT,
    status TEXT
);

CREATE TABLE master_secrets (
    id INTEGER PRIMARY KEY,
    secret_value TEXT
);

INSERT INTO reports (id, report_name, status) VALUES (1, 'Q1 Financial Report', 'published');
INSERT INTO reports (id, report_name, status) VALUES (2, 'Security Audit 2025', 'draft');
INSERT INTO reports (id, report_name, status) VALUES (3, 'Infrastructure Review', 'published');
INSERT INTO reports (id, report_name, status) VALUES (4, 'Compliance Report', 'pending');

INSERT INTO master_secrets (id, secret_value) VALUES (1, 'FLAG{sq_l04d_3xt_rc3}');
