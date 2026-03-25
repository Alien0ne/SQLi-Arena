DROP TABLE IF EXISTS data_entries;
DROP TABLE IF EXISTS system_config;

CREATE TABLE data_entries (
    id INTEGER PRIMARY KEY,
    label TEXT,
    value TEXT
);

CREATE TABLE system_config (
    id INTEGER PRIMARY KEY,
    config_key TEXT,
    config_value TEXT
);

INSERT INTO data_entries (id, label, value) VALUES (1, 'server_name', 'web-prod-01');
INSERT INTO data_entries (id, label, value) VALUES (2, 'server_ip', '10.0.1.50');
INSERT INTO data_entries (id, label, value) VALUES (3, 'region', 'us-east-1');
INSERT INTO data_entries (id, label, value) VALUES (4, 'environment', 'production');
INSERT INTO data_entries (id, label, value) VALUES (5, 'version', '3.2.1');

INSERT INTO system_config (id, config_key, config_value) VALUES (1, 'debug_mode', 'false');
INSERT INTO system_config (id, config_key, config_value) VALUES (2, 'master_flag', 'FLAG{sq_typ30f_z3r0bl0b}');
INSERT INTO system_config (id, config_key, config_value) VALUES (3, 'max_connections', '100');
