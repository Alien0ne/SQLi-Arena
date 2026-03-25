-- =========================
-- SQLi-Arena: MSSQL Lab 8
-- sp_OACreate: COM Object RCE
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab8')
    DROP DATABASE sqli_arena_mssql_lab8;
GO
CREATE DATABASE sqli_arena_mssql_lab8;
GO
USE sqli_arena_mssql_lab8;
GO
CREATE TABLE reports (
    id INT IDENTITY(1,1) PRIMARY KEY,
    report_name VARCHAR(200) NOT NULL,
    summary VARCHAR(500) NOT NULL,
    created_at DATETIME DEFAULT GETDATE()
);
GO
INSERT INTO reports (report_name, summary) VALUES
('Q1 Sales Report',      'Total revenue: $2.4M, 15% increase YoY'),
('Q2 Sales Report',      'Total revenue: $2.8M, 12% increase YoY'),
('Marketing Campaign',   'Social media engagement up 340% after launch'),
('Customer Satisfaction', 'NPS score improved from 42 to 67'),
('Server Uptime Report',  '99.97% uptime across all production servers');
GO

CREATE TABLE flags (
    id INT IDENTITY(1,1) PRIMARY KEY,
    flag VARCHAR(100) NOT NULL
);
GO
INSERT INTO flags (flag) VALUES ('FLAG{ms_sp_04cr34t3_rc3}');
GO
