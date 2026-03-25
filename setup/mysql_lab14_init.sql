-- =========================
-- SQLi-Arena: MySQL Lab 14
-- INSERT / UPDATE Injection
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab14;
USE sqli_arena_mysql_lab14;

DROP TABLE IF EXISTS admin_panel;
DROP TABLE IF EXISTS feedback;

CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    comment TEXT NOT NULL,
    rating INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin_panel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_secret VARCHAR(100) NOT NULL
);

INSERT INTO feedback (name, comment, rating, created_at) VALUES
('Alice Johnson', 'Great service, very responsive support team!',           5, '2026-03-18 09:00:00'),
('Bob Martinez',  'Decent product but shipping was slow.',                  3, '2026-03-19 11:30:00'),
('Carol Chen',    'Absolutely love it. Will recommend to friends.',         5, '2026-03-20 14:15:00'),
('Dave Wilson',   'Interface is confusing. Needs better documentation.',    2, '2026-03-21 16:45:00');

INSERT INTO admin_panel (admin_secret) VALUES
('FLAG{my_1ns3rt_3rr0r_l34k}');
