-- =========================
-- SQLi-Arena: MSSQL Internal Server (Server B)
-- Used by Lab 13 (Linked Server Pivoting)
-- Runs on sqli-arena-mssql-internal container
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'internal_db')
    DROP DATABASE internal_db;
GO
CREATE DATABASE internal_db;
GO
USE internal_db;
GO

-- Secret records table (target for linked server pivoting)
CREATE TABLE secret_records (
    id INT IDENTITY(1,1) PRIMARY KEY,
    record_name NVARCHAR(100) NOT NULL,
    record_value NVARCHAR(200) NOT NULL,
    classification NVARCHAR(20) NOT NULL
);
GO

INSERT INTO secret_records (record_name, record_value, classification) VALUES
('API Master Key',         'sk-prod-9f8e7d6c5b4a3210',       'TOP SECRET'),
('Database Root Password', 'S3rv3rB_R00t_P@ss!',             'CONFIDENTIAL'),
('Encryption Key',         'aes256-0xDEADBEEF42',            'TOP SECRET'),
('Internal Flag',          'FLAG{ms_l1nk3d_s3rv3r_p1v0t}',   'CLASSIFIED');
GO

-- Internal users table
CREATE TABLE internal_users (
    id INT IDENTITY(1,1) PRIMARY KEY,
    username NVARCHAR(50) NOT NULL,
    password_hash NVARCHAR(100) NOT NULL,
    role NVARCHAR(30) NOT NULL,
    department NVARCHAR(50) NOT NULL
);
GO

INSERT INTO internal_users (username, password_hash, role, department) VALUES
('svc_backup',   'hash_9a8b7c6d5e4f',   'service_account', 'IT Operations'),
('admin_intern', 'hash_1a2b3c4d5e6f',   'admin',           'IT Security'),
('db_admin',     'hash_f1e2d3c4b5a6',   'dba',             'Database Team'),
('cfo_account',  'hash_0f1e2d3c4b5a',   'executive',       'Finance');
GO

-- Salary data
CREATE TABLE salary_data (
    id INT IDENTITY(1,1) PRIMARY KEY,
    employee NVARCHAR(50) NOT NULL,
    salary DECIMAL(10,2) NOT NULL,
    bonus DECIMAL(10,2) NOT NULL
);
GO

INSERT INTO salary_data (employee, salary, bonus) VALUES
('CEO',  450000.00, 200000.00),
('CFO',  320000.00, 150000.00),
('CTO',  340000.00, 160000.00),
('CISO', 280000.00, 120000.00);
GO

PRINT 'Internal server (Server B): internal_db initialized with secret_records, internal_users, salary_data';
GO
