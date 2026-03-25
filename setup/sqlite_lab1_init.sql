DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS secret_keys;

CREATE TABLE books (
    id INTEGER PRIMARY KEY,
    title TEXT,
    author TEXT
);

CREATE TABLE secret_keys (
    id INTEGER PRIMARY KEY,
    key_name TEXT,
    key_value TEXT
);

INSERT INTO books (id, title, author) VALUES (1, 'The Art of SQL', 'John Doe');
INSERT INTO books (id, title, author) VALUES (2, 'Database Internals', 'Jane Smith');
INSERT INTO books (id, title, author) VALUES (3, 'SQL Antipatterns', 'Bill Karwin');
INSERT INTO books (id, title, author) VALUES (4, 'Learning SQL', 'Alan Beaulieu');
INSERT INTO books (id, title, author) VALUES (5, 'SQL Cookbook', 'Anthony Molinaro');

INSERT INTO secret_keys (id, key_name, key_value) VALUES (1, 'api_key', 'sk-1234567890abcdef');
INSERT INTO secret_keys (id, key_name, key_value) VALUES (2, 'master_flag', 'FLAG{sq_m4st3r_3num3r4t10n}');
INSERT INTO secret_keys (id, key_name, key_value) VALUES (3, 'encryption_key', 'aes-256-cbc-random');
