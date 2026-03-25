const { getDb } = require('../db');

// Lab 2: Field Suggestion Exploitation
// Vulnerability: Introspection is DISABLED, but Apollo's error messages
// suggest similar field names when you query invalid fields.
// User type has hidden fields: secretFlag, internalNotes

const typeDefs = `#graphql
  type User {
    id: Int!
    username: String!
    email: String!
    secretFlag: String
    internalNotes: String
  }

  type Product {
    id: Int!
    name: String!
    price: Float!
  }

  type Query {
    user(id: Int!): User
    users: [User!]!
    product(id: Int!): Product
  }
`;

const resolvers = {
  Query: {
    user: (_, { id }) => {
      const db = getDb();
      return db.prepare('SELECT * FROM lab2_users WHERE id = ?').get(id);
    },
    users: () => {
      const db = getDb();
      return db.prepare('SELECT * FROM lab2_users').all();
    },
    product: (_, { id }) => {
      const db = getDb();
      return db.prepare('SELECT * FROM lab2_products WHERE id = ?').get(id);
    },
  },
};

module.exports = { typeDefs, resolvers };
