DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS flags;

CREATE TABLE products (
    id INTEGER PRIMARY KEY,
    name TEXT,
    price REAL
);

CREATE TABLE flags (
    id INTEGER PRIMARY KEY,
    flag_value TEXT
);

INSERT INTO products (id, name, price) VALUES (1, 'Wireless Mouse', 29.99);
INSERT INTO products (id, name, price) VALUES (2, 'Mechanical Keyboard', 89.99);
INSERT INTO products (id, name, price) VALUES (3, 'USB-C Hub', 49.99);
INSERT INTO products (id, name, price) VALUES (4, 'Monitor Stand', 34.99);
INSERT INTO products (id, name, price) VALUES (5, 'Laptop Sleeve', 19.99);

INSERT INTO flags (id, flag_value) VALUES (1, 'FLAG{sq_3rr0r_l04d_3xt}');
