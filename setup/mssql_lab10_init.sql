-- =========================
-- SQLi-Arena: MSSQL Lab 10
-- File Read: OPENROWSET(BULK)
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab10')
    DROP DATABASE sqli_arena_mssql_lab10;
GO
CREATE DATABASE sqli_arena_mssql_lab10;
GO
USE sqli_arena_mssql_lab10;
GO

CREATE TABLE files (
    id INT IDENTITY(1,1) PRIMARY KEY,
    filename NVARCHAR(200) NOT NULL,
    filesize INT NOT NULL,
    uploaded_by NVARCHAR(50) NOT NULL
);
GO

INSERT INTO files (filename, filesize, uploaded_by) VALUES
('report_q3_2026.pdf',    2458000, 'admin'),
('budget_forecast.xlsx',  1245000, 'finance'),
('network_diagram.png',    890000, 'infra'),
('employee_list.csv',      124000, 'hr'),
('security_audit.docx',   3200000, 'security');
GO

CREATE TABLE flags (
    id INT IDENTITY(1,1) PRIMARY KEY,
    flag NVARCHAR(100) NOT NULL
);
GO

INSERT INTO flags (flag) VALUES ('FLAG{ms_0p3nr0ws3t_bulk}');
GO
