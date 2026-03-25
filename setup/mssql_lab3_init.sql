-- =========================
-- SQLi-Arena: MSSQL Lab 3
-- Error-Based: IN Operator Subquery
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab3')
    DROP DATABASE sqli_arena_mssql_lab3;
GO
CREATE DATABASE sqli_arena_mssql_lab3;
GO
USE sqli_arena_mssql_lab3;
GO

CREATE TABLE users (
    id INT IDENTITY(1,1) PRIMARY KEY,
    username NVARCHAR(50) NOT NULL,
    password NVARCHAR(100) NOT NULL,
    email NVARCHAR(100)
);
GO

INSERT INTO users (username, password, email) VALUES
('alice',   'alice_sunny_42',            'alice@example.com'),
('bob',     'b0bSecure!99',              'bob@example.com'),
('admin',   'FLAG{ms_1n_typ3_3rr0r}',   'admin@sqli-arena.local');
GO

CREATE TABLE products (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name NVARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category NVARCHAR(50) NOT NULL
);
GO

INSERT INTO products (name, price, category) VALUES
('Laptop Pro 15',       1299.99, 'Electronics'),
('Wireless Mouse',        29.99, 'Electronics'),
('USB-C Hub',             49.99, 'Electronics'),
('Standing Desk',        599.99, 'Furniture'),
('Ergonomic Chair',      449.99, 'Furniture'),
('Mechanical Keyboard',  149.99, 'Electronics'),
('Monitor 27"',          399.99, 'Electronics'),
('Desk Lamp',             39.99, 'Furniture');
GO
