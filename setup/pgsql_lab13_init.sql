-- =========================
-- SQLi-Arena: PostgreSQL Lab 13
-- XML Injection -- xmlparse / xpath
-- =========================
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab13;
CREATE DATABASE sqli_arena_pgsql_lab13;
\c sqli_arena_pgsql_lab13

CREATE TABLE configs (
    id SERIAL PRIMARY KEY,
    config_key VARCHAR(200) NOT NULL,
    config_value TEXT NOT NULL
);

CREATE TABLE hidden_flags (
    id SERIAL PRIMARY KEY,
    flag_value VARCHAR(200) NOT NULL
);

INSERT INTO configs (config_key, config_value) VALUES
('app.name', 'SQLi-Arena Config Viewer'),
('app.version', '2.4.1'),
('db.pool_size', '25'),
('cache.ttl', '3600'),
('log.level', 'INFO'),
('session.timeout', '1800');

INSERT INTO hidden_flags (flag_value) VALUES
('FLAG{pg_xml_xp4th_1nj3ct}');
