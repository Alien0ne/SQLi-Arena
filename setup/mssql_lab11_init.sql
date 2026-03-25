-- =========================
-- SQLi-Arena: MSSQL Lab 11
-- OOB: xp_dirtree DNS Exfiltration
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab11')
    DROP DATABASE sqli_arena_mssql_lab11;
GO
CREATE DATABASE sqli_arena_mssql_lab11;
GO
USE sqli_arena_mssql_lab11;
GO

CREATE TABLE tickets (
    id INT IDENTITY(1,1) PRIMARY KEY,
    title NVARCHAR(200) NOT NULL,
    status NVARCHAR(20) NOT NULL,
    priority NVARCHAR(10) NOT NULL,
    created_at DATETIME DEFAULT GETDATE()
);
GO

INSERT INTO tickets (title, status, priority) VALUES
('Login page bug',          'open',        'high'),
('Dashboard slow loading',  'open',        'medium'),
('Password reset broken',   'closed',      'high'),
('Export CSV timeout',       'open',        'low'),
('Mobile layout issues',    'in-progress', 'medium'),
('API rate limiting',        'open',        'high'),
('Search not working',      'closed',      'medium');
GO

CREATE TABLE flags (
    id INT IDENTITY(1,1) PRIMARY KEY,
    flag NVARCHAR(100) NOT NULL
);
GO

INSERT INTO flags (flag) VALUES ('FLAG{ms_xp_d1rtr33_00b}');
GO
