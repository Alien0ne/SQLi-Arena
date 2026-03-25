-- =========================
-- SQLi-Arena: MSSQL Lab 7
-- xp_cmdshell: OS Command Execution
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab7')
    DROP DATABASE sqli_arena_mssql_lab7;
GO
CREATE DATABASE sqli_arena_mssql_lab7;
GO
USE sqli_arena_mssql_lab7;
GO

CREATE TABLE documents (
    id INT IDENTITY(1,1) PRIMARY KEY,
    title NVARCHAR(200) NOT NULL,
    description NVARCHAR(500) NOT NULL
);
GO

INSERT INTO documents (title, description) VALUES
('Q3 Financial Report',    'Quarterly earnings and revenue analysis for Q3 2026'),
('Employee Handbook',      'Company policies, benefits, and code of conduct'),
('Network Architecture',   'Internal network topology and security zones'),
('Incident Response Plan', 'Procedures for handling security incidents'),
('Server Inventory',       'List of all production and staging servers');
GO

CREATE TABLE flags (
    id INT IDENTITY(1,1) PRIMARY KEY,
    flag NVARCHAR(100) NOT NULL
);
GO

INSERT INTO flags (flag) VALUES ('FLAG{ms_xp_cmdsh3ll_rc3}');
GO

-- Table for capturing xp_cmdshell output (used in attack chain)
CREATE TABLE cmd_output (
    line NVARCHAR(4000)
);
GO
