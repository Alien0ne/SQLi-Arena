-- Oracle Lab 10: Out-of-Band HTTPURITYPE / XXE
-- Tables: orders (id, product, total_price, status, customer), internal_flags (id, flag)

CREATE TABLE orders (
    id          NUMBER PRIMARY KEY,
    product     VARCHAR2(200),
    total_price NUMBER(10,2),
    status      VARCHAR2(50),
    customer    VARCHAR2(200)
);

INSERT INTO orders (id, product, total_price, status, customer) VALUES (1, 'Laptop Pro 15', 1299.99, 'shipped', 'John Doe');
INSERT INTO orders (id, product, total_price, status, customer) VALUES (2, 'Wireless Earbuds', 79.99, 'delivered', 'John Doe');
INSERT INTO orders (id, product, total_price, status, customer) VALUES (3, 'Standing Desk', 499.00, 'processing', 'Jane Smith');
INSERT INTO orders (id, product, total_price, status, customer) VALUES (4, 'Monitor 27 4K', 349.99, 'shipped', 'Alice Brown');
INSERT INTO orders (id, product, total_price, status, customer) VALUES (5, 'Keyboard Case', 45.00, 'delivered', 'Bob Wilson');

CREATE TABLE internal_flags (
    id   NUMBER PRIMARY KEY,
    flag VARCHAR2(200)
);

INSERT INTO internal_flags (id, flag) VALUES (1, 'FLAG{or_httpur1typ3_xx3}');

COMMIT;
EXIT;
