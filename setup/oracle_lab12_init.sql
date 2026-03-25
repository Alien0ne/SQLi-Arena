-- =========================
-- SQLi-Arena: Oracle Lab 12
-- RCE -- Java Stored Procedure
-- =========================
-- Run as SYSDBA:
-- CREATE USER sqli_arena_oracle_lab12 IDENTIFIED BY sqli_arena_2026;
-- GRANT CONNECT, RESOURCE, CREATE PROCEDURE TO sqli_arena_oracle_lab12;
-- GRANT JAVAUSERPRIV TO sqli_arena_oracle_lab12;
-- Connect as lab user:

CREATE TABLE servers (
    id       NUMBER PRIMARY KEY,
    hostname VARCHAR2(100) NOT NULL,
    ip_addr  VARCHAR2(45)  NOT NULL,
    status   VARCHAR2(20)  NOT NULL
);

INSERT INTO servers VALUES (1, 'web-01.internal',  '10.10.1.1', 'running');
INSERT INTO servers VALUES (2, 'db-01.internal',   '10.10.1.2', 'running');
INSERT INTO servers VALUES (3, 'app-01.internal',  '10.10.1.3', 'stopped');

CREATE TABLE rce_flags (
    id   NUMBER PRIMARY KEY,
    flag VARCHAR2(100) NOT NULL
);

INSERT INTO rce_flags VALUES (1, 'FLAG{or_j4v4_st0r3d_rc3}');
COMMIT;
