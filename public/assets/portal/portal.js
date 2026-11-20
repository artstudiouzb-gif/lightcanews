/* LightCA.News — поведение шаблона портала. Без зависимостей; всё, что
   здесь делается, — улучшение: без скрипта поиск ведёт на страницу поиска,
   а новости по странам видны все сразу. */
(function () {
    'use strict';

    var root = document.documentElement;

    // Тема: тот же ключ localStorage, что читает theme-init.js до отрисовки.
    // Функцию зовёт и панель настроек отображения (a11y.js).
    var setTheme = function (next) {
        root.setAttribute('data-theme', next);
        try {
            localStorage.setItem('theme', next);
            localStorage.setItem('theme-base', root.getAttribute('data-theme-base') || 'light');
        } catch (e) { /* приватный режим: тема действует до перезагрузки */ }
    };
    window.asdrSetTheme = setTheme;
    var themeBtn = document.querySelector('[data-pt-theme]');
    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            setTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        });
    }

    // Иконка из спрайта страницы — ей рисуют свои кнопки push.js и соседи
    // (в прежнем шаблоне ту же функцию отдаёт frontend.js).
    window.asdrPublicIcon = function (name, size, className) {
        name = String(name || '').trim().toLowerCase().replace(/^tabler-/, '');
        if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(name)) { return ''; }
        size = Math.max(8, Math.min(160, Number(size) || 18));
        return '<svg class="icon icon-tabler ' + (className || 'ui-icon') + '" width="' + size + '" height="' + size
            + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
            + 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
            + '<use href="#tabler-' + name + '"></use></svg>';
    };

    // Подпункты меню. Панель стоит в строке шапки (.pt-mast__row), а не в
    // прокручиваемой полосе: место считается здесь — под пунктом, мега-меню и
    // узкий экран — во всю ширину от полосы меню.
    var subItems = Array.prototype.slice.call(document.querySelectorAll('[data-pt-sub]'));
    var closeSub = function (item, focusToggle) {
        var btn = item.querySelector('.pt-nav__toggle');
        var panel = btn && document.getElementById(btn.getAttribute('aria-controls'));
        item.classList.remove('is-open');
        if (btn) { btn.setAttribute('aria-expanded', 'false'); }
        if (panel) { panel.hidden = true; }
        if (focusToggle && btn) { btn.focus(); }
    };
    var placeSub = function (item, panel) {
        var row = item.closest('.pt-mast__row');
        var nav = item.closest('.pt-nav');
        if (!row || !nav) { return; }
        var rowBox = row.getBoundingClientRect();
        var itemBox = item.getBoundingClientRect();
        var navBox = nav.getBoundingClientRect();
        var wide = panel.classList.contains('pt-sub--mega') || window.matchMedia('(max-width: 850px)').matches;
        panel.style.top = (itemBox.bottom - rowBox.top) + 'px';
        if (wide) {
            panel.style.left = (window.matchMedia('(max-width: 850px)').matches ? 0 : navBox.left - rowBox.left) + 'px';
            panel.style.right = '0px';
        } else {
            panel.style.right = 'auto';
            var left = itemBox.left - rowBox.left;
            panel.style.left = Math.max(0, Math.min(left, rowBox.width - panel.offsetWidth)) + 'px';
        }
    };
    var openSub = function (item) {
        var btn = item.querySelector('.pt-nav__toggle');
        var panel = btn && document.getElementById(btn.getAttribute('aria-controls'));
        if (!panel) { return; }
        subItems.forEach(function (other) { if (other !== item) { closeSub(other, false); } });
        item.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        panel.hidden = false;
        placeSub(item, panel);
    };
    var hoverable = window.matchMedia('(hover: hover) and (pointer: fine)');
    subItems.forEach(function (item) {
        var btn = item.querySelector('.pt-nav__toggle');
        if (!btn) { return; }
        btn.addEventListener('click', function () {
            if (item.classList.contains('is-open')) { closeSub(item, false); } else { openSub(item); }
        });
        var timer = 0;
        item.addEventListener('mouseenter', function () {
            if (!hoverable.matches) { return; }
            clearTimeout(timer);
            openSub(item);
        });
        item.addEventListener('mouseleave', function () {
            if (!hoverable.matches) { return; }
            timer = setTimeout(function () { closeSub(item, false); }, 200);
        });
        item.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && item.classList.contains('is-open')) {
                closeSub(item, true);
            }
        });
        item.addEventListener('focusout', function (event) {
            if (!item.contains(event.relatedTarget)) { closeSub(item, false); }
        });
    });
    if (subItems.length) {
        document.addEventListener('click', function (event) {
            subItems.forEach(function (item) { if (!item.contains(event.target)) { closeSub(item, false); } });
        });
        var navEl = document.querySelector('.pt-nav');
        if (navEl) { navEl.addEventListener('scroll', function () { subItems.forEach(function (item) { closeSub(item, false); }); }, { passive: true }); }
        window.addEventListener('resize', function () { subItems.forEach(function (item) { closeSub(item, false); }); });
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

/* Опрос в статье: голос уходит тем же адресом, что и в прежнем шаблоне,
   результаты собираются из ответа узлами, без разметки из строки. */
(function () {
    'use strict';
    document.querySelectorAll('[data-pt-poll]').forEach(function (card) {
        var form = card.querySelector('.pt-poll__form');
        var box = card.querySelector('.pt-poll__results');
        if (!form || !box) { return; }
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var picked = form.querySelector('input[name="poll_option"]:checked');
            if (!picked) { return; }
            var button = form.querySelector('button');
            if (button) { button.disabled = true; }
            var body = new URLSearchParams();
            body.append('option', picked.value);
            var csrf = form.querySelector('input[name="csrf_token"]');
            if (csrf) { body.append('csrf_token', csrf.value); }
            fetch('/api/polls/' + encodeURIComponent(card.getAttribute('data-pt-poll')) + '/vote', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            }).then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok || !data.ok || !data.results) { throw new Error(data.error || 'HTTP ' + res.status); }
                    return data.results;
                });
            }).then(function (results) {
                box.textContent = '';
                (Array.isArray(results.items) ? results.items : []).forEach(function (item) {
                    var pct = Math.min(100, Math.max(0, Number(item.percent) || 0));
                    var row = document.createElement('div');
                    row.className = 'pt-poll__row';
                    var label = document.createElement('span');
                    label.textContent = String(item.label == null ? '' : item.label);
                    var value = document.createElement('b');
                    value.textContent = pct + '%';
                    var bar = document.createElement('progress');
                    bar.max = 100; bar.value = pct;
                    row.appendChild(label); row.appendChild(value); row.appendChild(bar);
                    box.appendChild(row);
                });
                var total = document.createElement('p');
                total.className = 'pt-meta';
                total.textContent = (box.getAttribute('data-total-label') || '') + ' ' + (parseInt(results.total, 10) || 0);
                box.appendChild(total);
                form.hidden = true;
                box.hidden = false;
            }).catch(function () {
                if (button) { button.disabled = false; }
            });
        });
    });
})();

/* Галерея статьи: стрелки, счётчик, миниатюры и просмотр на весь экран.
   Лента кадров — обычная прокрутка с привязкой, скрипт только двигает её
   и следит, какой кадр в центре. */
(function () {
    'use strict';
    document.querySelectorAll('[data-pt-gallery]').forEach(function (gal) {
        var track = gal.querySelector('[data-pt-gal-track]');
        if (!track) { return; }
        var slides = Array.prototype.slice.call(track.children);
        var thumbs = Array.prototype.slice.call(gal.querySelectorAll('[data-pt-gal-thumb]'));
        var current = gal.querySelector('[data-pt-gal-current]');
        var prev = gal.querySelector('[data-pt-gal-prev]');
        var next = gal.querySelector('[data-pt-gal-next]');
        var index = 0;
        var go = function (i) {
            index = Math.max(0, Math.min(slides.length - 1, i));
            track.scrollTo({ left: slides[index].offsetLeft - track.offsetLeft, behavior: 'smooth' });
        };
        var mark = function (i) {
            index = i;
            if (current) { current.textContent = String(i + 1); }
            thumbs.forEach(function (t, n) {
                t.classList.toggle('is-active', n === i);
                if (n === i) { t.setAttribute('aria-current', 'true'); } else { t.removeAttribute('aria-current'); }
            });
            var thumb = thumbs[i];
            if (thumb && thumb.parentElement) {
                var bar = thumb.parentElement;
                var left = thumb.offsetLeft - bar.offsetLeft - (bar.clientWidth - thumb.clientWidth) / 2;
                bar.scrollTo({ left: left, behavior: 'smooth' });
            }
            if (prev) { prev.disabled = i === 0; }
            if (next) { next.disabled = i === slides.length - 1; }
        };
        if (prev && next) {
            prev.hidden = false; next.hidden = false;
            prev.addEventListener('click', function () { go(index - 1); });
            next.addEventListener('click', function () { go(index + 1); });
        }
        thumbs.forEach(function (t, n) {
            t.addEventListener('click', function (e) { e.preventDefault(); go(n); });
        });
        track.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') { e.preventDefault(); go(index - 1); }
            if (e.key === 'ArrowRight') { e.preventDefault(); go(index + 1); }
        });
        var queued = false;
        track.addEventListener('scroll', function () {
            if (queued) { return; }
            queued = true;
            requestAnimationFrame(function () {
                queued = false;
                var i = Math.round(track.scrollLeft / Math.max(1, track.clientWidth));
                if (i !== index) { mark(Math.max(0, Math.min(slides.length - 1, i))); }
            });
        }, { passive: true });
        mark(0);

        // Просмотр на весь экран: кадр открывается в окне, листается стрелками.
        if (typeof HTMLDialogElement !== 'function') { return; }
        var box = document.createElement('dialog');
        box.className = 'pt-zoom';
        var img = document.createElement('img');
        img.alt = '';
        var cap = document.createElement('p');
        cap.className = 'pt-zoom__cap';
        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'pt-zoom__close';
        close.textContent = '×';
        close.setAttribute('aria-label', 'Esc');
        box.appendChild(close); box.appendChild(img); box.appendChild(cap);
        document.body.appendChild(box);
        var show = function (i) {
            index = Math.max(0, Math.min(slides.length - 1, i));
            var link = slides[index].querySelector('[data-pt-gal-zoom]');
            var pic = slides[index].querySelector('img');
            var fc = slides[index].querySelector('figcaption');
            img.src = link ? link.getAttribute('href') : '';
            img.alt = pic ? pic.alt : '';
            cap.textContent = (fc ? fc.textContent + ' · ' : '') + (index + 1) + ' / ' + slides.length;
        };
        slides.forEach(function (slide, n) {
            var link = slide.querySelector('[data-pt-gal-zoom]');
            if (!link) { return; }
            link.addEventListener('click', function (e) { e.preventDefault(); show(n); box.showModal(); });
        });
        close.addEventListener('click', function () { box.close(); });
        box.addEventListener('click', function (e) { if (e.target === box) { box.close(); } });
        box.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') { show(index - 1); }
            if (e.key === 'ArrowRight') { show(index + 1); }
        });
        box.addEventListener('close', function () { go(index); });
    });

    // Реклама: креативы одной зоны — соседи с одинаковым data-ad. Страница
    // кэшируется, поэтому «что сейчас в периоде» и «какой показать» решает
    // браузер: случайный при каждой загрузке или по очереди через интервал.
    // Смена по таймеру ждёт, пока курсор или фокус внутри креатива.
    // Группа — соседи одного родителя: лента бывает на главной дважды (общая
    // секция и отдельный блок), и у каждой копии зоны своя очередь.
    var adGroups = [];
    document.querySelectorAll('[data-ad]').forEach(function (el) {
        var group = adGroups.filter(function (g) {
            return g.parent === el.parentElement && g.key === el.getAttribute('data-ad');
        })[0];
        if (!group) {
            group = { parent: el.parentElement, key: el.getAttribute('data-ad'), items: [] };
            adGroups.push(group);
        }
        group.items.push(el);
    });
    adGroups.forEach(function (group) {
        var all = group.items;
        var now = Date.now() / 1000;
        var live = all.filter(function (el) {
            var from = Number(el.getAttribute('data-ad-from') || 0);
            var to = Number(el.getAttribute('data-ad-to') || 0);
            return (!from || from <= now) && (!to || to > now);
        });
        var show = function (target) {
            all.forEach(function (el) { el.hidden = el !== target; });
        };
        if (!live.length) { show(null); return; }
        var index = Math.floor(Math.random() * live.length);
        show(live[index]);
        if (live.length < 2 || all[0].getAttribute('data-ad-rotate') !== 'interval') { return; }
        var seconds = Math.max(5, Number(all[0].getAttribute('data-ad-interval')) || 15);
        var paused = false;
        live.forEach(function (el) {
            el.addEventListener('mouseenter', function () { paused = true; });
            el.addEventListener('mouseleave', function () { paused = false; });
            el.addEventListener('focusin', function () { paused = true; });
            el.addEventListener('focusout', function () { paused = false; });
        });
        window.setInterval(function () {
            if (paused || document.hidden) { return; }
            var t = Date.now() / 1000;
            var next = live.filter(function (el) {
                var to = Number(el.getAttribute('data-ad-to') || 0);
                return !to || to > t;
            });
            if (!next.length) { show(null); return; }
            index = (index + 1) % next.length;
            live = next;
            show(live[index]);
        }, seconds * 1000);
    });

    // Лента: «Последние» / «Главные» (самое читаемое за неделю). Без скрипта
    // видна лента «Последние», вкладки скрыты.
    document.querySelectorAll('[data-pt-tabs]').forEach(function (tabs) {
        var buttons = Array.prototype.slice.call(tabs.querySelectorAll('[role="tab"]'));
        tabs.hidden = false;
        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                buttons.forEach(function (b) {
                    var on = b === button;
                    b.setAttribute('aria-selected', on ? 'true' : 'false');
                    var panel = document.getElementById(b.getAttribute('aria-controls'));
                    if (panel) { panel.hidden = !on; }
                });
            });
        });
    });

    // «Видео и подкасты»: фильтр по виду выпуска.
    document.querySelectorAll('[data-pt-media]').forEach(function (section) {
        var tabs = section.querySelector('[data-pt-media-tabs]');
        if (!tabs) { return; }
        tabs.hidden = false;
        var buttons = Array.prototype.slice.call(tabs.querySelectorAll('button[data-kind]'));
        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var kind = button.getAttribute('data-kind');
                buttons.forEach(function (b) { b.setAttribute('aria-pressed', b === button ? 'true' : 'false'); });
                section.querySelectorAll('.pt-media__list [data-kind]').forEach(function (item) {
                    item.hidden = kind !== 'all' && item.getAttribute('data-kind') !== kind;
                });
            });
        });
    });
})();
