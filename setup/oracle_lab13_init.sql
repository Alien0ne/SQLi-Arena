-- Oracle Lab 13: RCE DBMS_SCHEDULER Job
-- Tables: tasks (id, task_name, priority, status, assigned_to), scheduler_flags (id, flag)

CREATE TABLE tasks (
    id          NUMBER PRIMARY KEY,
    task_name   VARCHAR2(200),
    priority    VARCHAR2(50),
    status      VARCHAR2(50),
    assigned_to VARCHAR2(100)
);

INSERT INTO tasks (id, task_name, priority, status, assigned_to) VALUES (1, 'Deploy v2.3.1', 'high', 'in_progress', 'devops');
INSERT INTO tasks (id, task_name, priority, status, assigned_to) VALUES (2, 'Update SSL Certs', 'critical', 'pending', 'devops');
INSERT INTO tasks (id, task_name, priority, status, assigned_to) VALUES (3, 'Database Backup', 'medium', 'completed', 'dba');
INSERT INTO tasks (id, task_name, priority, status, assigned_to) VALUES (4, 'Log Rotation Setup', 'low', 'pending', 'sysadmin');
INSERT INTO tasks (id, task_name, priority, status, assigned_to) VALUES (5, 'Security Patch', 'critical', 'in_progress', 'devops');

CREATE TABLE scheduler_flags (
    id   NUMBER PRIMARY KEY,
    flag VARCHAR2(200)
);

INSERT INTO scheduler_flags (id, flag) VALUES (1, 'FLAG{or_dbms_sch3d_rc3}');

COMMIT;
EXIT;
