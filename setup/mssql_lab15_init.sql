-- =========================
-- SQLi-Arena: MSSQL Lab 15
-- Header Injection: Referer
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab15')
    DROP DATABASE sqli_arena_mssql_lab15;
GO
CREATE DATABASE sqli_arena_mssql_lab15;
GO
USE sqli_arena_mssql_lab15;
GO
CREATE TABLE page_visits (
    id INT IDENTITY(1,1) PRIMARY KEY,
    url VARCHAR(500) NOT NULL,
    referer VARCHAR(500) NOT NULL,
    visitor_ip VARCHAR(45) NOT NULL,
    visited_at DATETIME DEFAULT GETDATE()
);
GO
INSERT INTO page_visits (url, referer, visitor_ip) VALUES
('/dashboard',     'http://google.com/search?q=site',  '10.0.0.1'),
('/products',      'http://bing.com/search?q=shop',    '10.0.0.2'),
('/about',         'direct',                            '10.0.0.3'),
('/contact',       'http://twitter.com/link',           '10.0.0.4');
GO

CREATE TABLE flags (
    id INT IDENTITY(1,1) PRIMARY KEY,
    flag VARCHAR(100) NOT NULL
);
GO
INSERT INTO flags (flag) VALUES ('FLAG{ms_r3f3r3r_h34d3r_1nj}');
GO
