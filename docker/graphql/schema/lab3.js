const { getDb } = require('../db');

// Lab 3: Alias-Based Auth Bypass
// Vulnerability: Rate limiting checks the number of top-level query
// operations but aliases bypass it. Using aliases like a1:verifyOTP,
// a2:verifyOTP sends multiple attempts in a single request.

// In-memory rate limiter (resets on server restart)
const rateLimitStore = new Map();
const RATE_LIMIT_MAX = 3;
const RATE_LIMIT_WINDOW_MS = 60000; // 1 minute

function checkRateLimit(key) {
  const now = Date.now();
  const entry = rateLimitStore.get(key);

  if (!entry || (now - entry.windowStart) > RATE_LIMIT_WINDOW_MS) {
    // New window
    rateLimitStore.set(key, { windowStart: now, count: 1 });
    return true;
  }

  if (entry.count >= RATE_LIMIT_MAX) {
    return false; // Rate limited
  }

  entry.count++;
  return true;
}

function resetRateLimit(key) {
  rateLimitStore.delete(key);
}

const typeDefs = `#graphql
  type User {
    id: Int!
    username: String!
    email: String!
    role: String!
  }

  type LoginResult {
    success: Boolean!
    message: String
    otpSent: Boolean
  }

  type OTPResult {
    success: Boolean!
    message: String
    token: String
  }

  type Query {
    user(id: Int!): User
    login(username: String!, password: String!): LoginResult!
    verifyOTP(username: String!, otp: String!): OTPResult!
  }
`;

const resolvers = {
  Query: {
    user: (_, { id }) => {
      const db = getDb();
      return db.prepare('SELECT id, username, email, role FROM lab3_users WHERE id = ?').get(id);
    },

    login: (_, { username, password }) => {
      // Rate limit check per IP (simplified: per username)
      const limitKey = `login:${username}`;
      if (!checkRateLimit(limitKey)) {
        return {
          success: false,
          message: 'Rate limit exceeded. Try again later.',
          otpSent: false,
        };
      }

      const db = getDb();
      const user = db.prepare('SELECT * FROM lab3_users WHERE username = ?').get(username);
      if (!user) {
        return { success: false, message: 'User not found', otpSent: false };
      }

      // Simplified: any password works for login, OTP is the real gate
      return {
        success: true,
        message: `OTP sent to ${user.email}`,
        otpSent: true,
      };
    },

    verifyOTP: (_, { username, otp }) => {
      // Rate limit check - this only increments ONCE per request at the
      // resolver level, but aliases call this resolver multiple times
      // in a single HTTP request. The vulnerability is that rate limiting
      // is checked per resolver call, and all alias calls in a single
      // request share the same rate limit counter. But since aliases
      // execute in the same request, an attacker can send hundreds of
      // OTP attempts in a single HTTP request before the rate limit kicks in.
      //
      // The "fix" the lab demonstrates: rate limiting counts HTTP requests,
      // not individual resolver invocations. So aliases bypass it.

      // We intentionally do NOT rate limit verifyOTP - the rate limit
      // is only on the HTTP request level (checked in middleware),
      // which counts as 1 request regardless of how many aliases are used.

      const db = getDb();
      const user = db.prepare('SELECT * FROM lab3_users WHERE username = ?').get(username);

      if (!user) {
        return { success: false, message: 'User not found', token: null };
      }

      if (user.otp_code === otp) {
        const token = user.role === 'admin'
          ? 'FLAG{gq_4l14s_4uth_byp4ss}'
          : `token_${user.username}_${Date.now()}`;
        return {
          success: true,
          message: 'OTP verified successfully',
          token: token,
        };
      }

      return { success: false, message: 'Invalid OTP code', token: null };
    },
  },
};

module.exports = { typeDefs, resolvers, resetRateLimit };
