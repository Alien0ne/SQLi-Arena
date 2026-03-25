-- ================================
-- SQLi-Arena: MariaDB Lab 6
-- sys_exec UDF -- OS Commands
-- ================================

DROP DATABASE IF EXISTS sqli_arena_mariadb_lab6;
CREATE DATABASE sqli_arena_mariadb_lab6;
USE sqli_arena_mariadb_lab6;

DROP TABLE IF EXISTS system_logs;
DROP TABLE IF EXISTS udf_secrets;

CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    details TEXT
);

CREATE TABLE udf_secrets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    secret_value VARCHAR(100) NOT NULL
);

INSERT INTO system_logs (action, details) VALUES
('LOGIN', 'User admin logged in from 192.168.1.10'),
('QUERY', 'SELECT * FROM performance_schema.threads'),
('UDF_LOAD', 'Loaded lib_mysqludf_sys.so into plugin_dir'),
('EXEC', 'sys_exec(whoami) returned: mysql'),
('FILE_READ', 'LOAD_FILE(/etc/passwd) succeeded'),
('PRIV_CHECK', 'User has FILE and SUPER privileges');

INSERT INTO udf_secrets (secret_value) VALUES
('FLAG{ma_sys_3x3c_udf_rc3}');
