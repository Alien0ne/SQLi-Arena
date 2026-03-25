-- =========================
-- SQLi-Arena: MySQL Lab 3
-- String with Parentheses
-- =========================

DROP DATABASE IF EXISTS sqli_arena_mysql_lab3;
CREATE DATABASE sqli_arena_mysql_lab3;
USE sqli_arena_mysql_lab3;

DROP TABLE IF EXISTS employees;

CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL,
    salary INT NOT NULL,
    ssn VARCHAR(100) NOT NULL
);

INSERT INTO employees (name, department, salary, ssn) VALUES
('John Smith',      'engineering',  85000,  '123-45-6789'),
('Jane Doe',        'engineering',  90000,  '234-56-7890'),
('Mike Johnson',    'marketing',    72000,  '345-67-8901'),
('Sarah Williams',  'marketing',    68000,  '456-78-9012'),
('Tom Brown',       'sales',        65000,  '567-89-0123'),
('Lisa Davis',      'sales',        71000,  '678-90-1234'),
('Admin Root',      'executive',    250000, 'FLAG{p4r3nth3s3s_br34k0ut}');
