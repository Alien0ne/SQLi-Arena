const { getDb } = require('../db');

// Lab 1: Introspection Schema Discovery
// Vulnerability: Introspection is enabled (Apollo default).
// Hidden type SecretFlag is discoverable via __schema query.

const typeDefs = `#graphql
  type User {
    id: Int!
    username: String!
    email: String!
    role: String!
  }

  type Product {
    id: Int!
    name: String!
    price: Float!
    category: String!
  }

  type SecretFlag {
    id: Int!
    flag: String!
    description: String!
  }

  type MutationResult {
    success: Boolean!
    message: String
  }

  type Query {
    user(id: Int!): User
    users: [User!]!
    product(id: Int!): Product
    products: [Product!]!
    secretflags: [SecretFlag!]!
  }

  type Mutation {
    updateProfile(username: String!, email: String!): MutationResult!
  }
`;

const resolvers = {
  Query: {
    user: (_, { id }) => {
      const db = getDb();
      return db.prepare('SELECT * FROM lab1_users WHERE id = ?').get(id);
    },
    users: () => {
      const db = getDb();
      return db.prepare('SELECT * FROM lab1_users').all();
    },
    product: (_, { id }) => {
      const db = getDb();
      return db.prepare('SELECT * FROM lab1_products WHERE id = ?').get(id);
    },
    products: () => {
      const db = getDb();
      return db.prepare('SELECT * FROM lab1_products').all();
    },
    secretflags: () => {
      const db = getDb();
      return db.prepare('SELECT * FROM lab1_secretflags').all();
    },
  },
  Mutation: {
    updateProfile: (_, { username, email }) => {
      return { success: true, message: `Profile updated for ${username}` };
    },
  },
};

module.exports = { typeDefs, resolvers };
