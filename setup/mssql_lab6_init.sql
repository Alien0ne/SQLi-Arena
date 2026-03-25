-- =========================
-- SQLi-Arena: MSSQL Lab 6
-- Stacked Queries: Full Control
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab6')
    DROP DATABASE sqli_arena_mssql_lab6;
GO
CREATE DATABASE sqli_arena_mssql_lab6;
GO
USE sqli_arena_mssql_lab6;
GO
CREATE TABLE notes (
    id INT IDENTITY(1,1) PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    content VARCHAR(500) NOT NULL
);
GO
INSERT INTO notes (title, content) VALUES
('Meeting Notes',     'Discuss Q3 roadmap with engineering team'),
('Todo List',         'Fix bug #4521, deploy hotfix, update docs'),
('Project Ideas',     'ML-based anomaly detection for network traffic'),
('Grocery List',      'Milk, eggs, bread, coffee, bananas'),
('Workout Plan',      'Monday: chest, Wednesday: back, Friday: legs');
GO

CREATE TABLE flags (
    id INT IDENTITY(1,1) PRIMARY KEY,
    flag VARCHAR(100) NOT NULL
);
GO
INSERT INTO flags (flag) VALUES ('FLAG{ms_st4ck3d_full_ctrl}');
GO
