-- =========================
-- SQLi-Arena: Oracle Lab 7
-- Blind Time -- DBMS_PIPE
-- =========================
-- Run as SYSDBA:
-- CREATE USER sqli_arena_oracle_lab7 IDENTIFIED BY sqli_arena_2026;
-- GRANT CONNECT, RESOURCE TO sqli_arena_oracle_lab7;
-- GRANT EXECUTE ON DBMS_PIPE TO sqli_arena_oracle_lab7;
-- Connect as lab user:

CREATE TABLE users (
    id       NUMBER PRIMARY KEY,
    username VARCHAR2(50)  NOT NULL,
    password VARCHAR2(100) NOT NULL,
    active   NUMBER(1)     DEFAULT 1
);

INSERT INTO users VALUES (1, 'admin',  'Sup3rS3cur3!', 1);
INSERT INTO users VALUES (2, 'guest',  'guest123',     1);
INSERT INTO users VALUES (3, 'banned', 'n0acc3ss',     0);

CREATE TABLE admin_tokens (
    id    NUMBER PRIMARY KEY,
    token VARCHAR2(100) NOT NULL
);

INSERT INTO admin_tokens VALUES (1, 'FLAG{or_dbms_p1p3_t1m3}');
COMMIT;
