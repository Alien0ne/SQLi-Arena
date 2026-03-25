-- SQLite Lab 8: RCE - load_extension() Exploitation
-- Tables: reports, master_secrets

CREATE TABLE IF NOT EXISTS reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    report_name TEXT NOT NULL,
    status TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS master_secrets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    secret_value TEXT NOT NULL
);

INSERT INTO reports (report_name, status) VALUES ('Q1 Financial Summary', 'completed');
INSERT INTO reports (report_name, status) VALUES ('Security Audit 2025', 'in_progress');
INSERT INTO reports (report_name, status) VALUES ('Incident Response Plan', 'draft');
INSERT INTO reports (report_name, status) VALUES ('Compliance Review', 'completed');
INSERT INTO reports (report_name, status) VALUES ('Penetration Test Results', 'pending');

INSERT INTO master_secrets (secret_value) VALUES ('FLAG{sl_l04d_3xt3ns10n_rc3}');
INSERT INTO master_secrets (secret_value) VALUES ('rsa_private_key_placeholder');
INSERT INTO master_secrets (secret_value) VALUES ('root_password_hash_placeholder');
