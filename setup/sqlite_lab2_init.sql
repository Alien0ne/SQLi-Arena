DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS hidden_data;

CREATE TABLE employees (
    id INTEGER PRIMARY KEY,
    name TEXT,
    department TEXT
);

CREATE TABLE hidden_data (
    id INTEGER PRIMARY KEY,
    secret_flag TEXT,
    notes TEXT
);

INSERT INTO employees (id, name, department) VALUES (1, 'Alice Johnson', 'Engineering');
INSERT INTO employees (id, name, department) VALUES (2, 'Bob Williams', 'Marketing');
INSERT INTO employees (id, name, department) VALUES (3, 'Carol Davis', 'Finance');
INSERT INTO employees (id, name, department) VALUES (4, 'David Brown', 'Engineering');
INSERT INTO employees (id, name, department) VALUES (5, 'Eve Wilson', 'HR');

INSERT INTO hidden_data (id, secret_flag, notes) VALUES (1, 'FLAG{sq_pr4gm4_t4bl3_1nf0}', 'This is the master flag');
INSERT INTO hidden_data (id, notes, secret_flag) VALUES (2, 'Decoy entry', 'not_a_flag');
