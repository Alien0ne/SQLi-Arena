-- =========================
-- SQLi-Arena: MySQL Lab 13
-- Stacked Queries
-- =========================

DROP DATABASE IF EXISTS sqli_arena_mysql_lab13;
CREATE DATABASE sqli_arena_mysql_lab13;
USE sqli_arena_mysql_lab13;

DROP TABLE IF EXISTS notes;
DROP TABLE IF EXISTS flag_store;
DROP TABLE IF EXISTS verification;

CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    author VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE flag_store (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flag_text VARCHAR(100) NOT NULL
);

CREATE TABLE verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    verified BOOL DEFAULT 0
);

INSERT INTO notes (title, content, author, created_at) VALUES
('Meeting Notes',       'Discuss Q1 targets and budget allocation.',     'alice',   '2026-03-10 09:00:00'),
('Project Plan',        'Phase 1 deadline is April 15th.',               'alice',   '2026-03-11 10:30:00'),
('Bug Report #42',      'Login page crashes on mobile devices.',         'bob',     '2026-03-12 14:00:00'),
('Server Maintenance',  'Scheduled downtime Saturday 2am-4am.',          'charlie', '2026-03-13 08:00:00'),
('API Documentation',   'REST endpoints for v2 are finalized.',          'bob',     '2026-03-14 11:15:00'),
('Security Audit',      'Penetration test results pending review.',      'diana',   '2026-03-15 16:45:00');

INSERT INTO flag_store (flag_text) VALUES
('FLAG{st4ck3d_qu3r13s_mult1}');

INSERT INTO verification (verified) VALUES (0);
