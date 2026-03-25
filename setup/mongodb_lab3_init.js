// MongoDB Init Script -- Lab 3: Blind Extraction via $regex
// Run: mongosh < mongodb_lab3_init.js

use sqli_arena_mongodb_lab3;

db.lab3_users.drop();

db.lab3_users.insertMany([
    {
        _id: 1,
        username: "admin",
        password: "FLAG{mg_r3g3x_bl1nd_3xtr4ct}",
        email: "admin@nosql-corp.io",
        role: "admin"
    },
    {
        _id: 2,
        username: "editor",
        password: "editPass99",
        email: "editor@nosql-corp.io",
        role: "editor"
    },
    {
        _id: 3,
        username: "viewer",
        password: "viewOnly!",
        email: "viewer@nosql-corp.io",
        role: "viewer"
    }
]);

print("Lab 3 initialized: " + db.lab3_users.countDocuments() + " users inserted");
