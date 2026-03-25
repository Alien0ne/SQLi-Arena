// MongoDB Init Script -- Lab 1: Auth Bypass via $ne Operator
// Run: mongosh < mongodb_lab1_init.js
// Or load via mongoimport

use sqli_arena_mongodb_lab1;

db.lab1_users.drop();

db.lab1_users.insertMany([
    {
        _id: 1,
        username: "admin",
        password: "FLAG{mg_n3_0p3r4t0r_byp4ss}",
        email: "admin@nosql-corp.io",
        role: "admin"
    },
    {
        _id: 2,
        username: "guest",
        password: "guest123",
        email: "guest@nosql-corp.io",
        role: "user"
    },
    {
        _id: 3,
        username: "developer",
        password: "dev2026!",
        email: "dev@nosql-corp.io",
        role: "user"
    }
]);

print("Lab 1 initialized: " + db.lab1_users.countDocuments() + " users inserted");
