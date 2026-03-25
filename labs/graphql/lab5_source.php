<?php
// ============================================================
// Lab 5: Nested Query DoS + Data Extraction (Source Code)
// ============================================================
// Engine: GraphQL (Simulated)
// Data: /home/kali/SQLi-Arena/data/graphql/lab5.json
// ============================================================

// Type definitions with circular reference:
//   User { id, name, email, posts: [Post], secretField }
//   Post { id, title, content, author: User }
//
// User.posts -> Post[] and Post.author -> User create a cycle.
// With no depth limit, queries can nest infinitely.

// The resolver processes nested selections recursively:
function resolveFields($typeName, $data, $fieldSelection, $depth) {
    foreach ($fields as $field) {
        if ($field === 'posts' && $typeName === 'User') {
            // Resolve Post[] for this user
            foreach ($user->posts as $post) {
                resolveFields('Post', $post, $field->subfields, $depth + 1);
                //                                               ^^^^^^^^^
                //                                       No depth check!
            }
        }
        if ($field === 'author' && $typeName === 'Post') {
            // Resolve User for this post's author. CIRCULAR!
            resolveFields('User', $post->author, $field->subfields, $depth + 1);
            //                                                      ^^^^^^^^^
            //                                         Back to User, can loop forever!
        }
        if ($field === 'secretField') {
            // Hidden field: accessible at any depth
            $result['secretField'] = $user->secretField;
        }
    }
}

// --- Why is this dangerous? ---

// 1. Denial of Service (DoS):
// { user(id:1) { posts { author { posts { author { posts { author {
//   posts { author { posts { author { posts { author { name } } } } } } } }
// } } } } }
// Each nesting level multiplies the resolver count exponentially.
// Depth 10 with 2 posts per user = 2^10 = 1024 resolver calls.
// Depth 20 = 1,048,576 resolver calls -> server crash!

// 2. Data Extraction:
// The secretField is on the User type but not documented.
// By nesting through posts->author, the attacker reaches
// the User type at any depth and can request secretField.
// { user(id:1) { posts { author { secretField } } } }

// --- Secure Version ---
// 1. Implement query depth limiting (max depth: 5-10)
// 2. Use query complexity analysis (cost per field/connection)
// 3. Set a maximum execution time per query
// 4. Implement field-level authorization on secretField
// 5. Use persisted queries (allowlist of known queries)
// 6. Tools: graphql-depth-limit, graphql-query-complexity
?>
