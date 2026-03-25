-- Oracle Lab 5: Error-Based CTXSYS.DRITHSX.SN
-- Tables: employees (id, name, department), hidden_table (id, secret)

CREATE TABLE employees (
    id         NUMBER PRIMARY KEY,
    name       VARCHAR2(200) NOT NULL,
    department VARCHAR2(100)
);

INSERT INTO employees (id, name, department) VALUES (1, 'John Smith', 'Engineering');
INSERT INTO employees (id, name, department) VALUES (2, 'Jane Doe', 'Engineering');
INSERT INTO employees (id, name, department) VALUES (3, 'Mike Wilson', 'Marketing');
INSERT INTO employees (id, name, department) VALUES (4, 'Sarah Connor', 'Finance');
INSERT INTO employees (id, name, department) VALUES (5, 'Tom Brown', 'HR');

CREATE TABLE hidden_table (
    id     NUMBER PRIMARY KEY,
    secret VARCHAR2(200)
);

INSERT INTO hidden_table (id, secret) VALUES (1, 'FLAG{or_ctxsys_dr1thsx}');

COMMIT;
EXIT;
