-- =========================
-- SQLi-Arena: MySQL Lab 8
-- Error-Based: GTID_SUBSET / JSON Functions
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab8;
USE sqli_arena_mysql_lab8;

DROP TABLE IF EXISTS api_keys;
DROP TABLE IF EXISTS messages;

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender VARCHAR(50) NOT NULL,
    recipient VARCHAR(50) NOT NULL,
    subject VARCHAR(200),
    body TEXT,
    read_status TINYINT DEFAULT 0,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(50) NOT NULL,
    api_key VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO messages (sender, recipient, subject, body, read_status) VALUES
('alice',   'bob',      'Meeting Tomorrow',     'Don\'t forget the 10am standup.',              0),
('charlie', 'bob',      'Code Review',          'Please review PR #42 when you get a chance.',  0),
('admin',   'bob',      'System Maintenance',   'Downtime scheduled for Saturday 2am.',         0),
('alice',   'charlie',  'Lunch?',               'Want to grab lunch at noon?',                  1),
('bob',     'alice',    'RE: Project Update',   'Looks good, approved.',                        1);

INSERT INTO api_keys (service_name, api_key) VALUES
('weather',   'wk_a8f3b2c1d4e5f6789012345'),
('payment',   'pk_live_9z8y7x6w5v4u3t2s1r0q'),
('internal',  'FLAG{my_gt1d_3rr0r_l34k}');
