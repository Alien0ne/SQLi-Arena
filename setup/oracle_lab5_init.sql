-- =========================
-- SQLi-Arena: Oracle Lab 5
-- Error -- CTXSYS.DRITHSX.SN
-- =========================
-- Run as SYSDBA:
-- CREATE USER sqli_arena_oracle_lab5 IDENTIFIED BY sqli_arena_2026;
-- GRANT CONNECT, RESOURCE TO sqli_arena_oracle_lab5;
-- Connect as lab user:

CREATE TABLE employees (
    id         NUMBER PRIMARY KEY,
    name       VARCHAR2(100) NOT NULL,
    department VARCHAR2(50)  NOT NULL,
    salary     NUMBER(10,2)  NOT NULL
);

INSERT INTO employees VALUES (1, 'Alice Johnson',  'Engineering', 95000.00);
INSERT INTO employees VALUES (2, 'Bob Smith',      'Marketing',   72000.00);
INSERT INTO employees VALUES (3, 'Carol Williams', 'Finance',     88000.00);

CREATE TABLE admin_secrets (
    id     NUMBER PRIMARY KEY,
    secret VARCHAR2(100) NOT NULL
);

INSERT INTO admin_secrets VALUES (1, 'FLAG{or_ctxsys_dr1thsx}');
COMMIT;
