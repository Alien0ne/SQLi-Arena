-- ================================
-- SQLi-Arena: MariaDB Lab 8
-- Window Functions for Extraction
-- ================================

DROP DATABASE IF EXISTS sqli_arena_mariadb_lab8;
CREATE DATABASE sqli_arena_mariadb_lab8;
USE sqli_arena_mariadb_lab8;

DROP TABLE IF EXISTS scores;
DROP TABLE IF EXISTS window_flags;

CREATE TABLE scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player VARCHAR(50) NOT NULL,
    score INT NOT NULL
);

CREATE TABLE window_flags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flag_value VARCHAR(100) NOT NULL
);

INSERT INTO scores (player, score) VALUES
('DragonSlayer', 9500),
('CyberNinja', 8700),
('PixelWitch', 9200),
('NeonSamurai', 8100),
('GhostRunner', 9800),
('ByteKnight', 7600),
('VoidWalker', 8900),
('QuantumFox', 9100);

INSERT INTO window_flags (flag_value) VALUES
('FLAG{ma_w1nd0w_func_3xtr4ct}');
