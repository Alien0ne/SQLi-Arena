-- =========================
-- SQLi-Arena: MySQL Lab 16
-- Header Injection: User-Agent
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab16;
USE sqli_arena_mysql_lab16;

DROP TABLE IF EXISTS system_keys;
DROP TABLE IF EXISTS visitors;

CREATE TABLE visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NOT NULL,
    visit_time DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE system_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(50) NOT NULL,
    key_value VARCHAR(100) NOT NULL
);

INSERT INTO visitors (ip_address, user_agent, visit_time) VALUES
('192.168.1.10',  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0',    '2026-03-20 08:12:00'),
('10.0.0.55',     'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_3) Safari/17.2',  '2026-03-20 11:30:00'),
('172.16.0.22',   'Mozilla/5.0 (X11; Linux x86_64) Firefox/121.0',             '2026-03-21 09:45:00'),
('192.168.1.101', 'curl/8.4.0',                                                 '2026-03-22 14:00:00'),
('10.0.0.200',    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2) Safari/604.1',     '2026-03-23 07:15:00');

INSERT INTO system_keys (key_name, key_value) VALUES
('master', 'FLAG{my_h34d3r_us3r_4g3nt}');
