-- =========================
-- SQLi-Arena: MSSQL Lab 13
-- Linked Servers: Pivoting
-- Server A (this instance) links to Server B (sqli-arena-mssql-internal)
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
    name NVARCHAR(100) NOT NULL,
    email NVARCHAR(100) NOT NULL
);
GO

INSERT INTO customers (name, email) VALUES
('John Smith',     'john.smith@example.com'),
('Jane Wilson',    'jane.wilson@example.com'),
('Bob Johnson',    'bob.johnson@example.com'),
('Alice Chen',     'alice.chen@example.com'),
('David Brown',    'david.brown@example.com');
GO

-- Flag is on Server B (internal_db.dbo.secret_records) — students must pivot via linked server

-- ==========================================
-- Linked Server: points to real Server B
-- (sqli-arena-mssql-internal Docker container)
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
    @datasrc = 'sqli-arena-mssql-internal';
GO

-- Authenticate as sa on Server B
EXEC sp_addlinkedsrvlogin
    @rmtsrvname = 'INTERNAL_DB_SRV',
    @useself = 'false',
    @rmtuser = 'sa',
    @rmtpassword = 'Internal2026!';
GO

-- Enable RPC for EXEC AT syntax
EXEC sp_serveroption 'INTERNAL_DB_SRV', 'rpc', 'true';
EXEC sp_serveroption 'INTERNAL_DB_SRV', 'rpc out', 'true';
GO

PRINT 'Lab 13: Linked server INTERNAL_DB_SRV -> sqli-arena-mssql-internal configured';
GO
