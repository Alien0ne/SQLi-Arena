<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';
require __DIR__ . '/curriculum_data.php';

$curriculum = $curriculumData;

/* Build a map of solved labs */
$solvedMap = [];
foreach ($_SESSION as $k => $v) {
    if (str_ends_with($k, '_solved') && $v) {
        $solvedMap[$k] = true;
    }
}
$totalSolved = count($solvedMap);

function isLabSolved($engine, $num) {
    global $solvedMap;
    return isset($solvedMap[$engine . '_lab' . $num . '_solved']);
}

/* Define the learning path phases */
$phases = [
    [
        'id'    => 'first-injection',
        'num'   => 1,
        'title' => 'First Injection',
        'desc'  => 'Learn how SQL injection works. Inject into string and integer parameters, break out of quotes, and use UNION SELECT to extract data from the database.',
        'icon'  => '01',
        'difficulty' => 'Beginner',
        'categories' => ['UNION-Based'],
        'engines' => ['mysql', 'pgsql', 'sqlite', 'mssql', 'oracle', 'mariadb'],
    ],
    [
        'id'    => 'error-based',
        'num'   => 2,
        'title' => 'Error-Based Extraction',
        'desc'  => 'When UNION is blocked, force the database to leak data inside error messages. Master ExtractValue, CAST, XMLType, and other engine-specific error vectors.',
        'icon'  => '02',
        'difficulty' => 'Intermediate',
        'categories' => ['Error-Based'],
        'engines' => ['mysql', 'pgsql', 'sqlite', 'mssql', 'oracle', 'mariadb'],
    ],
    [
        'id'    => 'going-blind',
        'num'   => 3,
        'title' => 'Going Blind',
        'desc'  => 'No output, no errors. Extract data one bit at a time using boolean conditions and time delays. Learn SLEEP, WAITFOR, pg_sleep, DBMS_PIPE, and heavy query techniques.',
        'icon'  => '03',
        'difficulty' => 'Intermediate',
        'categories' => ['Blind Injection'],
        'engines' => ['mysql', 'pgsql', 'sqlite', 'mssql', 'oracle'],
    ],
    [
        'id'    => 'advanced-sql',
        'num'   => 4,
        'title' => 'Advanced SQL Techniques',
        'desc'  => 'Stacked queries, INSERT/UPDATE injection, ORDER BY injection, and engine-specific features like window functions, sequences, and large objects.',
        'icon'  => '04',
        'difficulty' => 'Advanced',
        'categories' => ['Advanced'],
        'engines' => ['mysql', 'pgsql', 'mssql', 'mariadb', 'oracle'],
    ],
    [
        'id'    => 'injection-vectors',
        'num'   => 5,
        'title' => 'Injection Vectors',
        'desc'  => 'SQL injection doesn\'t only happen in form fields. Exploit HTTP headers (User-Agent, Cookie, Referer), second-order injection, and INSERT/RETURNING clauses.',
        'icon'  => '05',
        'difficulty' => 'Advanced',
        'categories' => ['Injection Vectors'],
        'engines' => ['mysql', 'pgsql', 'mssql', 'mongodb'],
    ],
    [
        'id'    => 'waf-bypass',
        'num'   => 6,
        'title' => 'WAF Bypass',
        'desc'  => 'Real-world applications have filters. Bypass keyword blacklists, WAFs, and encoding defenses using inline comments, case mixing, wide byte encoding, and Unicode tricks.',
        'icon'  => '06',
        'difficulty' => 'Expert',
        'categories' => ['WAF Bypass'],
        'engines' => ['mysql', 'sqlite', 'mssql'],
    ],
    [
        'id'    => 'file-operations',
        'num'   => 7,
        'title' => 'File Operations',
        'desc'  => 'Read arbitrary files from the server and write webshells to disk. Techniques include LOAD_FILE, COPY, OPENROWSET, ATTACH DATABASE, and CONFIG SET.',
        'icon'  => '07',
        'difficulty' => 'Expert',
        'categories' => ['File Operations'],
        'engines' => ['pgsql', 'sqlite', 'mssql', 'redis'],
    ],
    [
        'id'    => 'remote-code-execution',
        'num'   => 8,
        'title' => 'Remote Code Execution',
        'desc'  => 'The ultimate goal. Achieve OS command execution through xp_cmdshell, COPY TO PROGRAM, UDF loading, Java stored procedures, Lua EVAL, and MODULE LOAD.',
        'icon'  => '08',
        'difficulty' => 'Expert',
        'categories' => ['Code Execution'],
        'engines' => ['pgsql', 'sqlite', 'mssql', 'oracle', 'mariadb', 'mongodb', 'redis'],
    ],
    [
        'id'    => 'out-of-band',
        'num'   => 9,
        'title' => 'Out-of-Band Exfiltration',
        'desc'  => 'When the application shows nothing at all. Exfiltrate data via DNS queries, HTTP callbacks, SMB requests, LDAP connections, and Redis replication.',
        'icon'  => '09',
        'difficulty' => 'Expert',
        'categories' => ['Out-of-Band'],
        'engines' => ['pgsql', 'mssql', 'oracle', 'redis'],
    ],
    [
        'id'    => 'privilege-escalation',
        'num'   => 10,
        'title' => 'Privilege Escalation',
        'desc'  => 'Escalate from a low-privilege database user to DBA or sysadmin. Exploit ALTER ROLE, EXECUTE AS, linked servers, GRANT, and AUTHID DEFINER.',
        'icon'  => '10',
        'difficulty' => 'Expert',
        'categories' => ['Privilege Escalation'],
        'engines' => ['pgsql', 'mssql', 'oracle'],
    ],
    [
        'id'    => 'nosql-injection',
        'num'   => 11,
        'title' => 'NoSQL Injection',
        'desc'  => 'SQL isn\'t the only target. Exploit MongoDB operator injection ($ne, $gt, $regex, $where), aggregation pipelines, and BSON type abuse.',
        'icon'  => '11',
        'difficulty' => 'Intermediate',
        'categories' => ['NoSQL Injection'],
        'engines' => ['mongodb'],
    ],
    [
        'id'    => 'redis-exploitation',
        'num'   => 12,
        'title' => 'Redis Exploitation',
        'desc'  => 'Attack the in-memory data store. CRLF protocol injection, Lua scripting abuse, CONFIG SET file writes, SLAVEOF exfiltration, and MODULE LOAD for RCE.',
        'icon'  => '12',
        'difficulty' => 'Advanced',
        'categories' => ['Command Injection', 'File Operations', 'Code Execution', 'Out-of-Band'],
        'engines' => ['redis'],
    ],
    [
        'id'    => 'hql-injection',
        'num'   => 13,
        'title' => 'HQL Injection',
        'desc'  => 'Hibernate Query Language operates on objects, not tables. Exploit entity injection, .class metadata, native query escape, Criteria API bypass, and cache poisoning.',
        'icon'  => '13',
        'difficulty' => 'Advanced',
        'categories' => ['HQL Injection', 'Advanced'],
        'engines' => ['hql'],
    ],
    [
        'id'    => 'graphql-attacks',
        'num'   => 14,
        'title' => 'GraphQL Attacks',
        'desc'  => 'Abuse the API query language. Introspection for schema discovery, field suggestion enumeration, alias-based auth bypass, batching attacks, and nested query exploitation.',
        'icon'  => '14',
        'difficulty' => 'Intermediate',
        'categories' => ['Enumeration', 'Auth Bypass', 'Advanced'],
        'engines' => ['graphql'],
    ],
];

/* Collect labs for a phase */
function getPhaseLabsFromData($phase, $curriculum) {
    $labs = [];
    foreach ($phase['engines'] as $engine) {
        if (!isset($curriculum[$engine])) continue;
        foreach ($curriculum[$engine]['labs'] as $lab) {
            if (in_array($lab['category'], $phase['categories'])) {
                $labs[] = array_merge($lab, [
                    'engine' => $engine,
                    'engine_name' => $curriculum[$engine]['name'],
                    'engine_icon' => $curriculum[$engine]['icon'],
                    'engine_color' => $curriculum[$engine]['color'],
                ]);
            }
        }
    }
    return $labs;
}

$totalLabs = 0;
$totalLive = 0;
foreach ($curriculum as $db) {
    foreach ($db['labs'] as $lab) {
        $totalLabs++;
        if ($lab['status'] === 'live') $totalLive++;
    }
}

/* Check if a specific phase is requested */
$activePhase = $_GET['phase'] ?? '';
$currentPhase = null;
if ($activePhase) {
    foreach ($phases as $p) {
        if ($p['id'] === $activePhase) { $currentPhase = $p; break; }
    }
    if (!$currentPhase) {
        die("<div class='container'><div class='card'><h3>Phase not found</h3></div></div>");
    }
}
?>

<div class="container">

<?php if ($currentPhase): ?>
    <?php
    /* ====== PHASE DETAIL VIEW ====== */
    $phaseLabs = getPhaseLabsFromData($currentPhase, $curriculum);
    $phaseTotal = count($phaseLabs);
    $phaseSolved = 0;
    foreach ($phaseLabs as $pl) {
        if (isLabSolved($pl['engine'], $pl['num'])) $phaseSolved++;
    }
    $phaseProgress = $phaseTotal > 0 ? round(($phaseSolved / $phaseTotal) * 100) : 0;

    $diffClass = match($currentPhase['difficulty']) {
        'Beginner' => 'lp-diff-beginner',
        'Intermediate' => 'lp-diff-intermediate',
        'Advanced' => 'lp-diff-advanced',
        'Expert' => 'lp-diff-expert',
        default => '',
    };
    ?>

    <a href="<?= url_page('learning-path') ?>" class="back-link anim">&larr; back to learning path</a>

    <section class="anim" style="padding:24px 0 0;">
        <div class="lp-phase-header" style="margin-bottom:8px;">
            <div>
                <div class="lp-phase-eyebrow">
                    <span class="lp-phase-num-badge">Phase <?= $currentPhase['icon'] ?></span>
                    <span class="<?= $diffClass ?>"><?= $currentPhase['difficulty'] ?></span>
                    <span class="lp-phase-count"><?= $phaseTotal ?> labs</span>
                </div>
                <h1 class="lp-phase-title" style="font-size:32px;margin-bottom:8px;"><?= htmlspecialchars($currentPhase['title']) ?></h1>
                <p class="lp-phase-desc" style="max-width:700px;"><?= htmlspecialchars($currentPhase['desc']) ?></p>
            </div>
            <div class="lp-progress-ring" title="<?= $phaseSolved ?>/<?= $phaseTotal ?> completed" style="width:64px;height:64px;">
                <svg viewBox="0 0 36 36" style="width:64px;height:64px;">
                    <path class="lp-ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path class="lp-ring-fg" stroke-dasharray="<?= $phaseProgress ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                </svg>
                <span class="lp-ring-text" style="font-size:12px;"><?= $phaseSolved ?>/<?= $phaseTotal ?></span>
            </div>
        </div>

        <div class="lp-engines" style="margin-bottom:20px;">
            <?php
            $seenEngines = [];
            foreach ($phaseLabs as $pl):
                if (isset($seenEngines[$pl['engine']])) continue;
                $seenEngines[$pl['engine']] = true;
            ?>
                <span class="lp-engine-tag" style="color:var(--<?= $pl['engine_color'] ?>);background:var(--<?= $pl['engine_color'] ?>-g);">
                    <?= $pl['engine_icon'] ?>
                </span>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="anim anim-d1">
        <div class="lp-labs" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px;">
            <?php foreach ($phaseLabs as $pl):
                $solved = isLabSolved($pl['engine'], $pl['num']);
                $href = url_lab($pl['engine'], $pl['num'], 'black', $currentPhase['id']);
            ?>
                <a href="<?= $href ?>" class="lp-lab <?= $solved ? 'lp-lab--solved' : '' ?>" style="--lab-color: var(--<?= $pl['engine_color'] ?>);">
                    <div class="lp-lab-top">
                        <span class="lp-lab-engine" style="color:var(--<?= $pl['engine_color'] ?>);background:var(--<?= $pl['engine_color'] ?>-g);"><?= $pl['engine_icon'] ?></span>
                        <span class="lp-lab-num">#<?= str_pad($pl['num'], 2, '0', STR_PAD_LEFT) ?></span>
                        <?php if ($solved): ?>
                            <span class="lp-lab-check">&#10003;</span>
                        <?php endif; ?>
                    </div>
                    <div class="lp-lab-title"><?= htmlspecialchars($pl['title']) ?></div>
                    <div class="lp-lab-diff">
                        <span class="cur-d-<?= $pl['difficulty'] ?>" style="font-size:10px;padding:1px 6px;border-radius:4px;"><?= ucfirst($pl['difficulty']) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

<?php else: ?>
    <?php /* ====== PHASE CARDS VIEW (main page) ====== */ ?>

    <section class="hero anim" style="padding:36px 0 20px;">
        <div class="hero-eyebrow">
            <span class="dot"></span>
            structured training path
        </div>
        <h1><span class="hl">Learning Path</span></h1>
        <p class="hero-sub">
            Progress from your first injection to full RCE.
            14 phases, <?= $totalLive ?> live labs across 10 database engines.
        </p>
        <div class="stats-row" style="margin-top:16px;">
            <div class="stat-block">
                <div class="stat-val">14</div>
                <div class="stat-lbl">Phases</div>
            </div>
            <div class="stat-block">
                <div class="stat-val"><?= $totalLive ?></div>
                <div class="stat-lbl">Labs</div>
            </div>
            <div class="stat-block">
                <div class="stat-val"><?= $totalSolved ?>/<?= $totalLive ?></div>
                <div class="stat-lbl">Completed</div>
            </div>
        </div>
    </section>

    <!-- Phase Cards Grid -->
    <div class="lp-timeline anim anim-d1">
        <?php foreach ($phases as $phase):
            $phaseLabs = getPhaseLabsFromData($phase, $curriculum);
            $phaseTotal = count($phaseLabs);
            $phaseSolved = 0;
            foreach ($phaseLabs as $pl) {
                if (isLabSolved($pl['engine'], $pl['num'])) $phaseSolved++;
            }
            $phaseComplete = ($phaseTotal > 0 && $phaseSolved === $phaseTotal);
            $phaseProgress = $phaseTotal > 0 ? round(($phaseSolved / $phaseTotal) * 100) : 0;

            $diffClass = match($phase['difficulty']) {
                'Beginner' => 'lp-diff-beginner',
                'Intermediate' => 'lp-diff-intermediate',
                'Advanced' => 'lp-diff-advanced',
                'Expert' => 'lp-diff-expert',
                default => '',
            };
        ?>

        <div class="lp-phase <?= $phaseComplete ? 'lp-phase--complete' : '' ?>">
            <div class="lp-connector">
                <div class="lp-node <?= $phaseComplete ? 'lp-node--complete' : '' ?>">
                    <span><?= $phase['icon'] ?></span>
                </div>
                <div class="lp-line"></div>
            </div>

            <div class="lp-content">
                <a href="<?= url_phase($phase['id']) ?>" class="lp-phase-card">
                    <div class="lp-phase-header">
                        <div>
                            <div class="lp-phase-eyebrow">
                                <span class="<?= $diffClass ?>"><?= $phase['difficulty'] ?></span>
                                <span class="lp-phase-count"><?= $phaseTotal ?> labs</span>
                            </div>
                            <h2 class="lp-phase-title"><?= htmlspecialchars($phase['title']) ?></h2>
                            <p class="lp-phase-desc"><?= htmlspecialchars($phase['desc']) ?></p>
                        </div>
                        <div class="lp-progress-ring" title="<?= $phaseSolved ?>/<?= $phaseTotal ?> completed">
                            <svg viewBox="0 0 36 36">
                                <path class="lp-ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path class="lp-ring-fg" stroke-dasharray="<?= $phaseProgress ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            </svg>
                            <span class="lp-ring-text"><?= $phaseSolved ?>/<?= $phaseTotal ?></span>
                        </div>
                    </div>

                    <div class="lp-engines">
                        <?php
                        $seenEngines = [];
                        foreach ($phaseLabs as $pl):
                            if (isset($seenEngines[$pl['engine']])) continue;
                            $seenEngines[$pl['engine']] = true;
                        ?>
                            <span class="lp-engine-tag" style="color:var(--<?= $pl['engine_color'] ?>);background:var(--<?= $pl['engine_color'] ?>-g);">
                                <?= $pl['engine_icon'] ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </a>
            </div>
        </div>

        <?php endforeach; ?>

        <!-- End node -->
        <div class="lp-phase">
            <div class="lp-connector">
                <div class="lp-node lp-node--end">
                    <span>&#9733;</span>
                </div>
            </div>
            <div class="lp-content" style="padding-top:12px;">
                <h2 class="lp-phase-title" style="color:var(--neon);">Path Complete</h2>
                <p class="lp-phase-desc">You've mastered SQL injection across all 10 database engines.</p>
            </div>
        </div>
    </div>

<?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
