<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';

$db = $_GET['db'] ?? '';
$allowed = ['mysql', 'pgsql', 'sqlite', 'mssql', 'oracle', 'mariadb', 'mongodb', 'redis', 'hql', 'graphql'];

if (!in_array($db, $allowed, true)) {
    die("<div class='container'><div class='card'><h3>Invalid database engine</h3></div></div>");
}

$labs = [
    'mysql' => [
        ['num' => 1,  'title' => 'UNION: String (Single Quote)',       'desc' => 'UNION-based extraction via single-quoted string parameter',                'difficulty' => 'easy'],
        ['num' => 2,  'title' => 'UNION: Integer (No Quotes)',         'desc' => 'No quotes. Inject directly into numeric WHERE clause',                    'difficulty' => 'easy'],
        ['num' => 3,  'title' => 'UNION: String with Parentheses',    'desc' => 'Close parentheses and quotes to break out of nested query',                'difficulty' => 'easy'],
        ['num' => 4,  'title' => 'UNION: Double Quotes',              'desc' => 'Input wrapped in double quotes. Adapt your injection syntax',              'difficulty' => 'medium'],
        ['num' => 5,  'title' => 'Error: ExtractValue / UpdateXML',   'desc' => 'Extract data through XPath error messages only',                            'difficulty' => 'medium'],
        ['num' => 6,  'title' => 'Error: Floor + GROUP BY',           'desc' => 'Duplicate key error via FLOOR(RAND(0)*2) leaks data',                       'difficulty' => 'medium'],
        ['num' => 7,  'title' => 'Error: EXP / BIGINT Overflow',      'desc' => 'Advanced error-based extraction via multiple techniques',                   'difficulty' => 'hard'],
        ['num' => 8,  'title' => 'Error: GTID_SUBSET / JSON_KEYS',    'desc' => 'Modern MySQL 5.7+ error vectors for data extraction',                       'difficulty' => 'hard'],
        ['num' => 9,  'title' => 'Blind Boolean: SUBSTRING + IF',     'desc' => 'True/false oracle: extract data one character at a time',                  'difficulty' => 'medium'],
        ['num' => 10, 'title' => 'Blind Boolean: REGEXP / LIKE',      'desc' => 'Pattern matching as boolean oracle for blind extraction',                   'difficulty' => 'medium'],
        ['num' => 11, 'title' => 'Blind Time: SLEEP() + IF',          'desc' => 'Use SLEEP() and IF() to infer data from response timing',                   'difficulty' => 'hard'],
        ['num' => 12, 'title' => 'Blind Time: Heavy Query',           'desc' => 'SLEEP blocked: use cartesian joins for timing oracle',                     'difficulty' => 'hard'],
        ['num' => 13, 'title' => 'Stacked Queries',                    'desc' => 'Execute multiple statements via mysqli_multi_query',                        'difficulty' => 'hard'],
        ['num' => 14, 'title' => 'INSERT / UPDATE Injection',          'desc' => 'Inject into INSERT VALUES to extract hidden data',                          'difficulty' => 'hard'],
        ['num' => 15, 'title' => 'ORDER BY / GROUP BY Injection',      'desc' => 'Inject into ORDER BY clause. UNION not possible',                          'difficulty' => 'medium'],
        ['num' => 16, 'title' => 'Header Injection: User-Agent',      'desc' => 'SQL injection via HTTP User-Agent header logged to DB',                     'difficulty' => 'hard'],
        ['num' => 17, 'title' => 'Header Injection: Cookie',          'desc' => 'SQL injection via unsanitized cookie value',                                'difficulty' => 'hard'],
        ['num' => 18, 'title' => 'Second-Order Injection',             'desc' => 'Payload stored safely, triggers on a different page',                       'difficulty' => 'insane'],
        ['num' => 19, 'title' => 'WAF Bypass: Keyword Blacklist',     'desc' => 'Bypass str_ireplace WAF with nested keywords',                              'difficulty' => 'insane'],
        ['num' => 20, 'title' => 'WAF Bypass: GBK Wide Byte',         'desc' => 'Bypass addslashes with GBK multi-byte character trick',                     'difficulty' => 'insane'],
    ],
    'pgsql' => [
        ['num' => 1,  'title' => 'UNION: Basic String Injection',        'desc' => 'PostgreSQL UNION with || concat and type strictness',                    'difficulty' => 'easy'],
        ['num' => 2,  'title' => 'UNION: Dollar-Quoting Bypass',         'desc' => 'Bypass quote escaping with $$string$$ dollar-quoting',                   'difficulty' => 'medium'],
        ['num' => 3,  'title' => 'Error: CAST Type Mismatch',            'desc' => 'CAST((SELECT...) AS INT) leaks data in type error',                      'difficulty' => 'medium'],
        ['num' => 4,  'title' => 'Blind Boolean: CASE + SUBSTRING',      'desc' => 'Boolean oracle via conditional responses',                               'difficulty' => 'medium'],
        ['num' => 5,  'title' => 'Blind Time: pg_sleep()',               'desc' => 'CASE WHEN cond THEN pg_sleep(5) timing oracle',                          'difficulty' => 'hard'],
        ['num' => 6,  'title' => 'Stacked Queries: Multi-Statement',     'desc' => 'Full stacked query support via pg_query()',                              'difficulty' => 'medium'],
        ['num' => 7,  'title' => 'File Read: pg_read_file / COPY',       'desc' => 'Read server files via pg_read_file() or COPY FROM',                      'difficulty' => 'hard'],
        ['num' => 8,  'title' => 'File Write: COPY TO / lo_export',      'desc' => 'Write files via COPY TO or lo_export()',                                 'difficulty' => 'hard'],
        ['num' => 9,  'title' => 'RCE: COPY TO PROGRAM',                'desc' => 'Execute OS commands via COPY TO PROGRAM',                                'difficulty' => 'insane'],
        ['num' => 10, 'title' => 'RCE: Custom C Function (UDF)',         'desc' => 'Upload .so via lo_import, CREATE FUNCTION for RCE',                      'difficulty' => 'insane'],
        ['num' => 11, 'title' => 'OOB: dblink + DNS Exfiltration',       'desc' => 'DNS exfiltration via dblink_connect',                                    'difficulty' => 'insane'],
        ['num' => 12, 'title' => 'Large Objects Abuse',                    'desc' => 'lo_import, lo_get, lo_export chain for file R/W',                        'difficulty' => 'hard'],
        ['num' => 13, 'title' => 'XML Injection: xmlparse / xpath',      'desc' => 'Abuse PostgreSQL XML functions for extraction',                          'difficulty' => 'hard'],
        ['num' => 14, 'title' => 'Privilege Escalation: ALTER ROLE',     'desc' => 'ALTER ROLE current_user SUPERUSER via stacked queries',                  'difficulty' => 'insane'],
        ['num' => 15, 'title' => 'INSERT / UPDATE Injection',             'desc' => 'Injection in RETURNING clause for data extraction',                       'difficulty' => 'medium'],
    ],
    'sqlite' => [
        ['num' => 1,  'title' => 'UNION: sqlite_master Enumeration',     'desc' => 'Query sqlite_master for table names and schemas',                        'difficulty' => 'easy'],
        ['num' => 2,  'title' => 'UNION: pragma_table_info()',           'desc' => 'Use pragma_table_info() to enumerate columns',                           'difficulty' => 'easy'],
        ['num' => 3,  'title' => 'Error: load_extension() Trigger',      'desc' => 'Conditional error oracle via load_extension()',                           'difficulty' => 'medium'],
        ['num' => 4,  'title' => 'Blind Boolean: hex(substr())',         'desc' => 'Character extraction via hex(substr()) comparison',                      'difficulty' => 'medium'],
        ['num' => 5,  'title' => 'Blind Time: RANDOMBLOB Heavy Query',   'desc' => 'No SLEEP(). CPU-bound timing with RANDOMBLOB()',                         'difficulty' => 'hard'],
        ['num' => 6,  'title' => 'typeof() / zeroblob() Tricks',          'desc' => 'SQLite-specific functions for data inference',                            'difficulty' => 'medium'],
        ['num' => 7,  'title' => 'ATTACH DATABASE: File Write',          'desc' => 'ATTACH DATABASE to write PHP webshell to disk',                          'difficulty' => 'hard'],
        ['num' => 8,  'title' => 'RCE: load_extension()',               'desc' => 'Load malicious .so/.dll for code execution',                             'difficulty' => 'insane'],
        ['num' => 9,  'title' => 'JSON Functions Injection',              'desc' => 'json_extract(), json_each() for data extraction',                         'difficulty' => 'medium'],
        ['num' => 10, 'title' => 'WAF Bypass: No Standard Functions',    'desc' => 'Use replace(), instr(), unicode(), char()',                               'difficulty' => 'insane'],
    ],
    'mssql' => [
        ['num' => 1,  'title' => 'UNION: Basic String Injection',        'desc' => 'MSSQL UNION with string concat (+)',                                     'difficulty' => 'easy'],
        ['num' => 2,  'title' => 'Error: CONVERT / CAST',               'desc' => 'CONVERT(INT, (SELECT...)) leaks data in error',                          'difficulty' => 'medium'],
        ['num' => 3,  'title' => 'Error: IN Operator Subquery',          'desc' => '1 IN (SELECT...) triggers conversion error',                             'difficulty' => 'medium'],
        ['num' => 4,  'title' => 'Blind Boolean: SUBSTRING + ASCII',     'desc' => 'Binary search on ASCII values of characters',                            'difficulty' => 'medium'],
        ['num' => 5,  'title' => 'Blind Time: WAITFOR DELAY',           'desc' => "IF condition WAITFOR DELAY '0:0:5'",                                     'difficulty' => 'hard'],
        ['num' => 6,  'title' => 'Stacked Queries: Full Control',        'desc' => 'Execute any statement: CREATE, INSERT, EXEC',                            'difficulty' => 'medium'],
        ['num' => 7,  'title' => 'xp_cmdshell: OS Commands',             'desc' => "Enable xp_cmdshell, EXEC xp_cmdshell 'whoami'",                         'difficulty' => 'hard'],
        ['num' => 8,  'title' => 'sp_OACreate: COM Object RCE',         'desc' => 'OLE Automation for cmd exec without xp_cmdshell',                        'difficulty' => 'insane'],
        ['num' => 9,  'title' => 'Python sp_execute_external_script',     'desc' => 'Execute Python code inside MSSQL for RCE',                               'difficulty' => 'insane'],
        ['num' => 10, 'title' => 'File Read: OPENROWSET(BULK)',          'desc' => 'Read arbitrary files via OPENROWSET',                                    'difficulty' => 'hard'],
        ['num' => 11, 'title' => 'OOB: xp_dirtree DNS Exfil',           'desc' => 'xp_dirtree UNC triggers DNS/SMB for exfil',                              'difficulty' => 'hard'],
        ['num' => 12, 'title' => 'OOB: fn_xe_file + UNC Path',          'desc' => 'Stealthy exfil via fn_xe_file UNC path',                                 'difficulty' => 'insane'],
        ['num' => 13, 'title' => 'Linked Servers Pivoting',               'desc' => 'OPENQUERY to pivot to other SQL servers',                                'difficulty' => 'insane'],
        ['num' => 14, 'title' => 'Impersonation: EXECUTE AS',           'desc' => "EXECUTE AS LOGIN = 'sa' for privilege escalation",                       'difficulty' => 'hard'],
        ['num' => 15, 'title' => 'Header Injection: Referer',           'desc' => 'Inject via Referer header logged to DB',                                 'difficulty' => 'hard'],
        ['num' => 16, 'title' => 'INSERT: OUTPUT Clause',               'desc' => 'MSSQL OUTPUT clause leaks inserted rows',                                'difficulty' => 'medium'],
        ['num' => 17, 'title' => 'WAF Bypass: Unicode Normalization',    'desc' => 'IIS Unicode normalization bypass',                                       'difficulty' => 'insane'],
        ['num' => 18, 'title' => 'NTLM Hash Capture via SMB',             'desc' => 'Force auth to attacker SMB, capture NTLMv2',                             'difficulty' => 'insane'],
    ],
    'oracle' => [
        ['num' => 1,  'title' => 'UNION: FROM DUAL Required',           'desc' => 'Oracle requires FROM DUAL on every SELECT',                              'difficulty' => 'medium'],
        ['num' => 2,  'title' => 'UNION: all_tables Enumeration',        'desc' => 'Enumerate via all_tables, all_tab_columns',                              'difficulty' => 'medium'],
        ['num' => 3,  'title' => 'Error: XMLType()',                     'desc' => 'XML parse error leaks data in message',                                  'difficulty' => 'hard'],
        ['num' => 4,  'title' => 'Error: UTL_INADDR',                   'desc' => 'DNS resolve error contains query result',                                'difficulty' => 'hard'],
        ['num' => 5,  'title' => 'Error: CTXSYS.DRITHSX.SN',            'desc' => 'Oracle Text function throws error with value',                           'difficulty' => 'hard'],
        ['num' => 6,  'title' => 'Blind Boolean: CASE + 1/0',           'desc' => 'Division-by-zero as boolean oracle',                                     'difficulty' => 'medium'],
        ['num' => 7,  'title' => 'Blind Time: DBMS_PIPE',               'desc' => 'DBMS_PIPE.RECEIVE_MESSAGE pipe-based delay',                             'difficulty' => 'hard'],
        ['num' => 8,  'title' => 'Blind Time: Heavy Query',             'desc' => 'Cartesian join or UTL_HTTP timeout delay',                               'difficulty' => 'hard'],
        ['num' => 9,  'title' => 'OOB: UTL_HTTP.REQUEST',               'desc' => 'HTTP callback with extracted data',                                      'difficulty' => 'insane'],
        ['num' => 10, 'title' => 'OOB: HTTPURITYPE / XXE',              'desc' => 'HTTPURITYPE().getclob() for OOB exfil',                                  'difficulty' => 'insane'],
        ['num' => 11, 'title' => 'OOB: DBMS_LDAP.INIT',                'desc' => 'LDAP-based data exfiltration',                                            'difficulty' => 'insane'],
        ['num' => 12, 'title' => 'RCE: Java Stored Procedure',          'desc' => 'Java Runtime.exec() via stored procedure',                               'difficulty' => 'insane'],
        ['num' => 13, 'title' => 'RCE: DBMS_SCHEDULER Job',             'desc' => 'DBMS_SCHEDULER runs OS commands as oracle',                              'difficulty' => 'insane'],
        ['num' => 14, 'title' => 'Privilege Escalation: DBA Grant',      'desc' => 'Exploit AUTHID DEFINER to GRANT DBA',                                   'difficulty' => 'insane'],
    ],
    'mariadb' => [
        ['num' => 1,  'title' => 'UNION: MySQL-Compatible Basics',       'desc' => 'Verify UNION works identically to MySQL in MariaDB',                    'difficulty' => 'easy'],
        ['num' => 2,  'title' => 'CONNECT Engine: Remote Tables',        'desc' => 'Inject to read remote data via CONNECT engine',                         'difficulty' => 'hard'],
        ['num' => 3,  'title' => 'Spider Engine: Federated Injection',   'desc' => 'Pivot to other instances via Spider engine',                             'difficulty' => 'insane'],
        ['num' => 4,  'title' => 'Oracle Mode: PL/SQL Syntax',          'desc' => 'Exploit Oracle-style constructs in MariaDB',                             'difficulty' => 'hard'],
        ['num' => 5,  'title' => 'Sequence Object Injection',             'desc' => 'Extract data via NEXT VALUE FOR sequence',                               'difficulty' => 'medium'],
        ['num' => 6,  'title' => 'sys_exec UDF: OS Commands',            'desc' => 'Full RCE via lib_mysqludf_sys',                                          'difficulty' => 'insane'],
        ['num' => 7,  'title' => 'Error: SIGNAL / GET DIAGNOSTICS',     'desc' => 'Custom error messages for extraction',                                   'difficulty' => 'hard'],
        ['num' => 8,  'title' => 'Window Functions for Extraction',        'desc' => 'ROW_NUMBER(), RANK() OVER() in subqueries',                             'difficulty' => 'medium'],
    ],
    'mongodb' => [
        ['num' => 1, 'title' => 'Auth Bypass: $ne Operator',        'desc' => 'NoSQL authentication bypass via not-equal operator injection',         'difficulty' => 'easy'],
        ['num' => 2, 'title' => 'Auth Bypass: $gt Operator',        'desc' => 'Bypass login with greater-than comparison operator',                   'difficulty' => 'easy'],
        ['num' => 3, 'title' => 'Blind Extraction: $regex',         'desc' => 'Extract data character-by-character with regex oracle',                'difficulty' => 'medium'],
        ['num' => 4, 'title' => 'Server-Side JS: $where',           'desc' => 'Inject JavaScript into $where clause for blind extraction',            'difficulty' => 'hard'],
        ['num' => 5, 'title' => 'Aggregation Pipeline Injection',     'desc' => 'Inject $lookup stage to access hidden collections',                    'difficulty' => 'hard'],
        ['num' => 6, 'title' => '$lookup: Cross-Collection Access',  'desc' => 'Manipulate join parameters to read unauthorized data',                 'difficulty' => 'hard'],
        ['num' => 7, 'title' => 'JSON Parameter Pollution',           'desc' => 'Inject operators via JSON body to bypass authentication',              'difficulty' => 'medium'],
        ['num' => 8, 'title' => 'BSON: $type / $exists',            'desc' => 'Enumerate schema and extract data via type and existence checks',      'difficulty' => 'medium'],
    ],
    'redis' => [
        ['num' => 1, 'title' => 'CRLF Protocol Injection',           'desc' => 'Break out of SET value with CRLF to execute arbitrary commands',       'difficulty' => 'medium'],
        ['num' => 2, 'title' => 'Lua EVAL Injection',                'desc' => 'Inject Lua code into EVAL for arbitrary command execution',            'difficulty' => 'hard'],
        ['num' => 3, 'title' => 'CONFIG SET: File Write',           'desc' => 'Write webshell via CONFIG SET dir/dbfilename trick',                   'difficulty' => 'hard'],
        ['num' => 4, 'title' => 'SLAVEOF Data Exfiltration',         'desc' => 'Rogue master replication to exfiltrate all keys',                      'difficulty' => 'insane'],
        ['num' => 5, 'title' => 'MODULE LOAD: RCE',                'desc' => 'Load malicious .so module for remote code execution',                  'difficulty' => 'insane'],
    ],
    'hql' => [
        ['num' => 1, 'title' => 'Entity Name Injection',             'desc' => 'Inject into FROM clause. HQL uses Java class names',                 'difficulty' => 'medium'],
        ['num' => 2, 'title' => '.class Metadata Access',            'desc' => 'Leak class names via Hibernate dot notation',                          'difficulty' => 'medium'],
        ['num' => 3, 'title' => 'Native Query Escape',               'desc' => 'Break out of HQL into native SQL execution',                          'difficulty' => 'hard'],
        ['num' => 4, 'title' => 'Criteria API Bypass',               'desc' => 'Inject via dynamically built Criteria API queries',                    'difficulty' => 'hard'],
        ['num' => 5, 'title' => 'Cache Poisoning',                   'desc' => 'Poison Hibernate query cache to alter results',                        'difficulty' => 'insane'],
    ],
    'graphql' => [
        ['num' => 1, 'title' => 'Introspection: Schema Discovery', 'desc' => 'Dump entire API schema via __schema introspection query',              'difficulty' => 'easy'],
        ['num' => 2, 'title' => 'Field Suggestion Exploitation',     'desc' => 'Discover hidden fields via GraphQL error suggestions',                 'difficulty' => 'medium'],
        ['num' => 3, 'title' => 'Alias-Based Auth Bypass',           'desc' => 'Access unauthorized data via aliased field queries',                   'difficulty' => 'medium'],
        ['num' => 4, 'title' => 'Batching Attack',                   'desc' => 'Brute-force credentials by batching mutations in one request',         'difficulty' => 'hard'],
        ['num' => 5, 'title' => 'Nested Query DoS + Data Extraction','desc' => 'Deeply nested queries for resource abuse and data leak',               'difficulty' => 'insane'],
    ],
];

$dbNames = [
    'mysql'   => 'MySQL',
    'pgsql'   => 'PostgreSQL',
    'sqlite'  => 'SQLite',
    'mssql'   => 'MS SQL Server',
    'oracle'  => 'Oracle DB',
    'mariadb' => 'MariaDB',
    'mongodb' => 'MongoDB',
    'redis'   => 'Redis',
    'hql'     => 'HQL (Hibernate)',
    'graphql' => 'GraphQL',
];

$dbLabList = $labs[$db] ?? [];
?>

<div class="container">

    <div class="section-title anim">
        <span class="accent">#</span> <?= htmlspecialchars($dbNames[$db]) ?> // lab selection
    </div>

    <ul class="lab-list anim anim-d2">
        <?php foreach ($dbLabList as $lab): ?>
            <?php
                $isSoon = !empty($lab['soon']);
                $labKey = "{$db}_lab{$lab['num']}_solved";
                $isSolved = !empty($_SESSION[$labKey]);
                $href = $isSoon ? '#' : url_lab($db, $lab['num']);
            ?>
            <a href="<?= $href ?>"
               class="lab-item"
               <?= $isSoon ? 'style="opacity:0.4;pointer-events:none;"' : '' ?>>

                <span class="lab-num"><?= str_pad($lab['num'], 2, '0', STR_PAD_LEFT) ?></span>

                <div class="lab-info">
                    <div class="lab-title">
                        <?= htmlspecialchars($lab['title']) ?>
                        <?php if ($isSoon): ?>
                            <span class="tag">locked</span>
                        <?php endif; ?>
                    </div>
                    <div class="lab-desc"><?= htmlspecialchars($lab['desc']) ?></div>
                </div>

                <span class="lab-difficulty diff-<?= $lab['difficulty'] ?>">
                    <?= $lab['difficulty'] ?>
                </span>

                <span class="lab-status <?= $isSolved ? 'solved' : '' ?>"></span>
            </a>
        <?php endforeach; ?>
    </ul>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
