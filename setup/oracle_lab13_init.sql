-- =========================
-- SQLi-Arena: Oracle Lab 13
-- RCE -- DBMS_SCHEDULER Job
-- =========================
-- Run as SYSDBA:
-- CREATE USER sqli_arena_oracle_lab13 IDENTIFIED BY sqli_arena_2026;
-- GRANT CONNECT, RESOURCE, CREATE JOB TO sqli_arena_oracle_lab13;
-- Connect as lab user:

CREATE TABLE tasks (
    id          NUMBER PRIMARY KEY,
    task_name   VARCHAR2(100) NOT NULL,
    assigned_to VARCHAR2(50)  NOT NULL,
    priority    VARCHAR2(20)  NOT NULL,
    status      VARCHAR2(20)  NOT NULL
);

INSERT INTO tasks VALUES (1, 'Deploy v2.1',        'devops',  'high',   'pending');
INSERT INTO tasks VALUES (2, 'Patch CVE-2025-001', 'infosec', 'critical', 'in_progress');
INSERT INTO tasks VALUES (3, 'Backup rotation',    'dba',     'medium', 'completed');

CREATE TABLE scheduler_flags (
    id   NUMBER PRIMARY KEY,
    flag VARCHAR2(100) NOT NULL
);

INSERT INTO scheduler_flags VALUES (1, 'FLAG{or_dbms_sch3d_rc3}');
COMMIT;
