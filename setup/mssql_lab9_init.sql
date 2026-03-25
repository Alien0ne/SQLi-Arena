-- =========================
-- SQLi-Arena: MSSQL Lab 9
-- Python sp_execute_external_script
-- =========================
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'sqli_arena_mssql_lab9')
    DROP DATABASE sqli_arena_mssql_lab9;
GO
CREATE DATABASE sqli_arena_mssql_lab9;
GO
USE sqli_arena_mssql_lab9;
GO
CREATE TABLE ml_models (
    id INT IDENTITY(1,1) PRIMARY KEY,
    model_name VARCHAR(100) NOT NULL,
    accuracy DECIMAL(5,2) NOT NULL,
    last_trained DATETIME DEFAULT GETDATE()
);
GO
INSERT INTO ml_models (model_name, accuracy) VALUES
('churn_predictor',      94.20),
('fraud_detector',       97.80),
('recommendation_engine', 89.50),
('sentiment_analyzer',   91.30),
('price_forecaster',     86.70);
GO

CREATE TABLE flags (
    id INT IDENTITY(1,1) PRIMARY KEY,
    flag VARCHAR(100) NOT NULL
);
GO
INSERT INTO flags (flag) VALUES ('FLAG{ms_py_3xt3rn4l_scr1pt}');
GO
