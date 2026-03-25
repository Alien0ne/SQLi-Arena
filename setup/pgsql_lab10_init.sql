-- Lab 10: RCE - Custom C Function (UDF)
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab10;
CREATE DATABASE sqli_arena_pgsql_lab10 OWNER sqli_arena;
\c sqli_arena_pgsql_lab10

CREATE TABLE analytics (
    id SERIAL PRIMARY KEY,
    page_name TEXT NOT NULL,
    visit_count INTEGER NOT NULL DEFAULT 0
);

INSERT INTO analytics (page_name, visit_count) VALUES
('/home', 15234),
('/about', 4521),
('/products', 8932),
('/contact', 2107),
('/blog', 6743);

CREATE TABLE master_key (
    id SERIAL PRIMARY KEY,
    key_value TEXT NOT NULL
);

INSERT INTO master_key (key_value) VALUES
('FLAG{pg_udf_sh4r3d_l1b}');

GRANT ALL ON ALL TABLES IN SCHEMA public TO sqli_arena;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;
