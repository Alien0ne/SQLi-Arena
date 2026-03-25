-- =========================
-- SQLi-Arena: MSSQL Lab 17
-- WAF Bypass: Unicode Normalization
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab17')
    DROP DATABASE sqli_arena_mssql_lab17;
GO
CREATE DATABASE sqli_arena_mssql_lab17;
GO
USE sqli_arena_mssql_lab17;
GO
CREATE TABLE products (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    price DECIMAL(10,2) NOT NULL
);
GO
INSERT INTO products (name, price) VALUES
('Laptop Pro 15',         1299.99),
('Wireless Mouse',         29.99),
('USB-C Hub',              49.99),
('Mechanical Keyboard',   149.99),
('Monitor 27" 4K',        399.99),
('Webcam HD',              79.99),
('SSD 1TB NVMe',         109.99),
('RAM 32GB DDR5',         139.99);
GO

CREATE TABLE flags (
    id INT IDENTITY(1,1) PRIMARY KEY,
    flag VARCHAR(100) NOT NULL
);
GO
INSERT INTO flags (flag) VALUES ('FLAG{ms_un1c0d3_n0rm_byp4ss}');
GO
