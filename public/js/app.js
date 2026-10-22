(function () {
    // ---- theme toggle (dark / light), remembered in the browser ----
    var root = document.documentElement;
    function setTheme(t) {
        root.setAttribute('data-bs-theme', t);
        try { localStorage.setItem('igk-theme', t); } catch (e) {}
        document.querySelectorAll('[data-theme-icon]').forEach(function (i) {
            i.className = t === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        });
    }
    document.querySelectorAll('[data-theme-toggle]').forEach(function (b) {
        b.addEventListener('click', function () {
            setTheme(root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark');
        });
    });
    setTheme(root.getAttribute('data-bs-theme') || 'dark');

    // ---- mobile sidebar ----
    var sb = document.getElementById('sidebar'), bd = document.getElementById('sbBackdrop');
    function toggle(open) {
        if (!sb) return;
        sb.classList.toggle('open', open);
        if (bd) bd.classList.toggle('show', open);
    }
    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (b) {
        b.addEventListener('click', function () { toggle(!sb.classList.contains('open')); });
    });
    if (bd) bd.addEventListener('click', function () { toggle(false); });

    // ---- flash messages fade out after a few seconds ----
    document.querySelectorAll('.alert[data-autohide]').forEach(function (a) {
        setTimeout(function () {
            a.style.transition = 'opacity .5s, transform .5s';
            a.style.opacity = '0'; a.style.transform = 'translateY(-6px)';
            setTimeout(function () { a.remove(); }, 520);
        }, 5000);
    });
})();
