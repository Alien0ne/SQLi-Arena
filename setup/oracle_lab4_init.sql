-- Oracle Lab 4: Error-Based UTL_INADDR
-- Tables: users (id, username, email, password)

CREATE TABLE users (
    id       NUMBER PRIMARY KEY,
    username VARCHAR2(100) NOT NULL,
    email    VARCHAR2(200),
    password VARCHAR2(200)
);

INSERT INTO users (id, username, email, password) VALUES (1, 'admin', 'admin@corp.local', 'FLAG{or_utl_1n4ddr_3rr0r}');
INSERT INTO users (id, username, email, password) VALUES (2, 'jsmith', 'jsmith@corp.local', 'smith2024!');
INSERT INTO users (id, username, email, password) VALUES (3, 'mary', 'mary@corp.local', 'm4ryP@ss');
INSERT INTO users (id, username, email, password) VALUES (4, 'devuser', 'devuser@corp.local', 'd3v_s3cur3');

COMMIT;
EXIT;
