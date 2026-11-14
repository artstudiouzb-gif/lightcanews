/* LightCA.News — поведение шаблона портала. Без зависимостей; всё, что
   здесь делается, — улучшение: без скрипта поиск ведёт на страницу поиска,
   а новости по странам видны все сразу. */
(function () {
    'use strict';

    var root = document.documentElement;

    // Тема: тот же ключ localStorage, что читает theme-init.js до отрисовки.
    var themeBtn = document.querySelector('[data-pt-theme]');
    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            try {
                localStorage.setItem('theme', next);
                localStorage.setItem('theme-base', root.getAttribute('data-theme-base') || 'light');
            } catch (e) { /* приватный режим: тема действует до перезагрузки */ }
        });
    }

    // Поиск окном, Ctrl+K открывает его с клавиатуры.
    var dialog = document.getElementById('site-quick-search-modal');
    var trigger = document.querySelector('[data-pt-search]');
    if (dialog && typeof dialog.showModal === 'function') {
        var open = function (event) {
            if (event) { event.preventDefault(); }
            dialog.showModal();
            var input = dialog.querySelector('input[type="search"]');
            if (input) { input.focus(); }
        };
        if (trigger) { trigger.addEventListener('click', open); }
        document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { open(event); }
        });
        var close = dialog.querySelector('[data-pt-search-close]');
        if (close) { close.addEventListener('click', function () { dialog.close(); }); }
        dialog.addEventListener('click', function (event) { if (event.target === dialog) { dialog.close(); } });
    }

    // Фильтр «Нити региона»: панель видна только со скриптом.
    var regions = document.querySelector('[data-pt-regions]');
    if (regions) {
        var bar = regions.querySelector('.pt-filters');
        var grid = regions.querySelector('.pt-regions');
        var buttons = bar ? bar.querySelectorAll('[data-pt-region]') : [];
        var cards = regions.querySelectorAll('[data-pt-region-card]');
        if (bar && buttons.length > 2) {
            bar.hidden = false;
            Array.prototype.forEach.call(buttons, function (button) {
                button.addEventListener('click', function () {
                    var key = button.getAttribute('data-pt-region');
                    Array.prototype.forEach.call(buttons, function (b) { b.setAttribute('aria-pressed', String(b === button)); });
                    Array.prototype.forEach.call(cards, function (card) {
                        card.hidden = key !== 'all' && card.getAttribute('data-pt-region-card') !== key;
                    });
                    grid.classList.toggle('is-filtered', key !== 'all');
                });
            });
        }
    }

    // Язык: меню закрывается кликом мимо.
    document.addEventListener('click', function (event) {
        document.querySelectorAll('.pt-lang[open]').forEach(function (details) {
            if (!details.contains(event.target)) { details.removeAttribute('open'); }
        });
    });
})();
