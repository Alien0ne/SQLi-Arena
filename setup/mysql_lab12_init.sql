-- =========================
-- SQLi-Arena: MySQL Lab 12
-- Blind Time-Based: Heavy Query (no SLEEP)
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab12;
USE sqli_arena_mysql_lab12;

DROP TABLE IF EXISTS master_password;
DROP TABLE IF EXISTS audit_log;

CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event VARCHAR(100) NOT NULL,
    user VARCHAR(50) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE master_password (
    id INT AUTO_INCREMENT PRIMARY KEY,
    password VARCHAR(100) NOT NULL
);

INSERT INTO audit_log (event, user, timestamp) VALUES
('login_success',      'admin',    '2026-03-20 08:00:00'),
('file_upload',        'jsmith',   '2026-03-20 09:12:00'),
('password_change',    'klee',     '2026-03-20 10:30:00'),
('login_failed',       'unknown',  '2026-03-21 02:15:00'),
('permission_change',  'admin',    '2026-03-21 11:00:00'),
('login_success',      'mnguyen',  '2026-03-22 07:45:00'),
('config_update',      'admin',    '2026-03-22 16:00:00'),
('logout',             'jsmith',   '2026-03-23 05:30:00');

INSERT INTO master_password (password) VALUES
('FLAG{my_h34vy_t1m3_c4rt3s1an}');
