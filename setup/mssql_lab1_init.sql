-- =========================
-- SQLi-Arena: MSSQL Lab 1
-- UNION -- Basic String Injection
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab1')
    DROP DATABASE sqli_arena_mssql_lab1;
GO
CREATE DATABASE sqli_arena_mssql_lab1;
GO
USE sqli_arena_mssql_lab1;
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
('frank',   'fr4nk_bl4ze',              'frank@example.com'),
('grace',   'gr4ce_st4r',               'grace@example.com'),
('admin',   'FLAG{ms_un10n_b4s1c_str1ng}', 'admin@sqli-arena.local');
GO
