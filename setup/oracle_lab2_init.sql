-- Oracle Lab 2: UNION ALL_TABLES Enumeration
-- Tables: products (id, name, price, description), hidden_table (id, secret)

CREATE TABLE products (
    id          NUMBER PRIMARY KEY,
    name        VARCHAR2(200) NOT NULL,
    price       NUMBER(10,2),
    description VARCHAR2(500)
);

INSERT INTO products (id, name, price, description) VALUES (1, 'Wireless Mouse', 29.99, 'Ergonomic wireless mouse with USB receiver');
INSERT INTO products (id, name, price, description) VALUES (2, 'Mechanical Keyboard', 89.50, 'RGB mechanical keyboard with Cherry MX switches');
INSERT INTO products (id, name, price, description) VALUES (3, 'USB-C Hub', 45.00, '7-in-1 USB-C hub with HDMI and Ethernet');
INSERT INTO products (id, name, price, description) VALUES (4, 'Monitor Stand', 34.99, 'Adjustable aluminum monitor stand');
INSERT INTO products (id, name, price, description) VALUES (5, 'Webcam HD', 59.99, '1080p HD webcam with built-in microphone');

CREATE TABLE hidden_table (
    id     NUMBER PRIMARY KEY,
    secret VARCHAR2(200)
);

INSERT INTO hidden_table (id, secret) VALUES (1, 'FLAG{or_4ll_t4bl3s_3num}');

COMMIT;
EXIT;
