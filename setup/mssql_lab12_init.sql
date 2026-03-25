-- =========================
-- SQLi-Arena: MSSQL Lab 12
-- OOB: fn_xe_file + UNC Path
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab12')
    DROP DATABASE sqli_arena_mssql_lab12;
GO
CREATE DATABASE sqli_arena_mssql_lab12;
GO
USE sqli_arena_mssql_lab12;
GO

CREATE TABLE metrics (
    id INT IDENTITY(1,1) PRIMARY KEY,
    hostname NVARCHAR(50) NOT NULL,
    cpu_usage DECIMAL(5,2) NOT NULL,
    memory_usage DECIMAL(5,2) NOT NULL,
    disk_io DECIMAL(10,2) NOT NULL,
    recorded_at DATETIME DEFAULT GETDATE()
);
GO

INSERT INTO metrics (hostname, cpu_usage, memory_usage, disk_io) VALUES
('web-01',   45.20, 72.10, 125.50),
('web-02',   38.80, 68.30,  98.20),
('db-01',    82.50, 91.00, 450.00),
('db-02',    55.30, 78.60, 320.80),
('cache-01', 12.10, 45.20,  15.30);
GO

CREATE TABLE flags (
    id INT IDENTITY(1,1) PRIMARY KEY,
    flag NVARCHAR(100) NOT NULL
);
GO

INSERT INTO flags (flag) VALUES ('FLAG{ms_x3_f1l3_t4rg3t}');
GO
