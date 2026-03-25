DROP TABLE IF EXISTS notes;
DROP TABLE IF EXISTS vault;

CREATE TABLE notes (
    id INTEGER PRIMARY KEY,
    title TEXT,
    body TEXT
);

CREATE TABLE vault (
    id INTEGER PRIMARY KEY,
    vault_key TEXT
);

INSERT INTO notes (id, title, body) VALUES (1, 'Meeting Notes', 'Discuss Q3 roadmap with engineering team');
INSERT INTO notes (id, title, body) VALUES (2, 'TODO List', 'Fix login bug, update dependencies');
INSERT INTO notes (id, title, body) VALUES (3, 'Ideas', 'New caching strategy for API responses');

INSERT INTO vault (id, vault_key) VALUES (1, 'FLAG{sq_4tt4ch_db_wr1t3}');
