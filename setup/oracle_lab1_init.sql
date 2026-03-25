-- =========================
-- SQLi-Arena: Oracle Lab 1
-- UNION -- FROM DUAL Required
-- =========================
-- Run as SYSDBA:
-- CREATE USER sqli_arena_oracle_lab1 IDENTIFIED BY sqli_arena_2026;
-- GRANT CONNECT, RESOURCE TO sqli_arena_oracle_lab1;
-- Connect as lab user:

CREATE TABLE users (
    id        NUMBER PRIMARY KEY,
    username  VARCHAR2(50)  NOT NULL,
    password  VARCHAR2(100) NOT NULL,
    email     VARCHAR2(150) NOT NULL
);

INSERT INTO users VALUES (1, 'admin',   'FLAG{or_un10n_fr0m_du4l}', 'admin@sqli-arena.local');
INSERT INTO users VALUES (2, 'jdoe',    'p@ssw0rd123',              'jdoe@sqli-arena.local');
INSERT INTO users VALUES (3, 'alice',   'alice2024!',               'alice@sqli-arena.local');
INSERT INTO users VALUES (4, 'bob',     'bobSecure#1',              'bob@sqli-arena.local');
INSERT INTO users VALUES (5, 'charlie', 'charL13!!',                'charlie@sqli-arena.local');
COMMIT;
