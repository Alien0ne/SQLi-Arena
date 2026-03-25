DROP TABLE IF EXISTS members;
DROP TABLE IF EXISTS secrets;

CREATE TABLE members (
    id INTEGER PRIMARY KEY,
    username TEXT,
    is_active INTEGER
);

CREATE TABLE secrets (
    id INTEGER PRIMARY KEY,
    flag_value TEXT
);

INSERT INTO members (id, username, is_active) VALUES (1, 'admin', 1);
INSERT INTO members (id, username, is_active) VALUES (2, 'guest', 1);
INSERT INTO members (id, username, is_active) VALUES (3, 'moderator', 1);
INSERT INTO members (id, username, is_active) VALUES (4, 'user123', 0);
INSERT INTO members (id, username, is_active) VALUES (5, 'testuser', 1);

INSERT INTO secrets (id, flag_value) VALUES (1, 'FLAG{sq_bl1nd_h3x_substr}');
