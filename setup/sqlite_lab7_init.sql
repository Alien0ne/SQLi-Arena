-- SQLite Lab 7: ATTACH DATABASE - File Write
-- Tables: notes, vault

CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    body TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS vault (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vault_key TEXT NOT NULL
);

INSERT INTO notes (title, body) VALUES ('Meeting Notes', 'Discuss Q3 roadmap with the team');
INSERT INTO notes (title, body) VALUES ('TODO List', 'Fix login bug, update deps, write tests');
INSERT INTO notes (title, body) VALUES ('Reminder', 'Submit expense report by Friday');

INSERT INTO vault (vault_key) VALUES ('FLAG{sl_4tt4ch_db_wr1t3}');
INSERT INTO vault (vault_key) VALUES ('backup_encryption_key_x9f2');
INSERT INTO vault (vault_key) VALUES ('recovery_code_7h3k9m2p');
