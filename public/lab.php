<?php
ob_start();
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';

$lab  = $_GET['lab'] ?? '';
$mode = $_GET['mode'] ?? 'black';

if (!preg_match('/^(mysql|pgsql|sqlite|mssql|oracle|mariadb|mongodb|redis|hql|graphql)\/lab[0-9]+$/', $lab)) {
    die("<div class='container'><div class='card'><h3>Invalid lab</h3></div></div>");
}

$labKey    = str_replace('/', '_', $lab);
$solvedKey = $labKey . '_solved';
$engine    = explode('/', $lab)[0];

$labFile      = __DIR__ . "/../labs/$lab.php";
$sourceFile   = __DIR__ . "/../labs/{$lab}_source.php";
$solutionFile = __DIR__ . "/../labs/{$lab}_solution.php";

if (!is_readable($labFile)) {
    die("<div class='container'><div class='card'><h3>Lab file not found</h3></div></div>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_lab']) && csrf_verify()) {
    unset($_SESSION[$solvedKey]);

    // Also reset the actual database for this lab
    require_once __DIR__ . '/../includes/reset_functions.php';
    preg_match('/lab(\d+)$/', $lab, $lm);
    $labNum = (int)$lm[1];
    resetLabDatabase($engine, $labNum);

    $resetRef = $_GET['ref'] ?? '';
    header("Location: " . url_lab_from_slug($lab, 'black', $resetRef));
    exit;
}
?>

<div class="container">

    <?php
    $ref = $_GET['ref'] ?? '';
    if ($ref && preg_match('/^[a-z0-9-]+$/', $ref)):
    ?>
        <a href="<?= url_phase($ref) ?>" class="back-link anim">&larr; back to learning path</a>
    <?php else: ?>
        <a href="<?= url_engine($engine) ?>" class="back-link anim">&larr; back to <?= htmlspecialchars($engine) ?> labs</a>
    <?php endif; ?>

    <!-- MODE BAR -->
    <div class="mode-bar anim anim-d1">
        <div class="mode-tabs">
            <a href="<?= url_lab_from_slug($lab, 'black', $ref) ?>"
               class="mode-tab <?= $mode === 'black' ? 'active' : '' ?>">
                black-box
            </a>
            <a href="<?= url_lab_from_slug($lab, 'white', $ref) ?>"
               class="mode-tab <?= $mode === 'white' ? 'active' : '' ?>">
                white-box
            </a>
        </div>

        <div class="mode-actions">
            <?php if ($mode === 'black'): ?>
                <label class="toggle-switch" id="queryToggle">
                    <input type="checkbox" onchange="toggleQueryVisibility()">
                    <span class="toggle-slider"></span>
                    <span class="toggle-label">show query</span>
                </label>
            <?php endif; ?>

            <button class="btn btn-ghost btn-sm" onclick="openSolutionModal()" aria-label="View solution">
                solution
            </button>

            <?php if (!empty($_SESSION[$solvedKey])): ?>
                <form method="POST" style="margin:0;">
                    <?= csrf_field() ?>
                    <button type="submit" name="reset_lab" class="btn btn-danger btn-sm">
                        reset lab
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- CONTENT -->
    <?php if ($mode === 'white'): ?>

        <div class="whitebox-grid anim anim-d2">
            <div class="card">
                <?php include $labFile; ?>
            </div>
            <div class="card">
                <h4>// source code</h4>
                <?php if (is_readable($sourceFile)): ?>
                    <pre class="source-code"><?= htmlspecialchars(file_get_contents($sourceFile)) ?></pre>
                <?php else: ?>
                    <div class="result-warning result-box">source file not available</div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($mode === 'solution'): ?>

        <div class="card anim anim-d2">
            <h3>// solution walkthrough</h3>
            <?php if (is_readable($solutionFile)): ?>
                <?php include $solutionFile; ?>
            <?php else: ?>
                <div class="result-warning result-box">solution not available yet</div>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <div class="anim anim-d2">
            <?php include $labFile; ?>
        </div>

    <?php endif; ?>

    <!-- SOLUTION MODAL -->
    <div id="solutionModal" class="modal-overlay hidden" role="dialog" aria-modal="true">
        <div class="modal-box">
            <h3>// view solution?</h3>
            <p>
                Viewing the solution reduces your learning.
                Try solving the lab yourself first.
            </p>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeSolutionModal()" aria-label="Close">cancel</button>
                <a href="<?= url_lab_from_slug($lab, 'solution', $ref) ?>" class="btn btn-primary">
                    show solution
                </a>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
