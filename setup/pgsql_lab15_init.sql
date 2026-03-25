-- =========================
-- SQLi-Arena: PostgreSQL Lab 15
-- INSERT / UPDATE Injection
-- =========================
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab15;
CREATE DATABASE sqli_arena_pgsql_lab15;
\c sqli_arena_pgsql_lab15

CREATE TABLE profiles (
    id SERIAL PRIMARY KEY,
    username VARCHAR(200) NOT NULL,
    bio TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE credentials (
    id SERIAL PRIMARY KEY,
    service VARCHAR(200) NOT NULL,
    secret VARCHAR(200) NOT NULL
);

INSERT INTO profiles (username, bio) VALUES
('alice', 'Database administrator and PostgreSQL enthusiast.'),
('bob', 'Security researcher focused on web application testing.'),
('charlie', 'Full-stack developer with a passion for open source.');

INSERT INTO credentials (service, secret) VALUES
('internal_api', 'FLAG{pg_1ns3rt_r3turn1ng}'),
('backup_key', 'aes256-cbc-pkcs7-backup-2026'),
('smtp_relay', 'mailgun-api-key-xxxx-yyyy');
