// Lab 6: $lookup Cross-Collection Access
// Database: sqli_arena_mongodb_lab6 (set by setup script)

db.lab6_products.drop();
db.lab6_reviews.drop();
db.lab6_admin_flags.drop();

db.lab6_products.insertMany([
    { name: "Wireless Mouse", category: "electronics", price: 25.99 },
    { name: "USB Keyboard", category: "electronics", price: 45.00 },
    { name: "Monitor Stand", category: "accessories", price: 35.50 },
    { name: "Webcam HD", category: "electronics", price: 79.99 }
]);

// Store product _ids for reviews (inserted docs get auto-generated ObjectIds)
var products = db.lab6_products.find().toArray();

db.lab6_reviews.insertMany([
    { product_id: products[0]._id, rating: 5, comment: "Great mouse!", reviewer: "alice" },
    { product_id: products[0]._id, rating: 4, comment: "Good value", reviewer: "bob" },
    { product_id: products[1]._id, rating: 3, comment: "Average keyboard", reviewer: "carol" },
    { product_id: products[2]._id, rating: 5, comment: "Sturdy stand", reviewer: "dave" },
    { product_id: products[3]._id, rating: 4, comment: "Clear picture", reviewer: "alice" }
]);

db.lab6_admin_flags.insertMany([
    { key: "admin_token", value: "tok-a8f3b2c1" },
    { key: "flag", value: "FLAG{mg_l00kup_cr0ss_c0ll3ct}" },
    { key: "backup_key", value: "bk-9d4e7f0a" }
]);

print("Lab 6 initialized: " + db.lab6_products.countDocuments() + " products, " + db.lab6_reviews.countDocuments() + " reviews, " + db.lab6_admin_flags.countDocuments() + " admin flags");
