-- =========================
-- SQLi-Arena: Oracle Lab 6
-- Blind Boolean -- CASE + 1/0
-- =========================
-- Run as SYSDBA:
-- CREATE USER sqli_arena_oracle_lab6 IDENTIFIED BY sqli_arena_2026;
-- GRANT CONNECT, RESOURCE TO sqli_arena_oracle_lab6;
-- Connect as lab user:

CREATE TABLE articles (
    id      NUMBER PRIMARY KEY,
    title   VARCHAR2(200) NOT NULL,
    content CLOB          NOT NULL,
    visible NUMBER(1)     DEFAULT 1
);

INSERT INTO articles VALUES (1, 'Welcome to Our Blog',   'This is the first post on our blog.', 1);
INSERT INTO articles VALUES (2, 'Oracle Tips and Tricks', 'Learn about Oracle database features.', 1);
INSERT INTO articles VALUES (3, 'Hidden Article',         'This article is not publicly visible.', 0);

CREATE TABLE secrets (
    id     NUMBER PRIMARY KEY,
    secret VARCHAR2(100) NOT NULL
);

INSERT INTO secrets VALUES (1, 'FLAG{or_bl1nd_c4s3_d1v0}');
COMMIT;
