/*
 * Interaksi aplikasi internal IPMI (sidebar drawer, menu akun, tabel responsif).
 * Vanilla JS, tanpa dependensi, dan hanya dimuat oleh layouts/internal.blade.php.
 * Tidak menyentuh atribut wire:* sehingga aman bersama Livewire.
 */
(function () {
    'use strict';

    var DESKTOP = window.matchMedia('(min-width: 1024px)');

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    onReady(function () {
        var body = document.body;
        var sidebar = document.getElementById('sidebar');
        var backdrop = document.querySelector('[data-nav-backdrop]');
        var openBtn = document.querySelector('[data-nav-open]');
        var closeBtn = document.querySelector('[data-nav-close]');
        var lastFocus = null;

        /* ---------- Sidebar (drawer di tablet/ponsel) ---------- */
        function setNav(open) {
            if (!sidebar) return;
            sidebar.classList.toggle('is-open', open);
            if (backdrop) backdrop.classList.toggle('is-visible', open);
            body.classList.toggle('nav-open', open);
            if (openBtn) openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');

            if (open) {
                lastFocus = document.activeElement;
                var first = sidebar.querySelector('a[href]');
                if (first) first.focus({ preventScroll: true });
            } else if (lastFocus && typeof lastFocus.focus === 'function') {
                lastFocus.focus({ preventScroll: true });
                lastFocus = null;
            }
        }

        if (openBtn) openBtn.addEventListener('click', function () { setNav(true); });
        if (closeBtn) closeBtn.addEventListener('click', function () { setNav(false); });
        if (backdrop) backdrop.addEventListener('click', function () { setNav(false); });

        if (sidebar) {
            sidebar.addEventListener('click', function (e) {
                // Tutup drawer setelah memilih menu (mobile).
                if (e.target.closest('a[href]') && !DESKTOP.matches) setNav(false);
            });
        }

        // Jika layar melebar ke desktop saat drawer terbuka, kembalikan keadaan.
        var onViewportChange = function (e) {
            if (e.matches && sidebar && sidebar.classList.contains('is-open')) {
                sidebar.classList.remove('is-open');
                if (backdrop) backdrop.classList.remove('is-visible');
                body.classList.remove('nav-open');
                if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
            }
        };
        if (DESKTOP.addEventListener) DESKTOP.addEventListener('change', onViewportChange);
        else if (DESKTOP.addListener) DESKTOP.addListener(onViewportChange);

        /* ---------- Menu akun ---------- */
        var accountBtn = document.querySelector('[data-account-toggle]');
        var accountPanel = document.querySelector('[data-account-panel]');

        function setAccount(open, restoreFocus) {
            if (!accountBtn || !accountPanel) return;
            accountPanel.hidden = !open;
            accountBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (!open && restoreFocus) accountBtn.focus({ preventScroll: true });
        }

        if (accountBtn && accountPanel) {
            accountBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                setAccount(accountPanel.hidden, false);
            });
            document.addEventListener('click', function (e) {
                if (!accountPanel.hidden && !accountPanel.contains(e.target) && !accountBtn.contains(e.target)) {
                    setAccount(false, false);
                }
            });
        }

        /* ---------- Escape menutup drawer / menu akun ---------- */
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (accountPanel && !accountPanel.hidden) {
                setAccount(false, true);
            } else if (sidebar && sidebar.classList.contains('is-open')) {
                setNav(false);
            }
        });

        /* ---------- Tabel responsif ----------
         * Bungkus tabel yang belum berada di dalam kontainer scroll horizontal
         * agar tidak memotong/mendorong layout di layar kecil. */
        document.querySelectorAll('#konten table').forEach(function (table) {
            var parent = table.parentElement;
            if (!parent || parent.classList.contains('overflow-x-auto') || parent.classList.contains('table-scroll')) return;
            var wrap = document.createElement('div');
            wrap.className = 'table-scroll';
            parent.insertBefore(wrap, table);
            wrap.appendChild(table);
        });
    });
})();
