// Lab 4: Server-Side JavaScript Injection via $where
// Database: sqli_arena_mongodb_lab4 (set by setup script)

db.lab4_users.drop();

db.lab4_users.insertMany([
    {
        username: "admin",
        password: "FLAG{mg_wh3r3_js_1nj3ct}",
        email: "admin@nosql-corp.io",
        role: "admin",
        active: true
    },
    {
        username: "operator",
        password: "ops2026!",
        email: "ops@nosql-corp.io",
        role: "operator",
        active: true
    },
    {
        username: "readonly",
        password: "r3ad0nly",
        email: "ro@nosql-corp.io",
        role: "viewer",
        active: false
    },
    {
        username: "devops",
        password: "d3v0ps!!",
        email: "devops@nosql-corp.io",
        role: "operator",
        active: true
    }
]);

print("Lab 4 initialized: " + db.lab4_users.countDocuments() + " users inserted");
