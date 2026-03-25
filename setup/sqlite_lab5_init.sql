DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS admin_tokens;

CREATE TABLE sessions (
    id INTEGER PRIMARY KEY,
    session_id TEXT
);

CREATE TABLE admin_tokens (
    id INTEGER PRIMARY KEY,
    token TEXT
);

INSERT INTO sessions (id, session_id) VALUES (1, 'sess_abc123def456');
INSERT INTO sessions (id, session_id) VALUES (2, 'sess_xyz789ghi012');
INSERT INTO sessions (id, session_id) VALUES (3, 'sess_mno345pqr678');

INSERT INTO admin_tokens (id, token) VALUES (1, 'FLAG{sq_r4nd0mbl0b_t1m3}');
