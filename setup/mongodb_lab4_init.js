// MongoDB Init Script -- Lab 4: Server-Side JS via $where
// Run: mongosh < mongodb_lab4_init.js

use sqli_arena_mongodb_lab4;

db.lab4_users.drop();

db.lab4_users.insertMany([
    {
        _id: 1,
        username: "admin",
        password: "FLAG{mg_wh3r3_js_1nj3ct}",
        email: "admin@nosql-corp.io",
        role: "admin",
        active: true
    },
    {
        _id: 2,
        username: "operator",
        password: "ops2026!",
        email: "ops@nosql-corp.io",
        role: "operator",
        active: true
    },
    {
        _id: 3,
        username: "readonly",
        password: "r3ad0nly",
        email: "ro@nosql-corp.io",
        role: "viewer",
        active: false
    }
]);

print("Lab 4 initialized: " + db.lab4_users.countDocuments() + " users inserted");
