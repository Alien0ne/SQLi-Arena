-- =========================
-- SQLi-Arena: MSSQL Lab 4
-- Blind Boolean: SUBSTRING + ASCII
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab4')
    DROP DATABASE sqli_arena_mssql_lab4;
GO
CREATE DATABASE sqli_arena_mssql_lab4;
GO
USE sqli_arena_mssql_lab4;
GO
CREATE TABLE employees (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    department VARCHAR(50) NOT NULL,
    salary DECIMAL(10,2) NOT NULL
);
GO
INSERT INTO employees (name, department, salary) VALUES
('John Smith',    'Engineering',  95000.00),
('Jane Doe',     'Marketing',    72000.00),
('Bob Wilson',   'Engineering',  88000.00),
('Alice Brown',  'Sales',        67000.00),
('Charlie Lee',  'Engineering', 102000.00);
GO

CREATE TABLE secrets (
    id INT IDENTITY(1,1) PRIMARY KEY,
    secret VARCHAR(100) NOT NULL
);
GO
INSERT INTO secrets (secret) VALUES ('FLAG{ms_bl1nd_b00l_4sc11}');
GO
