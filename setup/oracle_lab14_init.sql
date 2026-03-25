-- =========================
-- SQLi-Arena: Oracle Lab 14
-- Privilege Escalation -- DBA Grant
-- =========================
-- Run as SYSDBA:
-- CREATE USER sqli_arena_oracle_lab14 IDENTIFIED BY sqli_arena_2026;
-- GRANT CONNECT, RESOURCE TO sqli_arena_oracle_lab14;
-- Connect as lab user:

CREATE TABLE audit_log (
    id        NUMBER PRIMARY KEY,
    action    VARCHAR2(100) NOT NULL,
    performed VARCHAR2(50)  NOT NULL,
    timestamp VARCHAR2(30)  NOT NULL
);

INSERT INTO audit_log VALUES (1, 'LOGIN',           'admin',  '2026-03-20 08:15:00');
INSERT INTO audit_log VALUES (2, 'GRANT DBA',       'sysdba', '2026-03-20 09:30:00');
INSERT INTO audit_log VALUES (3, 'CREATE TABLE',    'admin',  '2026-03-20 10:00:00');
INSERT INTO audit_log VALUES (4, 'DROP USER temp',  'sysdba', '2026-03-21 14:22:00');

CREATE TABLE privesc_flags (
    id   NUMBER PRIMARY KEY,
    flag VARCHAR2(100) NOT NULL
);

INSERT INTO privesc_flags VALUES (1, 'FLAG{or_db4_gr4nt_pr1v3sc}');
COMMIT;
