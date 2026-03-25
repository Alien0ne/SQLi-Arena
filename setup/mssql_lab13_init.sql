-- =========================
-- SQLi-Arena: MSSQL Lab 13
-- Linked Servers: Pivoting
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab13')
    DROP DATABASE sqli_arena_mssql_lab13;
GO
CREATE DATABASE sqli_arena_mssql_lab13;
GO
USE sqli_arena_mssql_lab13;
GO
CREATE TABLE customers (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL
);
GO
INSERT INTO customers (name, email) VALUES
('John Smith',     'john.smith@example.com'),
('Jane Wilson',    'jane.wilson@example.com'),
('Bob Johnson',    'bob.johnson@example.com'),
('Alice Chen',     'alice.chen@example.com'),
('David Brown',    'david.brown@example.com'),
('Emily Davis',    'emily.davis@example.com');
GO

CREATE TABLE flags (
    id INT IDENTITY(1,1) PRIMARY KEY,
    flag VARCHAR(100) NOT NULL
);
GO
INSERT INTO flags (flag) VALUES ('FLAG{ms_l1nk3d_s3rv3r_p1v0t}');
GO

-- ==========================================
-- Server B: Internal database (pivot target)
-- ==========================================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_internal_db')
    DROP DATABASE sqli_arena_internal_db;
GO
CREATE DATABASE sqli_arena_internal_db;
GO
USE sqli_arena_internal_db;
GO

-- Sensitive secrets table
CREATE TABLE secrets (
    id INT IDENTITY(1,1) PRIMARY KEY,
    secret_name VARCHAR(100) NOT NULL,
    secret_value VARCHAR(200) NOT NULL,
    classification VARCHAR(20) NOT NULL
);
GO
INSERT INTO secrets (secret_name, secret_value, classification) VALUES
('API Master Key',        'sk-prod-9f8e7d6c5b4a3210',   'TOP SECRET'),
('Database Root Password','S3rv3rB_R00t_P@ss!',         'CONFIDENTIAL'),
('Encryption Key',        'aes256-0xDEADBEEF42',        'TOP SECRET'),
('Internal Flag',         'FLAG{p1v0t3d_t0_s3rv3r_B!}', 'CLASSIFIED');
GO

-- Internal users table
CREATE TABLE internal_users (
    id INT IDENTITY(1,1) PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(100) NOT NULL,
    role VARCHAR(30) NOT NULL,
    department VARCHAR(50) NOT NULL
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
    employee VARCHAR(50) NOT NULL,
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

-- ==========================================
-- Linked Server: loopback to simulate Server B
-- ==========================================
USE master;
GO
IF EXISTS (SELECT * FROM sys.servers WHERE name = 'INTERNAL_DB_SRV')
    EXEC sp_dropserver 'INTERNAL_DB_SRV', 'droplogins';
GO

EXEC sp_addlinkedserver
    @server = 'INTERNAL_DB_SRV',
    @srvproduct = '',
    @provider = 'SQLNCLI',
    @datasrc = 'localhost';
GO

-- When sqli_arena connects via linked server, authenticate as sa on "Server B"
EXEC sp_addlinkedsrvlogin
    @rmtsrvname = 'INTERNAL_DB_SRV',
    @useself = 'false',
    @rmtuser = 'sa',
    @rmtpassword = 'SqliArena2026!';
GO

-- Enable RPC for EXEC AT syntax
EXEC sp_serveroption 'INTERNAL_DB_SRV', 'rpc', 'true';
EXEC sp_serveroption 'INTERNAL_DB_SRV', 'rpc out', 'true';
GO

PRINT 'Lab 13: Linked server INTERNAL_DB_SRV configured (loopback to sqli_arena_internal_db)';
GO
