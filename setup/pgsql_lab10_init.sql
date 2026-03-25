-- =========================
-- SQLi-Arena: PostgreSQL Lab 10
-- RCE -- Custom C Function (UDF)
-- =========================
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab10;
CREATE DATABASE sqli_arena_pgsql_lab10;
\c sqli_arena_pgsql_lab10

CREATE TABLE analytics (
    id SERIAL PRIMARY KEY,
    page_name VARCHAR(200) NOT NULL,
    visit_count INTEGER NOT NULL
);

CREATE TABLE master_key (
    id SERIAL PRIMARY KEY,
    key_value VARCHAR(200) NOT NULL
);

INSERT INTO analytics (page_name, visit_count) VALUES
('/home', 15234),
('/about', 4521),
('/products', 8932),
('/contact', 2107),
('/blog', 6743),
('/api/docs', 3891);

INSERT INTO master_key (key_value) VALUES
('FLAG{pg_udf_c_funct10n_rc3}');
