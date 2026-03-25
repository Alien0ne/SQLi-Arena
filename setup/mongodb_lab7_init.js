// MongoDB Init Script -- Lab 7: JSON Parameter Pollution
// Run: mongosh -u sqli_arena -p sqli_arena_2026 --authenticationDatabase admin < mongodb_lab7_init.js

use sqli_arena_mongodb_lab7;

db.lab7_users.drop();

db.lab7_users.insertMany([
    {
        _id: 1,
        username: "admin",
        password: "FLAG{mg_js0n_p4r4m_p0llut3}",
        email: "admin@nosql-corp.io",
        role: "admin"
    },
    {
        _id: 2,
        username: "support",
        password: "supp0rt2026",
        email: "support@nosql-corp.io",
        role: "support"
    },
    {
        _id: 3,
        username: "demo",
        password: "demoAccount",
        email: "demo@nosql-corp.io",
        role: "demo"
    }
]);

print("Lab 7 initialized: " + db.lab7_users.countDocuments() + " users inserted");
