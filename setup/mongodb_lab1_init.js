// Lab 1: Auth Bypass via $ne Operator
// Database: sqli_arena_mongodb_lab1 (set by setup script)

db.lab1_users.drop();

db.lab1_users.insertMany([
    {
        username: "admin",
        password: "FLAG{mg_n3_0p3r4t0r_byp4ss}",
        email: "admin@nosql-corp.io",
        role: "admin"
    },
    {
        username: "guest",
        password: "guest123",
        email: "guest@nosql-corp.io",
        role: "user"
    },
    {
        username: "developer",
        password: "dev2026!",
        email: "dev@nosql-corp.io",
        role: "user"
    },
    {
        username: "auditor",
        password: "aud1tR3p0rt",
        email: "auditor@nosql-corp.io",
        role: "user"
    }
]);

print("Lab 1 initialized: " + db.lab1_users.countDocuments() + " users inserted");
