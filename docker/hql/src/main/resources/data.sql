-- ============================================================
-- SQLi-Arena HQL Labs - Seed Data
-- ============================================================

-- ============================================================
-- Lab 1: Entity Name Injection
-- Tables: products, admin_credentials, audit_logs
-- ============================================================

INSERT INTO products (name, price, category) VALUES ('Laptop Pro 15', 1299.99, 'Electronics');
INSERT INTO products (name, price, category) VALUES ('Wireless Mouse', 29.99, 'Electronics');
INSERT INTO products (name, price, category) VALUES ('USB-C Hub', 49.99, 'Electronics');
INSERT INTO products (name, price, category) VALUES ('Standing Desk', 599.99, 'Furniture');
INSERT INTO products (name, price, category) VALUES ('Ergonomic Chair', 449.99, 'Furniture');
INSERT INTO products (name, price, category) VALUES ('Monitor Arm', 89.99, 'Accessories');
INSERT INTO products (name, price, category) VALUES ('Mechanical Keyboard', 149.99, 'Electronics');
INSERT INTO products (name, price, category) VALUES ('Webcam HD', 79.99, 'Electronics');
INSERT INTO products (name, price, category) VALUES ('Desk Lamp', 34.99, 'Accessories');
INSERT INTO products (name, price, category) VALUES ('Cable Management Kit', 19.99, 'Accessories');

INSERT INTO admin_credentials (username, password_hash, secret_note) VALUES ('admin', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Default admin account');
INSERT INTO admin_credentials (username, password_hash, secret_note) VALUES ('superadmin', '$2a$10$R9h/cIPz0gi.URNNX3kh2OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW', 'FLAG{hq_3nt1ty_n4m3_1nj}');
INSERT INTO admin_credentials (username, password_hash, secret_note) VALUES ('backup_admin', '$2a$10$Ue8JHRqO7TkHSCGnsGEPRu2P5JEa7TGiMUCnlOF7r8PnoTfWnV3Hi', 'Backup account - rotate quarterly');

INSERT INTO audit_logs (action, timestamp, user_id) VALUES ('LOGIN', '2026-03-01 08:00:00', 1);
INSERT INTO audit_logs (action, timestamp, user_id) VALUES ('VIEW_PRODUCTS', '2026-03-01 08:05:00', 1);
INSERT INTO audit_logs (action, timestamp, user_id) VALUES ('UPDATE_PRICE', '2026-03-01 09:15:00', 1);
INSERT INTO audit_logs (action, timestamp, user_id) VALUES ('LOGIN', '2026-03-02 10:00:00', 2);
INSERT INTO audit_logs (action, timestamp, user_id) VALUES ('DELETE_PRODUCT', '2026-03-02 10:30:00', 2);
INSERT INTO audit_logs (action, timestamp, user_id) VALUES ('LOGIN', '2026-03-03 07:00:00', 3);
INSERT INTO audit_logs (action, timestamp, user_id) VALUES ('EXPORT_DATA', '2026-03-03 07:15:00', 3);

-- ============================================================
-- Lab 2: .class Metadata Access
-- Tables: users, secret_vault
-- ============================================================

INSERT INTO users (username, email, role) VALUES ('john_doe', 'john@example.com', 'user');
INSERT INTO users (username, email, role) VALUES ('jane_smith', 'jane@example.com', 'user');
INSERT INTO users (username, email, role) VALUES ('bob_admin', 'bob@example.com', 'admin');
INSERT INTO users (username, email, role) VALUES ('alice_mod', 'alice@example.com', 'moderator');
INSERT INTO users (username, email, role) VALUES ('charlie_dev', 'charlie@example.com', 'developer');
INSERT INTO users (username, email, role) VALUES ('diana_ops', 'diana@example.com', 'operator');

INSERT INTO secret_vault (vault_key, vault_value) VALUES ('api_key', 'sk-live-a1b2c3d4e5f6g7h8i9j0');
INSERT INTO secret_vault (vault_key, vault_value) VALUES ('db_password', 'S3cur3P@ssw0rd!2026');
INSERT INTO secret_vault (vault_key, vault_value) VALUES ('master_flag', 'FLAG{hq_cl4ss_m3t4d4t4}');
INSERT INTO secret_vault (vault_key, vault_value) VALUES ('encryption_key', 'aes-256-cbc:DEADBEEF0123456789');
INSERT INTO secret_vault (vault_key, vault_value) VALUES ('jwt_secret', 'super-secret-jwt-signing-key-2026');

-- ============================================================
-- Lab 3: Native Query Escape
-- Tables: employees, internal_secrets
-- ============================================================

INSERT INTO employees (name, department, salary) VALUES ('Alice Johnson', 'Engineering', 95000.00);
INSERT INTO employees (name, department, salary) VALUES ('Bob Williams', 'Engineering', 102000.00);
INSERT INTO employees (name, department, salary) VALUES ('Carol Davis', 'Engineering', 88000.00);
INSERT INTO employees (name, department, salary) VALUES ('Dave Brown', 'Marketing', 72000.00);
INSERT INTO employees (name, department, salary) VALUES ('Eve Wilson', 'Marketing', 68000.00);
INSERT INTO employees (name, department, salary) VALUES ('Frank Miller', 'Sales', 65000.00);
INSERT INTO employees (name, department, salary) VALUES ('Grace Taylor', 'Sales', 71000.00);
INSERT INTO employees (name, department, salary) VALUES ('Henry Anderson', 'HR', 78000.00);
INSERT INTO employees (name, department, salary) VALUES ('Iris Thomas', 'HR', 75000.00);
INSERT INTO employees (name, department, salary) VALUES ('Jack Martinez', 'Finance', 85000.00);

INSERT INTO internal_secrets (secret_key, secret_value) VALUES ('flag', 'FLAG{hq_n4t1v3_qu3ry_3sc}');
INSERT INTO internal_secrets (secret_key, secret_value) VALUES ('root_password', 'r00t_p@ss_2026!');
INSERT INTO internal_secrets (secret_key, secret_value) VALUES ('aws_access_key', 'AKIAIOSFODNN7EXAMPLE');
INSERT INTO internal_secrets (secret_key, secret_value) VALUES ('aws_secret_key', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');

-- ============================================================
-- Lab 4: Criteria API Bypass (ORDER BY Injection)
-- Tables: orders, secret_orders
-- ============================================================

INSERT INTO orders (customer_id, product, amount, status) VALUES (1, 'Laptop Pro 15', 1299.99, 'DELIVERED');
INSERT INTO orders (customer_id, product, amount, status) VALUES (1, 'Wireless Mouse', 29.99, 'DELIVERED');
INSERT INTO orders (customer_id, product, amount, status) VALUES (1, 'USB-C Hub', 49.99, 'SHIPPED');
INSERT INTO orders (customer_id, product, amount, status) VALUES (2, 'Standing Desk', 599.99, 'PROCESSING');
INSERT INTO orders (customer_id, product, amount, status) VALUES (2, 'Ergonomic Chair', 449.99, 'DELIVERED');
INSERT INTO orders (customer_id, product, amount, status) VALUES (3, 'Monitor Arm', 89.99, 'SHIPPED');
INSERT INTO orders (customer_id, product, amount, status) VALUES (3, 'Mechanical Keyboard', 149.99, 'DELIVERED');
INSERT INTO orders (customer_id, product, amount, status) VALUES (3, 'Webcam HD', 79.99, 'CANCELLED');
INSERT INTO orders (customer_id, product, amount, status) VALUES (4, 'Desk Lamp', 34.99, 'DELIVERED');
INSERT INTO orders (customer_id, product, amount, status) VALUES (4, 'Cable Management Kit', 19.99, 'SHIPPED');

INSERT INTO secret_orders (customer_id, product, amount, status, secret_flag) VALUES (999, 'FLAG_CONTAINER', 0.00, 'CLASSIFIED', 'FLAG{hq_cr1t3r14_4p1_byp4ss}');
INSERT INTO secret_orders (customer_id, product, amount, status, secret_flag) VALUES (999, 'DECOY_1', 0.00, 'CLASSIFIED', 'NOT_THE_FLAG');
INSERT INTO secret_orders (customer_id, product, amount, status, secret_flag) VALUES (999, 'DECOY_2', 0.00, 'CLASSIFIED', 'ALSO_NOT_THE_FLAG');

-- ============================================================
-- Lab 5: Cache Poisoning
-- Tables: articles, cache_config
-- ============================================================

INSERT INTO articles (title, content, author, cached) VALUES ('Introduction to Spring Boot', 'Spring Boot makes it easy to create stand-alone, production-grade Spring based Applications.', 'John Tech', TRUE);
INSERT INTO articles (title, content, author, cached) VALUES ('Understanding Hibernate ORM', 'Hibernate ORM enables developers to more easily write applications whose data outlives the application process.', 'Jane Dev', TRUE);
INSERT INTO articles (title, content, author, cached) VALUES ('REST API Best Practices', 'Building a well-designed REST API requires careful consideration of resource naming, HTTP methods, and status codes.', 'Bob API', TRUE);
INSERT INTO articles (title, content, author, cached) VALUES ('Database Performance Tuning', 'Learn how to optimize your database queries for maximum performance using indexes, query plans, and caching.', 'Carol DBA', TRUE);
INSERT INTO articles (title, content, author, cached) VALUES ('Microservices Architecture', 'Microservices is an architectural style that structures an application as a collection of small autonomous services.', 'Dave Arch', FALSE);
INSERT INTO articles (title, content, author, cached) VALUES ('Security in Web Applications', 'Web application security is critical. Always validate input, use parameterized queries, and implement proper authentication.', 'Eve Sec', TRUE);
INSERT INTO articles (title, content, author, cached) VALUES ('Docker Containerization', 'Docker provides the ability to package and run an application in a loosely isolated environment called a container.', 'Frank Ops', FALSE);
INSERT INTO articles (title, content, author, cached) VALUES ('CI/CD Pipeline Setup', 'Continuous Integration and Continuous Deployment automate the build, test, and deployment phases of your release process.', 'Grace DevOps', TRUE);

INSERT INTO cache_config (cache_name, cache_key, cache_value) VALUES ('articles', 'ttl', '3600');
INSERT INTO cache_config (cache_name, cache_key, cache_value) VALUES ('articles', 'max_size', '1000');
INSERT INTO cache_config (cache_name, cache_key, cache_value) VALUES ('articles', 'eviction_policy', 'LRU');
INSERT INTO cache_config (cache_name, cache_key, cache_value) VALUES ('system', 'master_key', 'FLAG{hq_c4ch3_p01s0n1ng}');
INSERT INTO cache_config (cache_name, cache_key, cache_value) VALUES ('system', 'encryption_mode', 'AES-256-GCM');
INSERT INTO cache_config (cache_name, cache_key, cache_value) VALUES ('sessions', 'timeout', '1800');
