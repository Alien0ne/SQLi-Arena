const { getDb } = require('../db');

// Lab 4: Batching Attack
// Vulnerability: Server accepts batched queries (array of operations).
// Auth check is per-batch, not per-query. The first query in the batch
// "authenticates" the context, and subsequent queries inherit it.

// Simulated auth state that persists across queries within a single batch
let batchAuthContext = {
  authenticated: false,
  role: 'anonymous',
};

function resetBatchAuth() {
  batchAuthContext = {
    authenticated: false,
    role: 'anonymous',
  };
}

function getBatchAuth() {
  return batchAuthContext;
}

function setBatchAuth(auth) {
  batchAuthContext = { ...batchAuthContext, ...auth };
}

const typeDefs = `#graphql
  type User {
    id: Int!
    username: String!
    role: String!
  }

  type Product {
    id: Int!
    name: String!
    price: Float!
  }

  type Dashboard {
    id: Int!
    title: String!
    content: String!
    accessLevel: String!
  }

  type Query {
    user(id: Int!): User
    product(id: Int!): Product
    secretDashboard: [Dashboard!]!
  }
`;

const resolvers = {
  Query: {
    user: (_, { id }) => {
      const db = getDb();
      const user = db.prepare('SELECT * FROM lab4_users WHERE id = ?').get(id);
      if (user) {
        // Side effect: querying a user "authenticates" as that user in the batch context
        setBatchAuth({ authenticated: true, role: user.role });
      }
      return user;
    },

    product: (_, { id }) => {
      const db = getDb();
      return db.prepare('SELECT * FROM lab4_products WHERE id = ?').get(id);
    },

    secretDashboard: () => {
      const db = getDb();
      const auth = getBatchAuth();

      // Access control: only return admin-level dashboards if authenticated as admin
      // Bug: in a batch, a previous query may have set the auth context
      if (auth.authenticated && auth.role === 'admin') {
        return db.prepare('SELECT * FROM lab4_dashboard').all();
      }

      // Non-admin only sees user-level entries
      return db.prepare("SELECT * FROM lab4_dashboard WHERE accessLevel = 'user'").all();
    },
  },
};

module.exports = { typeDefs, resolvers, resetBatchAuth };
