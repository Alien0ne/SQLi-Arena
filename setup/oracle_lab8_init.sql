-- =========================
-- SQLi-Arena: Oracle Lab 8
-- Blind Time -- Heavy Query
-- =========================
-- Run as SYSDBA:
-- CREATE USER sqli_arena_oracle_lab8 IDENTIFIED BY sqli_arena_2026;
-- GRANT CONNECT, RESOURCE TO sqli_arena_oracle_lab8;
-- Connect as lab user:

CREATE TABLE sessions (
    id         NUMBER PRIMARY KEY,
    session_id VARCHAR2(64) NOT NULL,
    username   VARCHAR2(50) NOT NULL,
    ip_addr    VARCHAR2(45) NOT NULL
);

INSERT INTO sessions VALUES (1, 'abc123def456', 'admin',  '10.0.0.1');
INSERT INTO sessions VALUES (2, 'xyz789uvw012', 'guest',  '10.0.0.2');
INSERT INTO sessions VALUES (3, 'mnp345qrs678', 'editor', '10.0.0.3');

CREATE TABLE api_keys (
    id      NUMBER PRIMARY KEY,
    api_key VARCHAR2(100) NOT NULL
);

INSERT INTO api_keys VALUES (1, 'FLAG{or_h34vy_qu3ry_t1m3}');
COMMIT;
