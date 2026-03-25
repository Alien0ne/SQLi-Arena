-- Oracle Lab 9: Out-of-Band UTL_HTTP.REQUEST
-- Tables: documents (id, title, author), oob_secrets (id, secret)

CREATE TABLE documents (
    id     NUMBER PRIMARY KEY,
    title  VARCHAR2(300),
    author VARCHAR2(200)
);

INSERT INTO documents (id, title, author) VALUES (1, 'Q4 Financial Report', 'Finance Team');
INSERT INTO documents (id, title, author) VALUES (2, 'Engineering Roadmap 2025', 'Engineering');
INSERT INTO documents (id, title, author) VALUES (3, 'HR Policy Update', 'HR Department');
INSERT INTO documents (id, title, author) VALUES (4, 'Security Audit Results', 'Security Team');
INSERT INTO documents (id, title, author) VALUES (5, 'Marketing Campaign Plan', 'Marketing');

CREATE TABLE oob_secrets (
    id     NUMBER PRIMARY KEY,
    secret VARCHAR2(200)
);

INSERT INTO oob_secrets (id, secret) VALUES (1, 'FLAG{or_utl_http_00b}');

COMMIT;
EXIT;
