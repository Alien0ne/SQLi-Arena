-- =========================
-- SQLi-Arena: Oracle Lab 3
-- Error -- XMLType()
-- =========================
-- Run as SYSDBA:
-- CREATE USER sqli_arena_oracle_lab3 IDENTIFIED BY sqli_arena_2026;
-- GRANT CONNECT, RESOURCE TO sqli_arena_oracle_lab3;
-- Connect as lab user:

CREATE TABLE users (
    id        NUMBER PRIMARY KEY,
    username  VARCHAR2(50)  NOT NULL,
    password  VARCHAR2(100) NOT NULL,
    role      VARCHAR2(30)  NOT NULL
);

INSERT INTO users VALUES (1, 'admin',   'FLAG{or_xmltyp3_3rr0r}', 'administrator');
INSERT INTO users VALUES (2, 'editor',  'ed1t0r_p@ss',            'editor');
INSERT INTO users VALUES (3, 'viewer',  'v13w_0nly!',             'viewer');
COMMIT;
