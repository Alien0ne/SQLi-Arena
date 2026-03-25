-- =========================
-- SQLi-Arena: Oracle Lab 4
-- Error -- UTL_INADDR
-- =========================
-- Run as SYSDBA:
-- CREATE USER sqli_arena_oracle_lab4 IDENTIFIED BY sqli_arena_2026;
-- GRANT CONNECT, RESOURCE TO sqli_arena_oracle_lab4;
-- GRANT EXECUTE ON UTL_INADDR TO sqli_arena_oracle_lab4;
-- Connect as lab user:

CREATE TABLE users (
    id        NUMBER PRIMARY KEY,
    username  VARCHAR2(50)  NOT NULL,
    password  VARCHAR2(100) NOT NULL,
    email     VARCHAR2(150) NOT NULL
);

INSERT INTO users VALUES (1, 'admin',   'FLAG{or_utl_1n4ddr_3rr0r}', 'admin@sqli-arena.local');
INSERT INTO users VALUES (2, 'jdoe',    'jd0e_s3cur3!',              'jdoe@sqli-arena.local');
INSERT INTO users VALUES (3, 'analyst', 'an4lyst_2026',              'analyst@sqli-arena.local');
COMMIT;
