-- =========================
-- SQLi-Arena: Oracle Lab 9
-- OOB -- UTL_HTTP.REQUEST
-- =========================
-- Run as SYSDBA:
-- CREATE USER sqli_arena_oracle_lab9 IDENTIFIED BY sqli_arena_2026;
-- GRANT CONNECT, RESOURCE TO sqli_arena_oracle_lab9;
-- GRANT EXECUTE ON UTL_HTTP TO sqli_arena_oracle_lab9;
-- BEGIN DBMS_NETWORK_ACL_ADMIN.CREATE_ACL(...); END; -- for network access
-- Connect as lab user:

CREATE TABLE documents (
    id      NUMBER PRIMARY KEY,
    title   VARCHAR2(200) NOT NULL,
    author  VARCHAR2(100) NOT NULL,
    content CLOB          NOT NULL
);

INSERT INTO documents VALUES (1, 'Annual Report 2025',  'Finance Team', 'Financial performance summary...');
INSERT INTO documents VALUES (2, 'Product Roadmap',     'Engineering',  'Upcoming features and timelines...');
INSERT INTO documents VALUES (3, 'Security Policy',     'InfoSec',      'Internal security guidelines...');

CREATE TABLE oob_secrets (
    id     NUMBER PRIMARY KEY,
    secret VARCHAR2(100) NOT NULL
);

INSERT INTO oob_secrets VALUES (1, 'FLAG{or_utl_http_00b}');
COMMIT;
