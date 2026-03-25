-- =========================
-- SQLi-Arena: MySQL Lab 8
-- Error-Based: GTID_SUBSET / JSON Functions
-- =========================

DROP DATABASE IF EXISTS sqli_arena_mysql_lab8;
CREATE DATABASE sqli_arena_mysql_lab8;
USE sqli_arena_mysql_lab8;

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS api_keys;

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender VARCHAR(50) NOT NULL,
    recipient VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    read_status BOOLEAN DEFAULT FALSE
);

CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(50) NOT NULL,
    api_key VARCHAR(100) NOT NULL
);

INSERT INTO messages (sender, recipient, message, read_status) VALUES
('alice',   'bob',      'Hey Bob, can you review the report?',          FALSE),
('charlie', 'bob',      'Meeting at 3pm today.',                        FALSE),
('david',   'bob',      'Updated the deployment scripts.',              TRUE),
('eve',     'alice',    'Alice, the test results are ready.',           FALSE),
('bob',     'alice',    'Got it, will check this afternoon.',           TRUE),
('charlie', 'alice',    'Please approve the pull request.',             FALSE);

INSERT INTO api_keys (service_name, api_key) VALUES
('internal', 'FLAG{gt1d_j50n_3rr0r_l34k}');
