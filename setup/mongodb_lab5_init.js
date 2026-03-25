// MongoDB Init Script -- Lab 5: Aggregation Pipeline Injection
// Run: mongosh -u sqli_arena -p sqli_arena_2026 --authenticationDatabase admin < mongodb_lab5_init.js

use sqli_arena_mongodb_lab5;

db.lab5_products.drop();
db.lab5_secret_analytics.drop();

db.lab5_products.insertMany([
    {_id: 1, name: "Wireless Mouse", category: "electronics", price: 25.99, stock: 150},
    {_id: 2, name: "USB Keyboard", category: "electronics", price: 45.00, stock: 80},
    {_id: 3, name: "Monitor Stand", category: "accessories", price: 35.50, stock: 200},
    {_id: 4, name: "Webcam HD", category: "electronics", price: 79.99, stock: 45},
    {_id: 5, name: "Laptop Bag", category: "accessories", price: 55.00, stock: 120},
    {_id: 6, name: "HDMI Cable", category: "cables", price: 12.99, stock: 300}
]);

db.lab5_secret_analytics.insertMany([
    {_id: 1, key: "api_key", value: "sk-nosql-12345"},
    {_id: 2, key: "flag", value: "FLAG{mg_4ggr3g4t3_p1p3l1n3}"},
    {_id: 3, key: "debug_mode", value: "false"},
    {_id: 4, key: "internal_note", value: "Pipeline injection allows cross-collection access"}
]);

print("Lab 5 initialized: " + db.lab5_products.countDocuments() + " products, " + db.lab5_secret_analytics.countDocuments() + " analytics entries");
