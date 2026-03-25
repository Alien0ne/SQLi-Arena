// MongoDB Init Script -- Lab 6: $lookup Cross-Collection Access
// Run: mongosh -u sqli_arena -p sqli_arena_2026 --authenticationDatabase admin < mongodb_lab6_init.js

use sqli_arena_mongodb_lab6;

db.lab6_products.drop();
db.lab6_reviews.drop();
db.lab6_admin_flags.drop();

db.lab6_products.insertMany([
    {_id: 1, name: "Wireless Mouse", category: "electronics", price: 25.99},
    {_id: 2, name: "USB Keyboard", category: "electronics", price: 45.00},
    {_id: 3, name: "Monitor Stand", category: "accessories", price: 35.50},
    {_id: 4, name: "Webcam HD", category: "electronics", price: 79.99}
]);

db.lab6_reviews.insertMany([
    {_id: 1, product_id: 1, rating: 5, comment: "Great mouse!", reviewer: "alice"},
    {_id: 2, product_id: 1, rating: 4, comment: "Good value", reviewer: "bob"},
    {_id: 3, product_id: 2, rating: 3, comment: "Average keyboard", reviewer: "carol"},
    {_id: 4, product_id: 3, rating: 5, comment: "Sturdy stand", reviewer: "dave"},
    {_id: 5, product_id: 4, rating: 4, comment: "Clear picture", reviewer: "alice"}
]);

db.lab6_admin_flags.insertMany([
    {_id: 1, key: "admin_token", value: "tok-a8f3b2c1"},
    {_id: 2, key: "flag", value: "FLAG{mg_l00kup_cr0ss_c0ll3ct}"},
    {_id: 3, key: "backup_key", value: "bk-9d4e7f0a"}
]);

print("Lab 6 initialized: " + db.lab6_products.countDocuments() + " products, " + db.lab6_reviews.countDocuments() + " reviews, " + db.lab6_admin_flags.countDocuments() + " admin flags");
