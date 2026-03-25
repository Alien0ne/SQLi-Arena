-- =========================
-- SQLi-Arena: MSSQL Lab 2
-- Error-Based: CONVERT / CAST
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab2')
    DROP DATABASE sqli_arena_mssql_lab2;
GO
CREATE DATABASE sqli_arena_mssql_lab2;
GO
USE sqli_arena_mssql_lab2;
GO
CREATE TABLE users (
    id INT IDENTITY(1,1) PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(100)
);
GO
INSERT INTO users (username, password, email) VALUES
('alice',   'alice_sunny_42',           'alice@example.com'),
('bob',     'b0bSecure!99',             'bob@example.com'),
('charlie', 'ch4rlie_thunder',          'charlie@example.com'),
('david',   'david_pass_2026',          'david@example.com'),
('eve',     'eVe_qu4ntum',              'eve@example.com'),
('admin',   'FLAG{ms_c0nv3rt_c4st_3rr0r}', 'admin@sqli-arena.local');
GO
