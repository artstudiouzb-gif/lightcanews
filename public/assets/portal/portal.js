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

/* Статья: ход чтения, подсветка раздела в оглавлении, «Поделиться». */
(function () {
    'use strict';
    var article = document.querySelector('[data-pt-article]');
    if (!article) { return; }
    var fill = document.querySelector('[data-pt-progress]');
    var pct = document.querySelector('[data-pt-progress-pct]');
    var links = Array.prototype.slice.call(document.querySelectorAll('[data-pt-toc] a'));
    var heads = links.map(function (a) { return document.getElementById(a.getAttribute('href').slice(1)); });
    var queued = false;
    var update = function () {
        queued = false;
        var box = article.getBoundingClientRect();
        var total = box.height - window.innerHeight;
        var done = total > 0 ? Math.min(1, Math.max(0, -box.top / total)) : 1;
        if (fill) { fill.style.width = (done * 100).toFixed(1) + '%'; }
        if (pct) { pct.textContent = Math.round(done * 100) + '%'; }
        var current = -1;
        heads.forEach(function (h, i) { if (h && h.getBoundingClientRect().top < 160) { current = i; } });
        links.forEach(function (a, i) { a.classList.toggle('is-active', i === current); });
    };
    window.addEventListener('scroll', function () {
        if (!queued) { queued = true; window.requestAnimationFrame(update); }
    }, { passive: true });
    update();

    var share = document.querySelector('[data-pt-share]');
    if (share) {
        share.addEventListener('click', function () {
            var url = share.getAttribute('data-url') || location.href;
            if (navigator.share) {
                navigator.share({ title: document.title, url: url }).catch(function () {});
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function () {
                    share.textContent = share.getAttribute('data-done') || share.textContent;
                });
            }
        });
    }
})();

/* Появление блоков при прокрутке: [data-reveal] и «карточки по очереди»
   ([data-reveal-items]). Наблюдение включается кадром после первой
   отрисовки — иначе у блока первого экрана нечего анимировать. */
(function () {
    'use strict';
    var stop = typeof window.asdrStopMotion === 'function' && window.asdrStopMotion();
    var reveals = Array.prototype.slice.call(document.querySelectorAll('[data-reveal]'));
    var sections = Array.prototype.slice.call(document.querySelectorAll('[data-reveal-items]'));
    if (!reveals.length && !sections.length) { return; }
    var show = function (el) { el.classList.add('is-visible'); };
    if (stop || !('IntersectionObserver' in window)) { reveals.forEach(show); return; }
    var later = function (fn) { requestAnimationFrame(function () { requestAnimationFrame(fn); }); };
    var io = new IntersectionObserver(function (entries, obs) {
        entries.forEach(function (e) { if (e.isIntersecting) { show(e.target); obs.unobserve(e.target); } });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    later(function () { reveals.forEach(function (el) { io.observe(el); }); });

    var GRIDS = '.cards-grid, .contact-cards, .block-news__grid, .mediagallery-grid, .block-counters__grid, .block-faq__list, .icon-text__grid, .imgcards-grid';
    var cio = new IntersectionObserver(function (entries, obs) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) { return; }
            (e.target.__cards || []).forEach(function (c, i) {
                c.style.setProperty('--card-reveal-delay', Math.min(i * 60, 360) + 'ms');
                c.classList.add('is-inview');
            });
            obs.unobserve(e.target);
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -6% 0px' });
    var plain = [];
    sections.forEach(function (s) {
        var grid = s.querySelector(GRIDS);
        var cards = grid ? Array.prototype.filter.call(grid.children, function (c) { return c.nodeType === 1; }) : [];
        if (cards.length < 2) { plain.push(s); return; }
        s.classList.add('is-motion-ready');
        cards.forEach(function (c) { c.classList.add('anim-card'); });
        grid.__cards = cards;
        later(function () { cio.observe(grid); });
    });
    // Секции без сетки появляются целиком: настройка обязана что-то менять.
    plain.forEach(function (s) {
        s.style.transition = 'none';
        s.setAttribute('data-reveal-type', 'fade');
        s.setAttribute('data-reveal', '');
    });
    if (plain.length) {
        void document.body.offsetHeight;
        requestAnimationFrame(function () {
            plain.forEach(function (s) { s.style.transition = ''; });
            later(function () { plain.forEach(function (s) { io.observe(s); }); });
        });
    }
})();
