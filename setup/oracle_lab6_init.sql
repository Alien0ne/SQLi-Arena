-- Oracle Lab 6: Blind Boolean CASE + 1/0
-- Tables: articles (id, title, content, visible), secrets (id, secret)

CREATE TABLE articles (
    id      NUMBER PRIMARY KEY,
    title   VARCHAR2(300),
    content CLOB,
    visible NUMBER(1) DEFAULT 1
);

INSERT INTO articles (id, title, content, visible) VALUES (1, 'Getting Started with Oracle', 'This article covers the basics of Oracle database administration and SQL queries.', 1);
INSERT INTO articles (id, title, content, visible) VALUES (2, 'Advanced PL/SQL Programming', 'Learn how to write complex stored procedures and triggers in PL/SQL.', 1);
INSERT INTO articles (id, title, content, visible) VALUES (3, 'Oracle Security Best Practices', 'A comprehensive guide to securing your Oracle database deployment.', 1);
INSERT INTO articles (id, title, content, visible) VALUES (4, 'Hidden Draft Article', 'This article is not visible to the public yet.', 0);

CREATE TABLE secrets (
    id     NUMBER PRIMARY KEY,
    secret VARCHAR2(200)
);

INSERT INTO secrets (id, secret) VALUES (1, 'FLAG{or_bl1nd_c4s3_d1v0}');

COMMIT;
EXIT;
