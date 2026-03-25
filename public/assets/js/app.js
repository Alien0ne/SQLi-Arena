/* =================================================================
   SQLi-Arena -- JS
================================================================= */

document.addEventListener('DOMContentLoaded', function() {
    document.documentElement.classList.add('js-ready');
    initScrollAnimations();
    initHints();
    try { initSQLHighlight(); } catch(e) { console.warn('SQL highlight error:', e); }
    try { initPHPHighlight(); } catch(e) { console.warn('PHP highlight error:', e); }
    initQueryToggle();
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
    }, { threshold: 0.01, rootMargin: '0px 0px 200px 0px' });

    els.forEach(function(el) { obs.observe(el); });
}

/* Hint toggles */
function initHints() {
    document.querySelectorAll('.hint-toggle').forEach(function(btn) {
        btn.setAttribute('aria-expanded', 'false');
        btn.addEventListener('click', function() {
            var target = document.getElementById(btn.dataset.hint);
            if (target) {
                target.classList.toggle('show');
                btn.setAttribute('aria-expanded', target.classList.contains('show'));
            }
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

/* Query visibility toggle (black-box mode) */
function initQueryToggle() {
    var toggle = document.getElementById('queryToggle');
    if (!toggle) return;
    var show = localStorage.getItem('sqli_hide_query') !== '1';
    applyQueryVisibility(show);
}

function toggleQueryVisibility() {
    var show = document.body.classList.contains('query-hidden');
    localStorage.setItem('sqli_hide_query', show ? '0' : '1');
    applyQueryVisibility(show);
}

function applyQueryVisibility(show) {
    var toggle = document.getElementById('queryToggle');
    if (!toggle) return;
    var cb = toggle.querySelector('input[type="checkbox"]');
    if (show) {
        document.body.classList.remove('query-hidden');
        if (cb) cb.checked = true;
    } else {
        document.body.classList.add('query-hidden');
        if (cb) cb.checked = false;
    }
}

/* PHP source highlighting (white-box mode) */
function initPHPHighlight() {
    document.querySelectorAll('pre.source-code').forEach(function(el) {
        var raw = el.textContent;
        var tokens = [];
        var i = 0;

        while (i < raw.length) {
            // Block comments
            if (raw[i] === '/' && raw[i+1] === '*') {
                var end = raw.indexOf('*/', i + 2);
                if (end === -1) end = raw.length - 2;
                tokens.push({type: 'comment', text: raw.substring(i, end + 2)});
                i = end + 2;
            }
            // Line comments
            else if (raw[i] === '/' && raw[i+1] === '/') {
                var nl = raw.indexOf('\n', i);
                if (nl === -1) nl = raw.length;
                tokens.push({type: 'comment', text: raw.substring(i, nl)});
                i = nl;
            }
            // Single-quoted strings
            else if (raw[i] === "'") {
                var j = i + 1;
                while (j < raw.length && raw[j] !== "'") { if (raw[j] === '\\') j++; j++; }
                tokens.push({type: 'string', text: raw.substring(i, j + 1)});
                i = j + 1;
            }
            // Double-quoted strings
            else if (raw[i] === '"') {
                var j = i + 1;
                while (j < raw.length && raw[j] !== '"') { if (raw[j] === '\\') j++; j++; }
                tokens.push({type: 'string', text: raw.substring(i, j + 1)});
                i = j + 1;
            }
            // Variables
            else if (raw[i] === '$' && /[a-zA-Z_]/.test(raw[i+1] || '')) {
                var m = raw.substring(i).match(/^\$[a-zA-Z_]\w*/);
                if (!m) { tokens.push({type: 'plain', text: raw[i]}); i++; continue; }
                tokens.push({type: 'var', text: m[0]});
                i += m[0].length;
            }
            // Words (keywords/functions/identifiers)
            else if (/[a-zA-Z_]/.test(raw[i])) {
                var m = raw.substring(i).match(/^[a-zA-Z_]\w*/);
                if (!m) { tokens.push({type: 'plain', text: raw[i]}); i++; continue; }
                tokens.push({type: 'word', text: m[0]});
                i += m[0].length;
            }
            // Everything else
            else {
                var start = i;
                while (i < raw.length && !/[a-zA-Z_$'"\/]/.test(raw[i])) i++;
                tokens.push({type: 'plain', text: raw.substring(start, i)});
            }
        }

        var kw = /^(if|else|elseif|while|for|foreach|return|function|class|new|try|catch|throw|isset|empty|echo|require_once|require|include|include_once|null|true|false|as)$/;
        var fn = /^(mysqli_query|mysqli_fetch_assoc|mysqli_error|mysqli_multi_query|mysqli_store_result|mysqli_free_result|mysqli_next_result|htmlspecialchars|json_encode|json_decode|trim|preg_match|array|header|exit|session_start|unset|pg_query|pg_fetch_assoc|pg_last_error|sqlsrv_query|sqlsrv_fetch_array|sqlsrv_errors|oci_parse|oci_execute|oci_fetch_array|oci_error)$/;

        function esc(s) {
            return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        var html = tokens.map(function(t) {
            var s = esc(t.text);
            if (t.type === 'comment') return '<span class="php-comment">' + s + '</span>';
            if (t.type === 'string')  return '<span class="php-string">' + s + '</span>';
            if (t.type === 'var')     return '<span class="php-var">' + s + '</span>';
            if (t.type === 'word' && kw.test(t.text)) return '<span class="php-keyword">' + s + '</span>';
            if (t.type === 'word' && fn.test(t.text)) return '<span class="php-func">' + s + '</span>';
            return s;
        }).join('');

        el.innerHTML = html;
    });
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
