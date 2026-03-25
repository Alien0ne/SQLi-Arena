-- SQLite Lab 5: Blind Time-Based - RANDOMBLOB Heavy Query
-- Tables: sessions, admin_tokens

CREATE TABLE IF NOT EXISTS sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS admin_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    token TEXT NOT NULL
);

INSERT INTO sessions (session_id) VALUES ('sess_a1b2c3d4e5f6');
INSERT INTO sessions (session_id) VALUES ('sess_f7e8d9c0b1a2');
INSERT INTO sessions (session_id) VALUES ('sess_1234567890ab');
INSERT INTO sessions (session_id) VALUES ('sess_abcdef012345');
INSERT INTO sessions (session_id) VALUES ('sess_fedcba987654');

INSERT INTO admin_tokens (token) VALUES ('FLAG{sl_r4nd0m_bl0b_t1m3}');
INSERT INTO admin_tokens (token) VALUES ('tok_regular_user_abc');
INSERT INTO admin_tokens (token) VALUES ('tok_service_account_def');
