// Lab 8: BSON $type / $exists Enumeration
// Database: sqli_arena_mongodb_lab8 (set by setup script)

db.lab8_documents.drop();

db.lab8_documents.insertMany([
    {
        username: "admin",
        password: "FLAG{mg_bs0n_typ3_3x1sts}",
        email: "admin@nosql-corp.io",
        role: "admin",
        secret_note: "The flag is the admin password",
        login_count: 42
    },
    {
        username: "manager",
        password: "mgr2026!!",
        email: "mgr@nosql-corp.io",
        role: "manager",
        login_count: 15
    },
    {
        username: "temp",
        password: "temp1234",
        email: "temp@nosql-corp.io",
        role: "temp",
        login_count: 3
    },
    {
        username: "security",
        password: "s3cur1ty!",
        email: "security@nosql-corp.io",
        role: "security",
        login_count: 28
    }
]);

print("Lab 8 initialized: " + db.lab8_documents.countDocuments() + " documents inserted");
