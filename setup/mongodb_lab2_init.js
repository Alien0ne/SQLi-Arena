// Lab 2: Auth Bypass via $gt Operator
// Database: sqli_arena_mongodb_lab2 (set by setup script)

db.lab2_users.drop();

db.lab2_users.insertMany([
    {
        username: "admin",
        password: "FLAG{mg_gt_0p3r4t0r_byp4ss}",
        email: "admin@nosql-corp.io",
        role: "admin"
    },
    {
        username: "analyst",
        password: "analyst2026",
        email: "analyst@nosql-corp.io",
        role: "user"
    },
    {
        username: "intern",
        password: "intern!",
        email: "intern@nosql-corp.io",
        role: "user"
    },
    {
        username: "contractor",
        password: "c0ntr4ct0r",
        email: "contractor@nosql-corp.io",
        role: "user"
    }
]);

print("Lab 2 initialized: " + db.lab2_users.countDocuments() + " users inserted");
