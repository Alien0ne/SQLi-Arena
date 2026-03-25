-- Oracle Lab 14: Privilege Escalation DBA Grant
-- Tables: audit_log (id, action, performed, timestamp), privesc_flags (id, flag)

CREATE TABLE audit_log (
    id          NUMBER PRIMARY KEY,
    action      VARCHAR2(200),
    performed   VARCHAR2(100),
    timestamp   VARCHAR2(50)
);

INSERT INTO audit_log (id, action, performed, timestamp) VALUES (1, 'LOGIN', 'admin', '2025-03-20 08:15:00');
INSERT INTO audit_log (id, action, performed, timestamp) VALUES (2, 'CREATE_USER', 'admin', '2025-03-20 09:30:00');
INSERT INTO audit_log (id, action, performed, timestamp) VALUES (3, 'MODIFY_SCHEMA', 'dba', '2025-03-21 10:00:00');
INSERT INTO audit_log (id, action, performed, timestamp) VALUES (4, 'DELETE_RECORD', 'admin', '2025-03-21 14:22:00');
INSERT INTO audit_log (id, action, performed, timestamp) VALUES (5, 'EXPORT_DATA', 'analyst', '2025-03-22 11:45:00');

CREATE TABLE privesc_flags (
    id   NUMBER PRIMARY KEY,
    flag VARCHAR2(200)
);

INSERT INTO privesc_flags (id, flag) VALUES (1, 'FLAG{or_db4_gr4nt_pr1v3sc}');

COMMIT;
EXIT;
