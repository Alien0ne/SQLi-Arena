// MongoDB Init Script -- Lab 2: Auth Bypass via $gt Operator
// Run: mongosh < mongodb_lab2_init.js

use sqli_arena_mongodb_lab2;

db.lab2_users.drop();

db.lab2_users.insertMany([
    {
        _id: 1,
        username: "admin",
        password: "FLAG{mg_gt_0p3r4t0r_byp4ss}",
        email: "admin@nosql-corp.io",
        role: "admin"
    },
    {
        _id: 2,
        username: "analyst",
        password: "analyst2026",
        email: "analyst@nosql-corp.io",
        role: "user"
    },
    {
        _id: 3,
        username: "intern",
        password: "intern!",
        email: "intern@nosql-corp.io",
        role: "user"
    }
]);

print("Lab 2 initialized: " + db.lab2_users.countDocuments() + " users inserted");
