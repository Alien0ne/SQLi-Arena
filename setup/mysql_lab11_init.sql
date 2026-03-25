-- =========================
-- SQLi-Arena: MySQL Lab 11
-- Blind Time-Based: SLEEP() + IF
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab11;
USE sqli_arena_mysql_lab11;

DROP TABLE IF EXISTS admin_tokens;
DROP TABLE IF EXISTS sessions;

CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_token VARCHAR(64) NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(100) NOT NULL
);

INSERT INTO sessions (session_token, user_id, created_at) VALUES
('abc123def456',  1, '2026-03-20 08:15:00'),
('xyz789ghi012',  2, '2026-03-20 09:30:00'),
('mno345pqr678',  3, '2026-03-21 11:00:00'),
('stu901vwx234',  4, '2026-03-22 14:45:00'),
('jkl567yza890',  5, '2026-03-23 07:00:00');

INSERT INTO admin_tokens (token) VALUES
('FLAG{my_t1m3_sl33p_bl1nd}');
