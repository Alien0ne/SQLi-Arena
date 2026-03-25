// MongoDB Init Script -- Lab 8: BSON $type / $exists Enumeration
// Run: mongosh -u sqli_arena -p sqli_arena_2026 --authenticationDatabase admin < mongodb_lab8_init.js

use sqli_arena_mongodb_lab8;

db.lab8_documents.drop();

db.lab8_documents.insertMany([
    {
        _id: 1,
        username: "admin",
        password: "FLAG{mg_bs0n_typ3_3x1sts}",
        email: "admin@nosql-corp.io",
        role: "admin",
        secret_note: "The flag is the admin password",
        login_count: 42
    },
    {
        _id: 2,
        username: "manager",
        password: "mgr2026!!",
        email: "mgr@nosql-corp.io",
        role: "manager",
        login_count: 15
    },
    {
        _id: 3,
        username: "temp",
        password: "temp1234",
        email: "temp@nosql-corp.io",
        role: "temp",
        login_count: 3
    }
]);

print("Lab 8 initialized: " + db.lab8_documents.countDocuments() + " documents inserted");
