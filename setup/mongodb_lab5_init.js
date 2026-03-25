// Lab 5: Aggregation Pipeline Injection
// Database: sqli_arena_mongodb_lab5 (set by setup script)

db.lab5_products.drop();
db.lab5_secret_analytics.drop();

db.lab5_products.insertMany([
    { name: "Wireless Mouse", category: "electronics", price: 25.99, stock: 150 },
    { name: "USB Keyboard", category: "electronics", price: 45.00, stock: 80 },
    { name: "Monitor Stand", category: "accessories", price: 35.50, stock: 200 },
    { name: "Webcam HD", category: "electronics", price: 79.99, stock: 45 },
    { name: "Laptop Bag", category: "accessories", price: 55.00, stock: 120 }
]);

db.lab5_secret_analytics.insertMany([
    { key: "api_key", value: "sk-nosql-12345" },
    { key: "flag", value: "FLAG{mg_4ggr3g4t3_p1p3l1n3}" },
    { key: "debug_mode", value: "false" },
    { key: "internal_note", value: "Pipeline injection allows cross-collection access" }
]);

print("Lab 5 initialized: " + db.lab5_products.countDocuments() + " products, " + db.lab5_secret_analytics.countDocuments() + " analytics entries");
