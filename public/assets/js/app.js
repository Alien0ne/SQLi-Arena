/* =================================================================
   SQLi-Arena -- JS
================================================================= */

document.addEventListener('DOMContentLoaded', function() {
    document.documentElement.classList.add('js-ready');
    initScrollAnimations();
    initHints();
    initSQLHighlight();
});

/* Intersection Observer for scroll-in */
function initScrollAnimations() {
    var els = document.querySelectorAll('.animate-on-scroll');
    if (!els.length) return;

    var obs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

    els.forEach(function(el) { obs.observe(el); });
}

/* Hint toggles */
function initHints() {
    document.querySelectorAll('.hint-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var target = document.getElementById(btn.dataset.hint);
            if (target) target.classList.toggle('show');
        });
    });
}

/* Solution modal */
function openSolutionModal() {
    var m = document.getElementById('solutionModal');
    if (m) m.classList.remove('hidden');
}

function closeSolutionModal() {
    var m = document.getElementById('solutionModal');
    if (m) m.classList.add('hidden');
}

/* SQL syntax highlighting */
function initSQLHighlight() {
    document.querySelectorAll('.terminal-body[data-highlight="sql"]').forEach(function(el) {
        var text = el.textContent;
        text = text.replace(/(--[^\n]*)/g, '<span class="sql-comment">$1</span>');
        text = text.replace(/('(?:[^'\\]|\\.)*')/g, '<span class="sql-string">$1</span>');
        text = text.replace(/\b(SELECT|FROM|WHERE|AND|OR|NOT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|TABLE|INTO|VALUES|SET|LIMIT|ORDER BY|GROUP BY|HAVING|JOIN|LEFT|RIGHT|INNER|OUTER|ON|AS|UNION|ALL|DISTINCT|LIKE|IN|BETWEEN|IS|NULL|EXISTS)\b/gi, '<span class="sql-keyword">$1</span>');
        text = text.replace(/\b(\d+)\b/g, '<span class="sql-number">$1</span>');
        el.innerHTML = text;
    });
}
