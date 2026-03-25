// Lab 3: Blind Extraction via $regex
// Database: sqli_arena_mongodb_lab3 (set by setup script)

db.lab3_users.drop();

db.lab3_users.insertMany([
    {
        username: "admin",
        password: "FLAG{mg_r3g3x_bl1nd_3xtr4ct}",
        email: "admin@nosql-corp.io",
        role: "admin"
    },
    {
        username: "editor",
        password: "editPass99",
        email: "editor@nosql-corp.io",
        role: "editor"
    },
    {
        username: "viewer",
        password: "viewOnly!",
        email: "viewer@nosql-corp.io",
        role: "viewer"
    },
    {
        username: "moderator",
        password: "m0d3r4t3!",
        email: "moderator@nosql-corp.io",
        role: "moderator"
    }
]);

print("Lab 3 initialized: " + db.lab3_users.countDocuments() + " users inserted");
