DROP TABLE IF EXISTS app_config;
DROP TABLE IF EXISTS json_secrets;

CREATE TABLE app_config (
    id INTEGER PRIMARY KEY,
    config_json TEXT
);

CREATE TABLE json_secrets (
    id INTEGER PRIMARY KEY,
    secret_data TEXT
);

INSERT INTO app_config (id, config_json) VALUES (1, '{"debug":false,"flag":"FLAG{sq_js0n_3xtr4ct_1nj}","version":"2.0"}');
INSERT INTO app_config (id, config_json) VALUES (2, '{"theme":"dark","language":"en","notifications":true}');
INSERT INTO app_config (id, config_json) VALUES (3, '{"rate_limit":100,"timeout":30,"retry":3}');

INSERT INTO json_secrets (id, secret_data) VALUES (1, 'FLAG{sq_js0n_3xtr4ct_1nj}');
