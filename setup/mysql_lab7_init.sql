-- =========================
-- SQLi-Arena: MySQL Lab 7
-- Error-Based: Advanced Error Techniques (EXP / BIGINT Overflow)
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab7;
USE sqli_arena_mysql_lab7;

DROP TABLE IF EXISTS config;
DROP TABLE IF EXISTS logs;

CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    action VARCHAR(100),
    log_time DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(50) NOT NULL,
    setting_value VARCHAR(200) NOT NULL
);

INSERT INTO logs (ip_address, action) VALUES
('192.168.1.10',  'login_success'),
('192.168.1.10',  'page_view'),
('192.168.1.25',  'login_failed'),
('10.0.0.5',      'login_success'),
('172.16.0.100',  'file_download'),
('172.16.0.22',   'settings_update');

INSERT INTO config (setting_name, setting_value) VALUES
('app_name',    'SQLi-Arena'),
('version',     '2.0.1'),
('debug_mode',  'false'),
('master_key',  'FLAG{my_4dv_3rr0r_m4st3r}');
