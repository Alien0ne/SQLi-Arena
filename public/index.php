<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

require_once __DIR__ . '/../includes/header.php';

$solved = 0;
foreach ($_SESSION as $k => $v) {
    if (str_ends_with($k, '_solved') && $v) $solved++;
}
?>

<div class="container">

    <!-- HERO -->
    <section class="hero anim">
        <div class="hero-eyebrow">
            <span class="dot"></span>
            system online | training environment active
        </div>

        <h1>SQL Injection<br><span class="hl">Training Arena</span></h1>

        <p class="hero-sub">
            Exploit real vulnerabilities across MySQL, PostgreSQL, SQLite, MSSQL, Oracle,
            MongoDB, Redis, MariaDB, HQL &amp; GraphQL.
            100+ labs from UNION attacks to NoSQL injection, WAF bypasses, and second-order injection.
        </p>

        <div class="stats-row">
            <div class="stat-block">
                <div class="stat-val">10</div>
                <div class="stat-lbl">Target DBs</div>
            </div>
            <div class="stat-block">
                <div class="stat-val"><?= LAB_TOTAL ?></div>
                <div class="stat-lbl">Total Labs</div>
            </div>
            <div class="stat-block">
                <div class="stat-val"><?= $solved ?>/<?= LAB_TOTAL ?></div>
                <div class="stat-lbl">Pwned</div>
            </div>
        </div>
    </section>

    <!-- TARGETS -->
    <section id="targets">
        <div class="section-title">
            <span class="accent">#</span> select target
        </div>

        <div class="target-grid">

            <!-- MySQL -->
            <a href="<?= url_engine('mysql') ?>" class="target-card mysql anim-card">
                <div class="tc-header">
                    <div class="tc-id">
                        <div class="tc-icon">MY</div>
                        <div>
                            <div class="tc-name">MySQL</div>
                            <div class="tc-ver">v8.0+</div>
                        </div>
                    </div>
                    <span class="tc-status online">online</span>
                </div>
                <div class="tc-body">
                    <div class="tc-desc">
                        The web's most deployed database. UNION injection, error extraction
                        via extractvalue/updatexml, blind boolean &amp; time-based, stacked queries.
                    </div>
                    <div class="tc-footer">
                        <div class="tc-labs"><strong><?= LAB_COUNTS['mysql'] ?></strong> labs live</div>
                        <div class="tc-diff">
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </a>

            <!-- PostgreSQL -->
            <a href="<?= url_engine('pgsql') ?>" class="target-card pgsql anim-card">
                <div class="tc-header">
                    <div class="tc-id">
                        <div class="tc-icon">PG</div>
                        <div>
                            <div class="tc-name">PostgreSQL</div>
                            <div class="tc-ver">v18+</div>
                        </div>
                    </div>
                    <span class="tc-status online">online</span>
                </div>
                <div class="tc-body">
                    <div class="tc-desc">
                        Dollar-quoting, COPY commands, type casting errors,
                        full stacked query support. Rich error messages for extraction.
                    </div>
                    <div class="tc-footer">
                        <div class="tc-labs"><strong><?= LAB_COUNTS['pgsql'] ?></strong> labs live</div>
                        <div class="tc-diff">
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                        </div>
                    </div>
                </div>
            </a>

            <!-- SQLite -->
            <a href="<?= url_engine('sqlite') ?>" class="target-card sqlite anim-card">
                <div class="tc-header">
                    <div class="tc-id">
                        <div class="tc-icon">SL</div>
                        <div>
                            <div class="tc-name">SQLite</div>
                            <div class="tc-ver">v3.x</div>
                        </div>
                    </div>
                    <span class="tc-status online">online</span>
                </div>
                <div class="tc-body">
                    <div class="tc-desc">
                        Embedded file-based DB. Exploit sqlite_master, typeof(),
                        and unique UNION behaviors. No user system, no stacked queries.
                    </div>
                    <div class="tc-footer">
                        <div class="tc-labs"><strong><?= LAB_COUNTS['sqlite'] ?></strong> labs live</div>
                        <div class="tc-diff">
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </a>

            <!-- MSSQL -->
            <a href="<?= url_engine('mssql') ?>" class="target-card mssql anim-card">
                <div class="tc-header">
                    <div class="tc-id">
                        <div class="tc-icon">MS</div>
                        <div>
                            <div class="tc-name">MS SQL Server</div>
                            <div class="tc-ver">2022</div>
                        </div>
                    </div>
                    <span class="tc-status online">online</span>
                </div>
                <div class="tc-body">
                    <div class="tc-desc">
                        Enterprise-grade with xp_cmdshell, OPENROWSET, error-based
                        via convert/cast, powerful stacked queries. Critical for pentests.
                    </div>
                    <div class="tc-footer">
                        <div class="tc-labs"><strong><?= LAB_COUNTS['mssql'] ?></strong> labs live</div>
                        <div class="tc-diff">
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Oracle -->
            <a href="<?= url_engine('oracle') ?>" class="target-card oracle anim-card">
                <div class="tc-header">
                    <div class="tc-id">
                        <div class="tc-icon">OR</div>
                        <div>
                            <div class="tc-name">Oracle DB</div>
                            <div class="tc-ver">21c+</div>
                        </div>
                    </div>
                    <span class="tc-status online">online</span>
                </div>
                <div class="tc-body">
                    <div class="tc-desc">
                        Requires FROM dual, UTL_HTTP for OOB, XMLType for error extraction,
                        DBMS_PIPE for time-based blind. A different beast entirely.
                    </div>
                    <div class="tc-footer">
                        <div class="tc-labs"><strong><?= LAB_COUNTS['oracle'] ?></strong> labs live</div>
                        <div class="tc-diff">
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                        </div>
                    </div>
                </div>
            </a>

            <!-- MariaDB -->
            <a href="<?= url_engine('mariadb') ?>" class="target-card mariadb anim-card">
                <div class="tc-header">
                    <div class="tc-id">
                        <div class="tc-icon">MA</div>
                        <div>
                            <div class="tc-name">MariaDB</div>
                            <div class="tc-ver">v11+</div>
                        </div>
                    </div>
                    <span class="tc-status online">online</span>
                </div>
                <div class="tc-body">
                    <div class="tc-desc">
                        MySQL-compatible fork with unique CONNECT engine injection,
                        Oracle-mode PL/SQL syntax, sequence objects, and sys_exec UDF.
                    </div>
                    <div class="tc-footer">
                        <div class="tc-labs"><strong><?= LAB_COUNTS['mariadb'] ?></strong> labs live</div>
                        <div class="tc-diff">
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </a>

            <!-- MongoDB -->
            <a href="<?= url_engine('mongodb') ?>" class="target-card mongodb anim-card">
                <div class="tc-header">
                    <div class="tc-id">
                        <div class="tc-icon">MG</div>
                        <div>
                            <div class="tc-name">MongoDB</div>
                            <div class="tc-ver">v7+</div>
                        </div>
                    </div>
                    <span class="tc-status online">online</span>
                </div>
                <div class="tc-body">
                    <div class="tc-desc">
                        NoSQL operator injection ($gt, $ne, $regex, $where),
                        authentication bypass, server-side JS injection, aggregation pipeline abuse.
                    </div>
                    <div class="tc-footer">
                        <div class="tc-labs"><strong><?= LAB_COUNTS['mongodb'] ?></strong> labs live</div>
                        <div class="tc-diff">
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Redis -->
            <a href="<?= url_engine('redis') ?>" class="target-card redis anim-card">
                <div class="tc-header">
                    <div class="tc-id">
                        <div class="tc-icon">RD</div>
                        <div>
                            <div class="tc-name">Redis</div>
                            <div class="tc-ver">v7+</div>
                        </div>
                    </div>
                    <span class="tc-status online">online</span>
                </div>
                <div class="tc-body">
                    <div class="tc-desc">
                        In-memory data store. CRLF protocol injection, Lua EVAL injection,
                        CONFIG SET file write, SLAVEOF exfiltration, MODULE LOAD RCE.
                    </div>
                    <div class="tc-footer">
                        <div class="tc-labs"><strong><?= LAB_COUNTS['redis'] ?></strong> labs live</div>
                        <div class="tc-diff">
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </a>

            <!-- HQL -->
            <a href="<?= url_engine('hql') ?>" class="target-card hql anim-card">
                <div class="tc-header">
                    <div class="tc-id">
                        <div class="tc-icon">HQ</div>
                        <div>
                            <div class="tc-name">HQL (Hibernate)</div>
                            <div class="tc-ver">v6+</div>
                        </div>
                    </div>
                    <span class="tc-status online">online</span>
                </div>
                <div class="tc-body">
                    <div class="tc-desc">
                        Object-oriented query language. Entity/class injection,
                        .class metadata access, native query escape, criteria API bypass.
                    </div>
                    <div class="tc-footer">
                        <div class="tc-labs"><strong><?= LAB_COUNTS['hql'] ?></strong> labs live</div>
                        <div class="tc-diff">
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </a>

            <!-- GraphQL -->
            <a href="<?= url_engine('graphql') ?>" class="target-card graphql anim-card">
                <div class="tc-header">
                    <div class="tc-id">
                        <div class="tc-icon">GQ</div>
                        <div>
                            <div class="tc-name">GraphQL</div>
                            <div class="tc-ver">API</div>
                        </div>
                    </div>
                    <span class="tc-status online">online</span>
                </div>
                <div class="tc-body">
                    <div class="tc-desc">
                        API query language. Introspection abuse, batching attacks,
                        nested query DoS, alias-based auth bypass, fragment injection.
                    </div>
                    <div class="tc-footer">
                        <div class="tc-labs"><strong><?= LAB_COUNTS['graphql'] ?></strong> labs live</div>
                        <div class="tc-diff">
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span class="on"></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </a>

        </div>
    </section>

    <!-- Status Messages (from admin redirects) -->
    <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
        <div class="result-success result-box" style="margin-top:16px;">
            All databases reset to default state.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['cleanup'])): ?>
        <?php $dropped = (int)($_GET['dropped'] ?? 0); $errs = (int)($_GET['errors'] ?? 0); ?>
        <div class="<?= $errs > 0 ? 'result-warning' : 'result-success' ?> result-box" style="margin-top:16px;">
            Cleanup complete: <?= $dropped ?> database(s) dropped<?= $errs > 0 ? ", $errs error(s)" : '' ?>.
            Session progress cleared.
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
