-- =========================
-- SQLi-Arena: MySQL Lab 3
-- String Injection with Parentheses
-- =========================

CREATE DATABASE IF NOT EXISTS sqli_arena_mysql_lab3;
USE sqli_arena_mysql_lab3;

DROP TABLE IF EXISTS employees;

CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL,
    salary DECIMAL(12,2) NOT NULL,
    ssn VARCHAR(100) NOT NULL
);

INSERT INTO employees (name, department, salary, ssn) VALUES
('John Smith',      'engineering',  85000.00,  '123-45-6789'),
('Jane Doe',        'engineering',  90000.00,  '234-56-7890'),
('Mike Johnson',    'marketing',    72000.00,  '345-67-8901'),
('Sarah Williams',  'marketing',    68000.00,  '456-78-9012'),
('Tom Brown',       'sales',        65000.00,  '567-89-0123'),
('Robert Chen',     'executive',   250000.00,  'FLAG{my_p4r3n_wr4pp3d_unl0n}');
