-- Oracle Lab 3: Error-Based XMLType()
-- Tables: users (id, username, password, role)

CREATE TABLE users (
    id       NUMBER PRIMARY KEY,
    username VARCHAR2(100) NOT NULL,
    password VARCHAR2(200),
    role     VARCHAR2(50)
);

INSERT INTO users (id, username, password, role) VALUES (1, 'admin', 'FLAG{or_xmltyp3_3rr0r}', 'administrator');
INSERT INTO users (id, username, password, role) VALUES (2, 'jdoe', 'welcome123', 'user');
INSERT INTO users (id, username, password, role) VALUES (3, 'alice', 'alice2024!', 'user');
INSERT INTO users (id, username, password, role) VALUES (4, 'manager', 'mgr_s3cure', 'manager');
INSERT INTO users (id, username, password, role) VALUES (5, 'guest', 'guest', 'guest');

COMMIT;
EXIT;
