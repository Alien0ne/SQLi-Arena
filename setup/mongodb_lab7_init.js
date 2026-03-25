// Lab 7: JSON Parameter Pollution
// Database: sqli_arena_mongodb_lab7 (set by setup script)

db.lab7_users.drop();

db.lab7_users.insertMany([
    {
        username: "admin",
        password: "FLAG{mg_js0n_p4r4m_p0llut3}",
        email: "admin@nosql-corp.io",
        role: "admin"
    },
    {
        username: "support",
        password: "supp0rt2026",
        email: "support@nosql-corp.io",
        role: "support"
    },
    {
        username: "demo",
        password: "demoAccount",
        email: "demo@nosql-corp.io",
        role: "demo"
    },
    {
        username: "api_user",
        password: "4p1Acc3ss!",
        email: "api@nosql-corp.io",
        role: "service"
    }
]);

print("Lab 7 initialized: " + db.lab7_users.countDocuments() + " users inserted");
