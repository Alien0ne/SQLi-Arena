const Database = require('better-sqlite3');
const path = require('path');

const DB_PATH = path.join(__dirname, 'data', 'graphql_labs.db');

let db;

function getDb() {
  if (!db) {
    const fs = require('fs');
    const dir = path.dirname(DB_PATH);
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
    }
    db = new Database(DB_PATH);
    db.pragma('journal_mode = WAL');
    db.pragma('foreign_keys = ON');
  }
  return db;
}

function initializeDatabase() {
  const db = getDb();

  // ===== Lab 1: Introspection Schema Discovery =====
  db.exec(`
    CREATE TABLE IF NOT EXISTS lab1_users (
      id INTEGER PRIMARY KEY,
      username TEXT NOT NULL,
      email TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT 'user'
    );

    CREATE TABLE IF NOT EXISTS lab1_products (
      id INTEGER PRIMARY KEY,
      name TEXT NOT NULL,
      price REAL NOT NULL,
      category TEXT NOT NULL DEFAULT 'general'
    );

    CREATE TABLE IF NOT EXISTS lab1_secretflags (
      id INTEGER PRIMARY KEY,
      flag TEXT NOT NULL,
      description TEXT NOT NULL
    );
  `);

  // Seed lab1 data (only if empty)
  const lab1UserCount = db.prepare('SELECT COUNT(*) as c FROM lab1_users').get().c;
  if (lab1UserCount === 0) {
    db.prepare('INSERT INTO lab1_users (id, username, email, role) VALUES (?, ?, ?, ?)').run(1, 'alice', 'alice@example.com', 'user');
    db.prepare('INSERT INTO lab1_users (id, username, email, role) VALUES (?, ?, ?, ?)').run(2, 'admin', 'admin@sqli-arena.local', 'admin');
  }

  const lab1ProductCount = db.prepare('SELECT COUNT(*) as c FROM lab1_products').get().c;
  if (lab1ProductCount === 0) {
    db.prepare('INSERT INTO lab1_products (id, name, price, category) VALUES (?, ?, ?, ?)').run(1, 'Widget', 9.99, 'tools');
    db.prepare('INSERT INTO lab1_products (id, name, price, category) VALUES (?, ?, ?, ?)').run(2, 'Gadget', 19.99, 'electronics');
  }

  const lab1FlagCount = db.prepare('SELECT COUNT(*) as c FROM lab1_secretflags').get().c;
  if (lab1FlagCount === 0) {
    db.prepare('INSERT INTO lab1_secretflags (id, flag, description) VALUES (?, ?, ?)').run(1, 'FLAG{gq_1ntr0sp3ct_sch3m4}', 'Hidden flag discoverable via introspection');
  }

  // ===== Lab 2: Field Suggestion Exploitation =====
  db.exec(`
    CREATE TABLE IF NOT EXISTS lab2_users (
      id INTEGER PRIMARY KEY,
      username TEXT NOT NULL,
      email TEXT NOT NULL,
      secretFlag TEXT DEFAULT NULL,
      internalNotes TEXT DEFAULT NULL
    );

    CREATE TABLE IF NOT EXISTS lab2_products (
      id INTEGER PRIMARY KEY,
      name TEXT NOT NULL,
      price REAL NOT NULL
    );
  `);

  const lab2UserCount = db.prepare('SELECT COUNT(*) as c FROM lab2_users').get().c;
  if (lab2UserCount === 0) {
    db.prepare('INSERT INTO lab2_users (id, username, email, secretFlag, internalNotes) VALUES (?, ?, ?, ?, ?)').run(1, 'alice', 'alice@example.com', 'FLAG{gq_f13ld_sugg3st10n}', 'Alice has admin access to staging');
    db.prepare('INSERT INTO lab2_users (id, username, email, secretFlag, internalNotes) VALUES (?, ?, ?, ?, ?)').run(2, 'bob', 'bob@example.com', 'no_flag_here', 'Bob is a regular user');
  }

  const lab2ProductCount = db.prepare('SELECT COUNT(*) as c FROM lab2_products').get().c;
  if (lab2ProductCount === 0) {
    db.prepare('INSERT INTO lab2_products (id, name, price) VALUES (?, ?, ?)').run(1, 'Widget', 9.99);
    db.prepare('INSERT INTO lab2_products (id, name, price) VALUES (?, ?, ?)').run(2, 'Gadget', 19.99);
  }

  // ===== Lab 3: Alias-Based Auth Bypass =====
  db.exec(`
    CREATE TABLE IF NOT EXISTS lab3_users (
      id INTEGER PRIMARY KEY,
      username TEXT NOT NULL UNIQUE,
      email TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT 'user',
      otp_code TEXT DEFAULT NULL
    );
  `);

  const lab3UserCount = db.prepare('SELECT COUNT(*) as c FROM lab3_users').get().c;
  if (lab3UserCount === 0) {
    db.prepare('INSERT INTO lab3_users (id, username, email, role, otp_code) VALUES (?, ?, ?, ?, ?)').run(1, 'admin', 'admin@sqli-arena.local', 'admin', '1337');
    db.prepare('INSERT INTO lab3_users (id, username, email, role, otp_code) VALUES (?, ?, ?, ?, ?)').run(2, 'alice', 'alice@example.com', 'user', '4242');
    db.prepare('INSERT INTO lab3_users (id, username, email, role, otp_code) VALUES (?, ?, ?, ?, ?)').run(3, 'bob', 'bob@example.com', 'user', '9999');
  }

  // ===== Lab 4: Batching Attack =====
  db.exec(`
    CREATE TABLE IF NOT EXISTS lab4_users (
      id INTEGER PRIMARY KEY,
      username TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT 'user'
    );

    CREATE TABLE IF NOT EXISTS lab4_products (
      id INTEGER PRIMARY KEY,
      name TEXT NOT NULL,
      price REAL NOT NULL
    );

    CREATE TABLE IF NOT EXISTS lab4_dashboard (
      id INTEGER PRIMARY KEY,
      title TEXT NOT NULL,
      content TEXT NOT NULL,
      accessLevel TEXT NOT NULL DEFAULT 'user'
    );
  `);

  const lab4UserCount = db.prepare('SELECT COUNT(*) as c FROM lab4_users').get().c;
  if (lab4UserCount === 0) {
    db.prepare('INSERT INTO lab4_users (id, username, role) VALUES (?, ?, ?)').run(1, 'alice', 'user');
    db.prepare('INSERT INTO lab4_users (id, username, role) VALUES (?, ?, ?)').run(2, 'admin', 'admin');
  }

  const lab4ProductCount = db.prepare('SELECT COUNT(*) as c FROM lab4_products').get().c;
  if (lab4ProductCount === 0) {
    db.prepare('INSERT INTO lab4_products (id, name, price) VALUES (?, ?, ?)').run(1, 'Widget', 9.99);
    db.prepare('INSERT INTO lab4_products (id, name, price) VALUES (?, ?, ?)').run(2, 'Gadget', 19.99);
  }

  const lab4DashCount = db.prepare('SELECT COUNT(*) as c FROM lab4_dashboard').get().c;
  if (lab4DashCount === 0) {
    db.prepare('INSERT INTO lab4_dashboard (id, title, content, accessLevel) VALUES (?, ?, ?, ?)').run(1, 'Public Dashboard', 'Welcome to the public dashboard.', 'user');
    db.prepare('INSERT INTO lab4_dashboard (id, title, content, accessLevel) VALUES (?, ?, ?, ?)').run(2, 'Admin Secrets', 'FLAG{gq_b4tch1ng_4tt4ck}', 'admin');
  }

  // ===== Lab 5: Nested Query DoS + Data Extraction =====
  db.exec(`
    CREATE TABLE IF NOT EXISTS lab5_users (
      id INTEGER PRIMARY KEY,
      username TEXT NOT NULL,
      email TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS lab5_friends (
      user_id INTEGER NOT NULL,
      friend_id INTEGER NOT NULL,
      PRIMARY KEY (user_id, friend_id),
      FOREIGN KEY (user_id) REFERENCES lab5_users(id),
      FOREIGN KEY (friend_id) REFERENCES lab5_users(id)
    );

    CREATE TABLE IF NOT EXISTS lab5_notes (
      id INTEGER PRIMARY KEY,
      user_id INTEGER NOT NULL,
      content TEXT NOT NULL,
      secret TEXT DEFAULT NULL,
      FOREIGN KEY (user_id) REFERENCES lab5_users(id)
    );
  `);

  const lab5UserCount = db.prepare('SELECT COUNT(*) as c FROM lab5_users').get().c;
  if (lab5UserCount === 0) {
    db.prepare('INSERT INTO lab5_users (id, username, email) VALUES (?, ?, ?)').run(1, 'admin', 'admin@sqli-arena.local');
    db.prepare('INSERT INTO lab5_users (id, username, email) VALUES (?, ?, ?)').run(2, 'alice', 'alice@example.com');
    db.prepare('INSERT INTO lab5_users (id, username, email) VALUES (?, ?, ?)').run(3, 'bob', 'bob@example.com');
    db.prepare('INSERT INTO lab5_users (id, username, email) VALUES (?, ?, ?)').run(4, 'charlie', 'charlie@example.com');

    // Friendship graph: alice->bob->charlie->admin, admin->alice
    db.prepare('INSERT INTO lab5_friends (user_id, friend_id) VALUES (?, ?)').run(2, 3); // alice -> bob
    db.prepare('INSERT INTO lab5_friends (user_id, friend_id) VALUES (?, ?)').run(3, 4); // bob -> charlie
    db.prepare('INSERT INTO lab5_friends (user_id, friend_id) VALUES (?, ?)').run(4, 1); // charlie -> admin
    db.prepare('INSERT INTO lab5_friends (user_id, friend_id) VALUES (?, ?)').run(1, 2); // admin -> alice

    // Notes
    db.prepare('INSERT INTO lab5_notes (id, user_id, content, secret) VALUES (?, ?, ?, ?)').run(1, 1, 'Admin private notes', 'FLAG{gq_n3st3d_d33p_qu3ry}');
    db.prepare('INSERT INTO lab5_notes (id, user_id, content, secret) VALUES (?, ?, ?, ?)').run(2, 2, 'Alice notes', 'nothing special');
    db.prepare('INSERT INTO lab5_notes (id, user_id, content, secret) VALUES (?, ?, ?, ?)').run(3, 3, 'Bob notes', null);
  }

  console.log('[db] Database initialized and seeded.');
  return db;
}

module.exports = { getDb, initializeDatabase };
