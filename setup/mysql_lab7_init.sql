-- =========================
-- SQLi-Arena: MySQL Lab 7
-- Error-Based: EXP / BIGINT Overflow
-- =========================

DROP DATABASE IF EXISTS sqli_arena_mysql_lab7;
CREATE DATABASE sqli_arena_mysql_lab7;
USE sqli_arena_mysql_lab7;

DROP TABLE IF EXISTS logs;
DROP TABLE IF EXISTS config;

CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(50) NOT NULL,
    setting_value VARCHAR(200) NOT NULL
);

INSERT INTO logs (action, ip_address, timestamp) VALUES
('login',       '192.168.1.10',     '2026-03-20 08:15:00'),
('logout',      '192.168.1.10',     '2026-03-20 09:30:00'),
('login',       '10.0.0.5',         '2026-03-20 10:00:00'),
('file_upload',  '10.0.0.5',        '2026-03-20 10:05:00'),
('login',       '172.16.0.22',      '2026-03-21 14:20:00'),
('settings',    '172.16.0.22',      '2026-03-21 14:25:00'),
('login',       '192.168.1.10',     '2026-03-22 07:45:00'),
('api_call',    '10.0.0.5',         '2026-03-22 11:00:00');

INSERT INTO config (setting_name, setting_value) VALUES
('master_key', 'FLAG{3xp_b1g1nt_0v3rfl0w}');
