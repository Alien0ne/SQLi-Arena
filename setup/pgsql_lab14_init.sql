-- Lab 14: Privilege Escalation - ALTER ROLE
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab14;
CREATE DATABASE sqli_arena_pgsql_lab14 OWNER sqli_arena;
\c sqli_arena_pgsql_lab14

CREATE TABLE admin_logs (
    id SERIAL PRIMARY KEY,
    action TEXT NOT NULL,
    details TEXT NOT NULL,
    log_time TIMESTAMP DEFAULT NOW()
);

INSERT INTO admin_logs (action, details, log_time) VALUES
('LOGIN', 'Admin user logged in from 192.168.1.100', '2025-03-20 09:15:00'),
('LOGIN', 'User editor logged in from 10.0.0.5', '2025-03-20 10:32:00'),
('CONFIG_CHANGE', 'Updated max_connections from 100 to 200', '2025-03-21 08:45:00'),
('BACKUP', 'Full database backup initiated by admin', '2025-03-21 14:00:00'),
('LOGOUT', 'Admin user logged out', '2025-03-21 18:30:00');

CREATE TABLE restricted_data (
    id SERIAL PRIMARY KEY,
    secret_value TEXT NOT NULL
);

INSERT INTO restricted_data (secret_value) VALUES
('FLAG{pg_4lt3r_r0l3_pr1v3sc}');

GRANT ALL ON ALL TABLES IN SCHEMA public TO sqli_arena;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;
