-- SQLite Lab 10: WAF Bypass - No Standard Keywords
-- Tables: search_data, hidden_flags

CREATE TABLE IF NOT EXISTS search_data (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    keyword TEXT NOT NULL,
    description TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS hidden_flags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    flag_value TEXT NOT NULL
);

INSERT INTO search_data (keyword, description) VALUES ('sqlite', 'A lightweight embedded relational database engine');
INSERT INTO search_data (keyword, description) VALUES ('injection', 'A code injection technique exploiting SQL queries');
INSERT INTO search_data (keyword, description) VALUES ('firewall', 'A network security system monitoring traffic');
INSERT INTO search_data (keyword, description) VALUES ('encryption', 'The process of encoding data for confidentiality');
INSERT INTO search_data (keyword, description) VALUES ('authentication', 'Verifying the identity of a user or process');

INSERT INTO hidden_flags (flag_value) VALUES ('FLAG{sl_w4f_n3st3d_byp4ss}');
INSERT INTO hidden_flags (flag_value) VALUES ('decoy_flag_not_real_001');
INSERT INTO hidden_flags (flag_value) VALUES ('decoy_flag_not_real_002');
