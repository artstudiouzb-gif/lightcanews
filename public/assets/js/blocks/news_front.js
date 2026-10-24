/*
 * «Страны региона»: вкладки поверх готового списка панелей. Без скрипта
 * видны все страны подряд со своими заголовками; скрипт показывает список
 * вкладок и оставляет одну панель. Клавиатура — стрелки, Home и End, как
 * у вкладок контейнера (blocks/tabs.js).
 */
(function () {
    'use strict';
    document.querySelectorAll('[data-nf-tabs]').forEach(function (root) {
        var list = root.querySelector('[role="tablist"]');
        var tabs = list ? Array.prototype.slice.call(list.querySelectorAll('[role="tab"]')) : [];
        if (tabs.length < 2) { return; }
        var panels = tabs.map(function (tab) { return document.getElementById(tab.getAttribute('aria-controls')); });

        var select = function (index, focus) {
            tabs.forEach(function (tab, n) {
                var on = n === index;
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
                tab.tabIndex = on ? 0 : -1;
                if (panels[n]) { panels[n].hidden = !on; }
            });
            if (focus) { tabs[index].focus(); }
        };

        list.hidden = false;
        root.classList.add('nf--tabbed');
        select(0, false);

        tabs.forEach(function (tab, n) {
            tab.addEventListener('click', function () { select(n, false); });
            tab.addEventListener('keydown', function (e) {
                var next = null;
                if (e.key === 'ArrowRight') { next = (n + 1) % tabs.length; }
                if (e.key === 'ArrowLeft') { next = (n - 1 + tabs.length) % tabs.length; }
                if (e.key === 'Home') { next = 0; }
                if (e.key === 'End') { next = tabs.length - 1; }
                if (next !== null) { e.preventDefault(); select(next, true); }
            });
        });
    });
})();
