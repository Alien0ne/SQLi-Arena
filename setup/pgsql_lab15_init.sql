-- Lab 15: INSERT / UPDATE Injection with RETURNING
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab15;
CREATE DATABASE sqli_arena_pgsql_lab15 OWNER sqli_arena;
\c sqli_arena_pgsql_lab15

CREATE TABLE profiles (
    id SERIAL PRIMARY KEY,
    username TEXT NOT NULL,
    bio TEXT NOT NULL
);

INSERT INTO profiles (username, bio) VALUES
('alice', 'Database administrator and PostgreSQL enthusiast.'),
('bob', 'Security researcher focused on web application testing.'),
('charlie', 'Full-stack developer with a passion for open source.');

CREATE TABLE credentials (
    id SERIAL PRIMARY KEY,
    service TEXT NOT NULL,
    secret TEXT NOT NULL
);

INSERT INTO credentials (service, secret) VALUES
('internal_api', 'FLAG{pg_r3turn1ng_1ns3rt}'),
('backup_key', 'aes256-cbc-pkcs7-backup-2026'),
('smtp_relay', 'mailgun-api-key-xxxx-yyyy');

GRANT ALL ON ALL TABLES IN SCHEMA public TO sqli_arena;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;
