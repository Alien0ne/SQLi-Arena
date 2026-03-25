-- =========================
-- SQLi-Arena: MSSQL Lab 14
-- Impersonation: EXECUTE AS
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab14')
    DROP DATABASE sqli_arena_mssql_lab14;
GO
CREATE DATABASE sqli_arena_mssql_lab14;
GO
USE sqli_arena_mssql_lab14;
GO

CREATE TABLE notes (
    id INT IDENTITY(1,1) PRIMARY KEY,
    title NVARCHAR(100) NOT NULL,
    content NVARCHAR(500) NOT NULL
);
GO

INSERT INTO notes (title, content) VALUES
('Public Data',      'This is publicly accessible information'),
('Team Schedule',    'Monday standup at 9am, Friday retro at 4pm'),
('Office Supplies',  'Order more pens and paper for the printer');
GO

CREATE TABLE flags (
    id INT IDENTITY(1,1) PRIMARY KEY,
    flag NVARCHAR(100) NOT NULL
);
GO

INSERT INTO flags (flag) VALUES ('FLAG{ms_3x3cut3_4s_pr1v3sc}');
GO

-- ==========================================
-- Low-privilege login for the web application
-- ==========================================
USE master;
GO

-- Create the low-priv login
IF NOT EXISTS (SELECT * FROM sys.server_principals WHERE name = 'lab14_web_user')
    CREATE LOGIN lab14_web_user WITH PASSWORD = 'WebUser2026!', CHECK_POLICY = OFF;
GO

-- CRITICAL: Grant IMPERSONATE on sa (the privilege escalation vector)
GRANT IMPERSONATE ON LOGIN::sa TO lab14_web_user;
GO

-- Configure database-level permissions
USE sqli_arena_mssql_lab14;
GO

IF NOT EXISTS (SELECT * FROM sys.database_principals WHERE name = 'lab14_web_user')
    CREATE USER lab14_web_user FOR LOGIN lab14_web_user;
GO

-- Allow reading notes (the app's normal function)
GRANT SELECT, UPDATE ON notes TO lab14_web_user;
GO

-- DENY access to flags (requires privilege escalation to read)
DENY SELECT ON flags TO lab14_web_user;
GO

-- Allow metadata queries (for injection enumeration)
GRANT VIEW DEFINITION TO lab14_web_user;
GO

PRINT 'Lab 14: lab14_web_user configured (SELECT on notes, DENY on flags, IMPERSONATE sa)';
GO
