-- Lab 12: Large Objects Abuse
DROP DATABASE IF EXISTS sqli_arena_pgsql_lab12;
CREATE DATABASE sqli_arena_pgsql_lab12 OWNER sqli_arena;
\c sqli_arena_pgsql_lab12

CREATE TABLE gallery (
    id SERIAL PRIMARY KEY,
    image_name TEXT NOT NULL,
    description TEXT NOT NULL
);

INSERT INTO gallery (image_name, description) VALUES
('sunset_beach.jpg', 'Golden sunset over a tropical beach with palm trees.'),
('mountain_peak.png', 'Snow-capped mountain peak at dawn with clear skies.'),
('city_skyline.jpg', 'Urban skyline at night with illuminated skyscrapers.'),
('forest_trail.png', 'Winding trail through an ancient redwood forest.'),
('ocean_waves.jpg', 'Powerful ocean waves crashing against rocky cliffs.');

CREATE TABLE system_secrets (
    id SERIAL PRIMARY KEY,
    secret_value TEXT NOT NULL
);

INSERT INTO system_secrets (secret_value) VALUES
('FLAG{pg_l4rg3_0bj3ct_f1l3}');

GRANT ALL ON ALL TABLES IN SCHEMA public TO sqli_arena;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO sqli_arena;
