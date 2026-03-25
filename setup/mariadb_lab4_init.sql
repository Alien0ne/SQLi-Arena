-- ================================
-- SQLi-Arena: MariaDB Lab 4
-- Oracle Mode -- PL/SQL Syntax
-- ================================

DROP DATABASE IF EXISTS sqli_arena_mariadb_lab4;
CREATE DATABASE sqli_arena_mariadb_lab4;
USE sqli_arena_mariadb_lab4;

DROP TABLE IF EXISTS oracle_data;
DROP TABLE IF EXISTS oracle_secrets;

CREATE TABLE oracle_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    value VARCHAR(100) NOT NULL
);

CREATE TABLE oracle_secrets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    secret_value VARCHAR(100) NOT NULL
);

INSERT INTO oracle_data (name, value) VALUES
('SQL_MODE', 'ORACLE'),
('PLSQL_COMPAT', 'ENABLED'),
('CURSOR_SUPPORT', 'BASIC'),
('EXCEPTION_HANDLING', 'PLSQL_STYLE'),
('PACKAGE_SUPPORT', 'LIMITED'),
('ANONYMOUS_BLOCKS', 'SUPPORTED');

INSERT INTO oracle_secrets (secret_value) VALUES
('FLAG{ma_0r4cl3_m0d3_plsql}');
