-- =========================
-- SQLi-Arena: MSSQL Lab 16
-- INSERT: OUTPUT Clause
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab16')
    DROP DATABASE sqli_arena_mssql_lab16;
GO
CREATE DATABASE sqli_arena_mssql_lab16;
GO
USE sqli_arena_mssql_lab16;
GO
CREATE TABLE feedback (
    id INT IDENTITY(1,1) PRIMARY KEY,
    author VARCHAR(100) NOT NULL,
    comment VARCHAR(500) NOT NULL,
    submitted_at DATETIME DEFAULT GETDATE()
);
GO
INSERT INTO feedback (author, comment) VALUES
('Alice',   'Great service, very helpful support team!'),
('Bob',     'The dashboard could use some UX improvements'),
('Charlie', 'Found a bug in the export feature'),
('Diana',   'Love the new reporting tools');
GO

CREATE TABLE flags (
    id INT IDENTITY(1,1) PRIMARY KEY,
    flag VARCHAR(100) NOT NULL
);
GO
INSERT INTO flags (flag) VALUES ('FLAG{ms_1ns3rt_0utput_cl4us3}');
GO
