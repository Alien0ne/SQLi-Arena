-- SQLite Lab 4: Blind Boolean - hex(substr()) Extraction
-- Tables: members, secrets

CREATE TABLE IF NOT EXISTS members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS secrets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    flag_value TEXT NOT NULL
);

INSERT INTO members (username, is_active) VALUES ('admin', 1);
INSERT INTO members (username, is_active) VALUES ('jdoe', 1);
INSERT INTO members (username, is_active) VALUES ('alice', 1);
INSERT INTO members (username, is_active) VALUES ('bob', 0);
INSERT INTO members (username, is_active) VALUES ('charlie', 1);

INSERT INTO secrets (flag_value) VALUES ('FLAG{sl_bl1nd_h3x_substr}');
INSERT INTO secrets (flag_value) VALUES ('decoy_secret_value_xyz');
INSERT INTO secrets (flag_value) VALUES ('another_decoy_value_999');
