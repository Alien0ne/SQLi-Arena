-- Oracle Lab 12: RCE Java Stored Procedure
-- Tables: servers (id, hostname, ip_addr, status), rce_flags (id, flag)

CREATE TABLE servers (
    id       NUMBER PRIMARY KEY,
    hostname VARCHAR2(200),
    ip_addr  VARCHAR2(50),
    status   VARCHAR2(50)
);

INSERT INTO servers (id, hostname, ip_addr, status) VALUES (1, 'web-prod-01', '10.0.1.10', 'running');
INSERT INTO servers (id, hostname, ip_addr, status) VALUES (2, 'db-prod-01', '10.0.1.20', 'running');
INSERT INTO servers (id, hostname, ip_addr, status) VALUES (3, 'cache-prod-01', '10.0.1.30', 'running');
INSERT INTO servers (id, hostname, ip_addr, status) VALUES (4, 'web-staging-01', '10.0.2.10', 'stopped');
INSERT INTO servers (id, hostname, ip_addr, status) VALUES (5, 'monitor-01', '10.0.1.50', 'running');

CREATE TABLE rce_flags (
    id   NUMBER PRIMARY KEY,
    flag VARCHAR2(200)
);

INSERT INTO rce_flags (id, flag) VALUES (1, 'FLAG{or_j4v4_st0r3d_rc3}');

COMMIT;
EXIT;
