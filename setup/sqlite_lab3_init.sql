-- SQLite Lab 3: Error-Based - load_extension() Boolean Oracle
-- Tables: products, flags

CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    price REAL NOT NULL
);

CREATE TABLE IF NOT EXISTS flags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    flag_value TEXT NOT NULL
);

INSERT INTO products (name, price) VALUES ('Wireless Mouse', 29.99);
INSERT INTO products (name, price) VALUES ('USB Keyboard', 49.95);
INSERT INTO products (name, price) VALUES ('Monitor Stand', 39.50);
INSERT INTO products (name, price) VALUES ('Webcam HD', 79.99);
INSERT INTO products (name, price) VALUES ('Desk Lamp', 24.95);

INSERT INTO flags (flag_value) VALUES ('FLAG{sl_l04d_3xt_3rr0r}');
INSERT INTO flags (flag_value) VALUES ('not_the_flag_1');
INSERT INTO flags (flag_value) VALUES ('not_the_flag_2');
