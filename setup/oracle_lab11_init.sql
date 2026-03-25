-- =========================
-- SQLi-Arena: Oracle Lab 11
-- OOB -- DBMS_LDAP.INIT
-- =========================
-- Run as SYSDBA:
-- CREATE USER sqli_arena_oracle_lab11 IDENTIFIED BY sqli_arena_2026;
-- GRANT CONNECT, RESOURCE TO sqli_arena_oracle_lab11;
-- GRANT EXECUTE ON DBMS_LDAP TO sqli_arena_oracle_lab11;
-- Connect as lab user:

CREATE TABLE inventory (
    id       NUMBER PRIMARY KEY,
    item     VARCHAR2(100) NOT NULL,
    quantity NUMBER        NOT NULL,
    location VARCHAR2(50)  NOT NULL
);

INSERT INTO inventory VALUES (1, 'Server Rack A1', 5,   'DC-East');
INSERT INTO inventory VALUES (2, 'Switch 48-Port', 12,  'DC-West');
INSERT INTO inventory VALUES (3, 'UPS 3000VA',     3,   'DC-East');
INSERT INTO inventory VALUES (4, 'Patch Panel',    20,  'DC-West');

CREATE TABLE ldap_secrets (
    id     NUMBER PRIMARY KEY,
    secret VARCHAR2(100) NOT NULL
);

INSERT INTO ldap_secrets VALUES (1, 'FLAG{or_dbms_ld4p_00b}');
COMMIT;
