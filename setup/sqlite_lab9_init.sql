-- SQLite Lab 9: JSON Functions Injection
-- Tables: app_config, json_secrets

CREATE TABLE IF NOT EXISTS app_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    config_json TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS json_secrets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    secret_data TEXT NOT NULL
);

INSERT INTO app_config (config_json) VALUES ('{"version":"2.4.1","debug":false,"flag":"FLAG{sl_js0n_3xtr4ct_1nj}","maintainer":"admin@corp.local"}');
INSERT INTO app_config (config_json) VALUES ('{"version":"2.4.1","debug":true,"module":"auth","timeout":30}');
INSERT INTO app_config (config_json) VALUES ('{"version":"2.4.1","debug":false,"module":"api","rate_limit":100}');
INSERT INTO app_config (config_json) VALUES ('{"version":"2.4.1","debug":false,"module":"logging","retention_days":90}');

INSERT INTO json_secrets (secret_data) VALUES ('FLAG{sl_js0n_3xtr4ct_1nj}');
INSERT INTO json_secrets (secret_data) VALUES ('internal_api_token_abc123');
INSERT INTO json_secrets (secret_data) VALUES ('webhook_signing_secret_xyz');
