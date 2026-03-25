-- =========================
-- SQLi-Arena: Oracle Lab 10
-- OOB -- HTTPURITYPE / XXE
-- =========================
-- Run as SYSDBA:
-- CREATE USER sqli_arena_oracle_lab10 IDENTIFIED BY sqli_arena_2026;
-- GRANT CONNECT, RESOURCE TO sqli_arena_oracle_lab10;
-- GRANT EXECUTE ON HTTPURITYPE TO sqli_arena_oracle_lab10;
-- Connect as lab user:

CREATE TABLE orders (
    id          NUMBER PRIMARY KEY,
    customer    VARCHAR2(100) NOT NULL,
    product     VARCHAR2(100) NOT NULL,
    total_price NUMBER(10,2)  NOT NULL,
    status      VARCHAR2(30)  NOT NULL
);

INSERT INTO orders VALUES (1, 'John Doe',     'Laptop Pro',       1299.99, 'shipped');
INSERT INTO orders VALUES (2, 'Jane Smith',   'Wireless Headset',  149.99, 'processing');
INSERT INTO orders VALUES (3, 'Bob Wilson',   'USB-C Dock',        79.99,  'delivered');
INSERT INTO orders VALUES (4, 'Alice Brown',  'Monitor 27in',     349.99,  'shipped');

CREATE TABLE internal_flags (
    id   NUMBER PRIMARY KEY,
    flag VARCHAR2(100) NOT NULL
);

INSERT INTO internal_flags VALUES (1, 'FLAG{or_httpur1typ3_xx3}');
COMMIT;
