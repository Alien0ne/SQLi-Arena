DROP TABLE IF EXISTS search_data;
DROP TABLE IF EXISTS hidden_flags;

CREATE TABLE search_data (
    id INTEGER PRIMARY KEY,
    keyword TEXT,
    description TEXT
);

CREATE TABLE hidden_flags (
    id INTEGER PRIMARY KEY,
    flag_value TEXT
);

INSERT INTO search_data (id, keyword, description) VALUES (1, 'networking', 'TCP/IP fundamentals and protocols');
INSERT INTO search_data (id, keyword, description) VALUES (2, 'encryption', 'AES, RSA, and modern cryptography');
INSERT INTO search_data (id, keyword, description) VALUES (3, 'authentication', 'OAuth, JWT, and session management');
INSERT INTO search_data (id, keyword, description) VALUES (4, 'firewall', 'Network security and packet filtering');
INSERT INTO search_data (id, keyword, description) VALUES (5, 'malware', 'Virus, trojan, and ransomware analysis');

INSERT INTO hidden_flags (id, flag_value) VALUES (1, 'FLAG{sq_w4f_n0_st4nd4rd}');
