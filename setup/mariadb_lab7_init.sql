-- ================================
-- SQLi-Arena: MariaDB Lab 7
-- Error -- SIGNAL / GET DIAGNOSTICS
-- ================================

DROP DATABASE IF EXISTS sqli_arena_mariadb_lab7;
CREATE DATABASE sqli_arena_mariadb_lab7;
USE sqli_arena_mariadb_lab7;

DROP TABLE IF EXISTS diagnostics;
DROP TABLE IF EXISTS signal_secrets;

CREATE TABLE diagnostics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(100) NOT NULL,
    result VARCHAR(100) NOT NULL
);

CREATE TABLE signal_secrets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    secret_value VARCHAR(100) NOT NULL
);

INSERT INTO diagnostics (test_name, result) VALUES
('SIGNAL_TEST', 'SQLSTATE 45000 raised successfully'),
('HANDLER_TEST', 'CONTINUE handler caught SQLWARNING'),
('DIAGNOSTICS_TEST', 'GET DIAGNOSTICS returned row_count=5'),
('CONDITION_TEST', 'DECLARE condition_name CONDITION FOR 1062'),
('RESIGNAL_TEST', 'RESIGNAL with modified message text'),
('STACK_TEST', 'GET STACKED DIAGNOSTICS captured original error');

INSERT INTO signal_secrets (secret_value) VALUES
('FLAG{ma_s1gn4l_d14gn0st1cs}');
