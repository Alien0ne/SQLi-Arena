-- =========================
-- SQLi-Arena: MySQL Lab 4
-- Double-Quote Wrapping
-- =========================

DROP DATABASE IF EXISTS sqli_arena_mysql_lab4;
CREATE DATABASE sqli_arena_mysql_lab4;
USE sqli_arena_mysql_lab4;

DROP TABLE IF EXISTS articles;

CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    draft_flag VARCHAR(100) DEFAULT NULL
);

INSERT INTO articles (title, author, content, draft_flag) VALUES
('Getting Started with SQL',        'alice',  'SQL is a powerful language for managing relational databases...',       NULL),
('Advanced JOIN Techniques',         'alice',  'Understanding INNER, LEFT, RIGHT and CROSS joins is essential...',     NULL),
('Database Normalization Guide',     'bob',    'Normalization reduces data redundancy and improves integrity...',      NULL),
('Indexing Best Practices',          'bob',    'Proper indexing can dramatically improve query performance...',        NULL),
('Securing Your Database',           'charlie','Always use parameterized queries to prevent injection attacks...',     NULL),
('Backup and Recovery Strategies',   'charlie','Regular backups are critical for disaster recovery planning...',       NULL),
('DRAFT: Internal Security Audit',   'editor', 'This article contains confidential findings from the Q1 audit...',    'FLAG{d0ubl3_qu0t3_3sc4p3}');
