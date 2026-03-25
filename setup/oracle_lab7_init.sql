-- Oracle Lab 7: Blind Time-Based DBMS_PIPE
-- Tables: users (id, username, password, active), admin_tokens (id, token)

CREATE TABLE users (
    id       NUMBER PRIMARY KEY,
    username VARCHAR2(100) NOT NULL,
    password VARCHAR2(200),
    active   NUMBER(1) DEFAULT 1
);

INSERT INTO users (id, username, password, active) VALUES (1, 'admin', 'adm1nP@ss!', 1);
INSERT INTO users (id, username, password, active) VALUES (2, 'operator', '0per@t0r', 1);
INSERT INTO users (id, username, password, active) VALUES (3, 'viewer', 'v13wOnly', 1);
INSERT INTO users (id, username, password, active) VALUES (4, 'disabled', 'n0acc3ss', 0);

CREATE TABLE admin_tokens (
    id    NUMBER PRIMARY KEY,
    token VARCHAR2(200)
);

INSERT INTO admin_tokens (id, token) VALUES (1, 'FLAG{or_dbms_p1p3_t1m3}');

COMMIT;
EXIT;
