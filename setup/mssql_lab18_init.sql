-- =========================
-- SQLi-Arena: MSSQL Lab 18
-- NTLM Hash Capture via SMB
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab18')
    DROP DATABASE sqli_arena_mssql_lab18;
GO
CREATE DATABASE sqli_arena_mssql_lab18;
GO
USE sqli_arena_mssql_lab18;
GO

CREATE TABLE assets (
    id INT IDENTITY(1,1) PRIMARY KEY,
    asset_name NVARCHAR(100) NOT NULL,
    asset_type NVARCHAR(50) NOT NULL,
    location NVARCHAR(100) NOT NULL
);
GO

INSERT INTO assets (asset_name, asset_type, location) VALUES
('web-server-01',   'Server',     'Datacenter A - Rack 12'),
('web-server-02',   'Server',     'Datacenter A - Rack 12'),
('db-server-01',    'Server',     'Datacenter B - Rack 5'),
('fw-edge-01',      'Firewall',   'Datacenter A - Core'),
('switch-floor-3',  'Network',    'Building 2 - Floor 3'),
('printer-hr',      'Peripheral', 'Building 1 - Floor 2'),
('laptop-ceo',      'Endpoint',   'Executive Office'),
('server-backup',   'Server',     'Datacenter B - Rack 8');
GO

CREATE TABLE flags (
    id INT IDENTITY(1,1) PRIMARY KEY,
    flag NVARCHAR(100) NOT NULL
);
GO

INSERT INTO flags (flag) VALUES ('FLAG{ms_ntlm_h4sh_c4ptur3}');
GO
