const { getDb } = require('../db');

// Lab 5: Nested Query DoS + Data Extraction
// Vulnerability: No query depth limit. Deeply nested queries can access
// fields that are normally filtered at shallow depths.
// At depth 1, privateNotes returns null (access control).
// At depth 3+, the access control check is bypassed.

// We track the current nesting depth via the resolver info
function getDepth(info) {
  let depth = 0;
  let node = info.path;
  while (node) {
    if (typeof node.key === 'string') {
      depth++;
    }
    node = node.prev;
  }
  return depth;
}

const typeDefs = `#graphql
  type Note {
    id: Int!
    content: String!
    secret: String
  }

  type User {
    id: Int!
    username: String!
    email: String!
    friends: [User!]!
    privateNotes: [Note]
  }

  type Query {
    user(id: Int!): User
    users: [User!]!
  }
`;

const resolvers = {
  Query: {
    user: (_, { id }) => {
      const db = getDb();
      return db.prepare('SELECT * FROM lab5_users WHERE id = ?').get(id);
    },
    users: () => {
      const db = getDb();
      return db.prepare('SELECT * FROM lab5_users').all();
    },
  },

  User: {
    friends: (parent) => {
      const db = getDb();
      return db.prepare(`
        SELECT u.* FROM lab5_users u
        JOIN lab5_friends f ON u.id = f.friend_id
        WHERE f.user_id = ?
      `).all(parent.id);
    },

    privateNotes: (parent, _args, _context, info) => {
      const depth = getDepth(info);
      const db = getDb();

      // Access control: at shallow depth (depth <= 3), privateNotes are
      // restricted. Only the user's own non-secret notes are returned.
      // At depth 4+ (deeply nested), the check is "forgotten" / bypassed
      // due to a simulated developer oversight.
      if (depth <= 3) {
        // "Secure" path: return null to hide private notes at top level
        return null;
      }

      // Deep nesting bypass: return full notes including secrets
      return db.prepare('SELECT * FROM lab5_notes WHERE user_id = ?').all(parent.id);
    },
  },

  Note: {
    secret: (parent, _args, _context, info) => {
      // At shallow depths, secret is hidden
      const depth = getDepth(info);
      if (depth <= 4) {
        return null;
      }
      return parent.secret;
    },
  },
};

module.exports = { typeDefs, resolvers };
