-- Oracle Lab 1: UNION FROM DUAL Required
-- Tables: users (id, username, email, password)

CREATE TABLE users (
    id       NUMBER PRIMARY KEY,
    username VARCHAR2(100) NOT NULL,
    email    VARCHAR2(200),
    password VARCHAR2(200)
);

INSERT INTO users (id, username, email, password) VALUES (1, 'admin', 'admin@company.com', 'FLAG{or_un10n_fr0m_du4l}');
INSERT INTO users (id, username, email, password) VALUES (2, 'jdoe', 'jdoe@company.com', 'p@ssw0rd123');
INSERT INTO users (id, username, email, password) VALUES (3, 'alice', 'alice@company.com', 'alice2024!');
INSERT INTO users (id, username, email, password) VALUES (4, 'bob', 'bob@company.com', 'b0bSecure#');
INSERT INTO users (id, username, email, password) VALUES (5, 'charlie', 'charlie@company.com', 'ch4rli3Pass');

COMMIT;
EXIT;
