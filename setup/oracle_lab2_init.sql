-- =========================
-- SQLi-Arena: Oracle Lab 2
-- UNION -- ALL_TABLES Enumeration
-- =========================
-- Run as SYSDBA:
-- CREATE USER sqli_arena_oracle_lab2 IDENTIFIED BY sqli_arena_2026;
-- GRANT CONNECT, RESOURCE TO sqli_arena_oracle_lab2;
-- Connect as lab user:

CREATE TABLE products (
    id          NUMBER PRIMARY KEY,
    name        VARCHAR2(100) NOT NULL,
    price       NUMBER(10,2)  NOT NULL,
    description VARCHAR2(200)
);

INSERT INTO products VALUES (1, 'Wireless Mouse',     29.99,  'Ergonomic wireless mouse');
INSERT INTO products VALUES (2, 'Mechanical Keyboard', 89.99, 'RGB backlit keyboard');
INSERT INTO products VALUES (3, 'USB Hub',            14.99,  '4-port USB 3.0 hub');
INSERT INTO products VALUES (4, 'Monitor Stand',      49.99,  'Adjustable monitor riser');

CREATE TABLE secret_flags (
    id    NUMBER PRIMARY KEY,
    flag  VARCHAR2(100) NOT NULL
);

INSERT INTO secret_flags VALUES (1, 'FLAG{or_4ll_t4bl3s_3num}');
COMMIT;
