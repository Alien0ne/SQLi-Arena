-- Oracle Lab 8: Blind Time-Based Heavy Query
-- Tables: sessions (session_id, username), api_keys (id, api_key)

CREATE TABLE sessions (
    session_id VARCHAR2(100) PRIMARY KEY,
    username   VARCHAR2(100)
);

INSERT INTO sessions (session_id, username) VALUES ('abc123def456', 'admin');
INSERT INTO sessions (session_id, username) VALUES ('xyz789ghi012', 'jdoe');
INSERT INTO sessions (session_id, username) VALUES ('sess_a1b2c3d4', 'alice');
INSERT INTO sessions (session_id, username) VALUES ('sess_e5f6g7h8', 'bob');

CREATE TABLE api_keys (
    id      NUMBER PRIMARY KEY,
    api_key VARCHAR2(200)
);

INSERT INTO api_keys (id, api_key) VALUES (1, 'FLAG{or_h34vy_qu3ry_t1m3}');

COMMIT;
EXIT;
