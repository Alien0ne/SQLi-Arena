-- SQLite Lab 2: UNION - pragma_table_info() Enumeration
-- Tables: employees, hidden_data

CREATE TABLE IF NOT EXISTS employees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    department TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS hidden_data (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    secret_flag TEXT NOT NULL
);

INSERT INTO employees (name, department) VALUES ('Alice Johnson', 'Engineering');
INSERT INTO employees (name, department) VALUES ('Bob Smith', 'Marketing');
INSERT INTO employees (name, department) VALUES ('Carol Williams', 'Finance');
INSERT INTO employees (name, department) VALUES ('David Brown', 'Engineering');
INSERT INTO employees (name, department) VALUES ('Eve Davis', 'Human Resources');

INSERT INTO hidden_data (secret_flag) VALUES ('FLAG{sl_pr4gm4_t4bl3_1nf0}');
INSERT INTO hidden_data (secret_flag) VALUES ('decoy_not_a_flag_abc123');
INSERT INTO hidden_data (secret_flag) VALUES ('decoy_not_a_flag_def456');
