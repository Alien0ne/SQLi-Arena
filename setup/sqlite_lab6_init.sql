-- SQLite Lab 6: typeof() / zeroblob() Tricks
-- Tables: data_entries, system_config

CREATE TABLE IF NOT EXISTS data_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    label TEXT NOT NULL,
    value TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS system_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    config_key TEXT NOT NULL,
    config_value TEXT NOT NULL
);

INSERT INTO data_entries (label, value) VALUES ('Temperature', '72.5 F');
INSERT INTO data_entries (label, value) VALUES ('Humidity', '45%');
INSERT INTO data_entries (label, value) VALUES ('Pressure', '1013 hPa');
INSERT INTO data_entries (label, value) VALUES ('Wind Speed', '12 mph');
INSERT INTO data_entries (label, value) VALUES ('Visibility', '10 miles');

INSERT INTO system_config (config_key, config_value) VALUES ('master_flag', 'FLAG{sl_typ30f_un10n_byp4ss}');
INSERT INTO system_config (config_key, config_value) VALUES ('db_version', '3.39.4');
INSERT INTO system_config (config_key, config_value) VALUES ('maintenance_mode', 'false');
