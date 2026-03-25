-- =========================
-- SQLi-Arena: MySQL Lab 17
-- Header Injection: Cookie
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab17;
USE sqli_arena_mysql_lab17;

DROP TABLE IF EXISTS credentials;
DROP TABLE IF EXISTS preferences;

CREATE TABLE preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    theme VARCHAR(50) NOT NULL,
    language VARCHAR(50) NOT NULL,
    last_login DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service VARCHAR(50) NOT NULL,
    secret VARCHAR(100) NOT NULL
);

INSERT INTO preferences (user_id, theme, language, last_login) VALUES
('user1', 'dark',       'en', '2026-03-20 08:30:00'),
('user2', 'light',      'fr', '2026-03-21 10:15:00'),
('user3', 'solarized',  'de', '2026-03-21 14:00:00'),
('user4', 'monokai',    'ja', '2026-03-22 09:45:00'),
('user5', 'dracula',    'es', '2026-03-23 07:00:00');

INSERT INTO credentials (service, secret) VALUES
('database', 'FLAG{my_c00k13_1nj3ct10n}');
