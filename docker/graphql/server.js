const express = require('express');
const cors = require('cors');
const bodyParser = require('body-parser');
const { ApolloServer } = require('@apollo/server');
const { expressMiddleware } = require('@apollo/server/express4');

const { initializeDatabase } = require('./db');

// Lab schemas
const lab1 = require('./schema/lab1');
const lab2 = require('./schema/lab2');
const lab3 = require('./schema/lab3');
const lab4 = require('./schema/lab4');
const lab5 = require('./schema/lab5');

const PORT = 4000;

async function startServer() {
  // Initialize database
  initializeDatabase();

  const app = express();

  // Global middleware
  app.use(cors());
  app.use(bodyParser.json({ limit: '1mb' }));

  // Health endpoint
  app.get('/health', (_req, res) => {
    res.json({ status: 'ok' });
  });

  // =========================================================
  // Lab 1: Introspection Schema Discovery
  // Introspection ENABLED (Apollo 4 default)
  // =========================================================
  const server1 = new ApolloServer({
    typeDefs: lab1.typeDefs,
    resolvers: lab1.resolvers,
    introspection: true,
  });
  await server1.start();
  app.use('/graphql/lab1', expressMiddleware(server1));

  // =========================================================
  // Lab 2: Field Suggestion Exploitation
  // Introspection DISABLED, but field suggestions in errors are enabled
  // Apollo Server 4 includes field suggestions in validation errors by default
  // =========================================================
  const server2 = new ApolloServer({
    typeDefs: lab2.typeDefs,
    resolvers: lab2.resolvers,
    introspection: false,
    // Field suggestions are part of GraphQL validation errors and
    // are enabled by default in Apollo Server 4. We keep them on.
  });
  await server2.start();
  app.use('/graphql/lab2', expressMiddleware(server2));

  // =========================================================
  // Lab 3: Alias-Based Auth Bypass
  // Rate limiting on HTTP request level, bypassed by aliases
  // =========================================================

  // Per-IP request counter for lab3
  const lab3RequestCounts = new Map();
  const LAB3_RATE_LIMIT = 3;
  const LAB3_WINDOW_MS = 60000;

  const server3 = new ApolloServer({
    typeDefs: lab3.typeDefs,
    resolvers: lab3.resolvers,
    introspection: true,
  });
  await server3.start();

  // Custom rate-limiting middleware that counts HTTP requests, not aliases
  app.use('/graphql/lab3', (req, res, next) => {
    if (req.method !== 'POST') return next();

    const ip = req.ip || req.connection.remoteAddress || 'unknown';
    const now = Date.now();
    let entry = lab3RequestCounts.get(ip);

    if (!entry || (now - entry.windowStart) > LAB3_WINDOW_MS) {
      entry = { windowStart: now, count: 0 };
      lab3RequestCounts.set(ip, entry);
    }

    entry.count++;

    // The rate limiter counts HTTP requests, not GraphQL operations.
    // A single request with 100 aliases counts as 1 request.
    // This is the intentional vulnerability.
    if (entry.count > LAB3_RATE_LIMIT) {
      return res.status(429).json({
        errors: [{
          message: 'Rate limit exceeded. Maximum 3 requests per minute.',
          extensions: { code: 'RATE_LIMITED' },
        }],
      });
    }

    next();
  });

  app.use('/graphql/lab3', expressMiddleware(server3));

  // =========================================================
  // Lab 4: Batching Attack
  // Accepts array of queries; auth context leaks between queries in batch
  // =========================================================
  const server4 = new ApolloServer({
    typeDefs: lab4.typeDefs,
    resolvers: lab4.resolvers,
    introspection: true,
  });
  await server4.start();

  // Custom handler for batched queries
  app.use('/graphql/lab4', async (req, res) => {
    if (req.method !== 'POST') {
      return res.status(405).json({ errors: [{ message: 'Method not allowed' }] });
    }

    const body = req.body;

    // Check if this is a batched request (array of operations)
    if (Array.isArray(body)) {
      // Reset auth context at the start of each batch
      lab4.resetBatchAuth();

      const results = [];
      for (const operation of body) {
        try {
          const result = await server4.executeOperation({
            query: operation.query,
            variables: operation.variables || {},
            operationName: operation.operationName || undefined,
          });

          // Apollo Server 4 executeOperation returns a response object
          if (result.body.kind === 'single') {
            results.push(result.body.singleResult);
          } else {
            results.push({ errors: [{ message: 'Unexpected response type' }] });
          }
        } catch (err) {
          results.push({ errors: [{ message: err.message }] });
        }
      }

      return res.json(results);
    }

    // Single query - reset auth and handle normally
    lab4.resetBatchAuth();

    try {
      const result = await server4.executeOperation({
        query: body.query,
        variables: body.variables || {},
        operationName: body.operationName || undefined,
      });

      if (result.body.kind === 'single') {
        return res.json(result.body.singleResult);
      }
      return res.json({ errors: [{ message: 'Unexpected response type' }] });
    } catch (err) {
      return res.json({ errors: [{ message: err.message }] });
    }
  });

  // =========================================================
  // Lab 5: Nested Query DoS + Data Extraction
  // No query depth limit. Deep nesting bypasses access control.
  // =========================================================
  const server5 = new ApolloServer({
    typeDefs: lab5.typeDefs,
    resolvers: lab5.resolvers,
    introspection: true,
    // Intentionally NO query depth limiting plugin
  });
  await server5.start();
  app.use('/graphql/lab5', expressMiddleware(server5));

  // =========================================================
  // Start Express server
  // =========================================================
  app.listen(PORT, '0.0.0.0', () => {
    console.log(`[server] GraphQL labs server running on http://0.0.0.0:${PORT}`);
    console.log(`[server] Lab 1 (Introspection):     POST /graphql/lab1`);
    console.log(`[server] Lab 2 (Field Suggestion):   POST /graphql/lab2`);
    console.log(`[server] Lab 3 (Alias Auth Bypass):  POST /graphql/lab3`);
    console.log(`[server] Lab 4 (Batching Attack):    POST /graphql/lab4`);
    console.log(`[server] Lab 5 (Nested Query DoS):   POST /graphql/lab5`);
    console.log(`[server] Health check:               GET  /health`);
  });
}

startServer().catch((err) => {
  console.error('[server] Failed to start:', err);
  process.exit(1);
});
