-- ================================
-- SQLi-Arena: MariaDB Lab 3
-- Spider Engine -- Federated Injection
-- ================================

DROP DATABASE IF EXISTS sqli_arena_mariadb_lab3;
CREATE DATABASE sqli_arena_mariadb_lab3;
USE sqli_arena_mariadb_lab3;

DROP TABLE IF EXISTS servers;
DROP TABLE IF EXISTS federation_keys;

CREATE TABLE servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostname VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL
);

CREATE TABLE federation_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_value VARCHAR(100) NOT NULL
);

INSERT INTO servers (hostname, status) VALUES
('node-alpha.cluster.local', 'active'),
('node-beta.cluster.local', 'active'),
('node-gamma.cluster.local', 'standby'),
('node-delta.cluster.local', 'active'),
('node-epsilon.cluster.local', 'maintenance'),
('spider-proxy.cluster.local', 'active');

INSERT INTO federation_keys (key_value) VALUES
('FLAG{ma_sp1d3r_f3d3r4t3d}');
