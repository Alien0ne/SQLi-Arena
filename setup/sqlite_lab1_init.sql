-- SQLite Lab 1: UNION - sqlite_master Enumeration
-- Tables: books, secret_keys

CREATE TABLE IF NOT EXISTS books (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    author TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS secret_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key_name TEXT NOT NULL,
    key_value TEXT NOT NULL
);

INSERT INTO books (title, author) VALUES ('The Art of SQL', 'Stephane Faroult');
INSERT INTO books (title, author) VALUES ('SQL Antipatterns', 'Bill Karwin');
INSERT INTO books (title, author) VALUES ('Database Internals', 'Alex Petrov');
INSERT INTO books (title, author) VALUES ('Learning SQL', 'Alan Beaulieu');
INSERT INTO books (title, author) VALUES ('High Performance MySQL', 'Baron Schwartz');

INSERT INTO secret_keys (key_name, key_value) VALUES ('master_flag', 'FLAG{sl_un10n_sql1t3_m4st3r}');
INSERT INTO secret_keys (key_name, key_value) VALUES ('api_key', 'sk-prod-29fba1c8e7d34a00');
INSERT INTO secret_keys (key_name, key_value) VALUES ('encryption_key', 'aes256-cbc-9f8e7d6c5b4a');
