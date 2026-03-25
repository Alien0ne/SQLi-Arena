-- Oracle Lab 11: Out-of-Band DBMS_LDAP.INIT
-- Tables: inventory (id, item, quantity, location), ldap_secrets (id, secret)

CREATE TABLE inventory (
    id       NUMBER PRIMARY KEY,
    item     VARCHAR2(200),
    quantity NUMBER,
    location VARCHAR2(100)
);

INSERT INTO inventory (id, item, quantity, location) VALUES (1, 'Server Rack Unit', 24, 'DC-East');
INSERT INTO inventory (id, item, quantity, location) VALUES (2, 'Network Switch 48-Port', 12, 'DC-East');
INSERT INTO inventory (id, item, quantity, location) VALUES (3, 'UPS Battery Pack', 8, 'DC-West');
INSERT INTO inventory (id, item, quantity, location) VALUES (4, 'Fiber Patch Cable', 500, 'DC-East');
INSERT INTO inventory (id, item, quantity, location) VALUES (5, 'SSD 1TB Enterprise', 36, 'DC-West');

CREATE TABLE ldap_secrets (
    id     NUMBER PRIMARY KEY,
    secret VARCHAR2(200)
);

INSERT INTO ldap_secrets (id, secret) VALUES (1, 'FLAG{or_dbms_ld4p_00b}');

COMMIT;
EXIT;
