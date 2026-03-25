-- =========================
-- SQLi-Arena: MSSQL Lab 5
-- Blind Time-Based: WAITFOR DELAY
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab5')
    DROP DATABASE sqli_arena_mssql_lab5;
GO
CREATE DATABASE sqli_arena_mssql_lab5;
GO
USE sqli_arena_mssql_lab5;
GO
CREATE TABLE audit_log (
    id INT IDENTITY(1,1) PRIMARY KEY,
    event VARCHAR(200) NOT NULL,
    timestamp DATETIME DEFAULT GETDATE(),
    user_id INT
);
GO
INSERT INTO audit_log (event, user_id) VALUES
('User login successful', 1),
('User login failed', 2),
('Password changed', 1),
('File uploaded', 3),
('User logout', 1),
('Admin panel accessed', 1),
('User login successful', 4),
('Settings modified', 2),
('Report generated', 3),
('User login failed', 5);
GO

CREATE TABLE secrets (
    id INT IDENTITY(1,1) PRIMARY KEY,
    secret VARCHAR(100) NOT NULL
);
GO
INSERT INTO secrets (secret) VALUES ('FLAG{ms_w41tf0r_d3l4y_bl1nd}');
GO
