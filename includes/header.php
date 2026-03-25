<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_ROOT ?>/assets/css/main.css?v=<?= filemtime(__DIR__ . '/../public/assets/css/main.css') ?>">
</head>
<body>

<nav class="navbar">
    <div class="nav-inner">
        <a href="<?= url_home() ?>" class="nav-brand">
            <span class="brand-icon">>_</span>
            <span class="brand-text"><?= APP_NAME ?></span>
        </a>

        <div class="nav-links">
            <a href="<?= url_home() ?>" class="nav-link">
                Home
            </a>
            <a href="<?= url_page('learning-path') ?>" class="nav-link">
                Learning Path
            </a>
            <a href="<?= url_page('attack-types') ?>" class="nav-link">
                Attack Types
            </a>
            <a href="<?= url_page('cheatsheet') ?>" class="nav-link">
                Cheatsheet
            </a>
            <a href="<?= url_page('control-panel') ?>" class="nav-link">
                Control Panel
            </a>
            <button id="themeToggle" class="nav-link theme-btn" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme">
                <span id="themeIcon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="theme-icon-dark">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="theme-icon-light">
                        <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                </span>
            </button>
        </div>
    </div>
</nav>

<script>
function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('sqli-arena-theme', t);
    var el = document.getElementById('themeIcon');
    if (el) {
        var dark = el.querySelector('.theme-icon-dark');
        var light = el.querySelector('.theme-icon-light');
        if (dark) dark.style.display = t === 'dark' ? 'block' : 'none';
        if (light) light.style.display = t === 'dark' ? 'none' : 'block';
    }
}
function toggleTheme() {
    var cur = document.documentElement.getAttribute('data-theme');
    setTheme(cur === 'dark' ? 'light' : 'dark');
}
(function() {
    setTheme(localStorage.getItem('sqli-arena-theme') || 'dark');
})();
</script>
