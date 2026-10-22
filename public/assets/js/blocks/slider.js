/**
 * Слайдеры: блок «Слайдер» (галерея изображений) и обложка со слайдами.
 * Разметка у них разная, поведение — одно, поэтому логика общая: показ слайда,
 * точки, стрелки, клавиатура, свайп и необязательная автопрокрутка.
 */
(function () {
    'use strict';

    // Проверяем при каждом тике: «остановка анимаций» в панели настроек
    // переключается на лету, а закэшированное значение этого не заметит.
    var reduceMotion = function () {
        return window.asdrReduceMotion
            ? window.asdrReduceMotion()
            : !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    };

    function initSlider(root, options) {
        var slides = root.querySelectorAll(options.slide);
        if (slides.length < 2) {
            return;
        }
        var current = 0;
        var dots = root.querySelectorAll('[data-slide-index]');
        var status = root.querySelector('[data-slider-status]');

        function show(index, announce) {
            slides[current].classList.remove('is-active');
            slides[current].setAttribute('aria-hidden', 'true');
            current = (index + slides.length) % slides.length;
            slides[current].classList.add('is-active');
            slides[current].setAttribute('aria-hidden', 'false');
            dots.forEach(function (dot, dotIndex) {
                var active = dotIndex === current;
                dot.classList.toggle('is-active', active);
                dot.setAttribute('aria-current', active ? 'true' : 'false');
            });
            if (announce && status) {
                status.textContent = slides[current].getAttribute('aria-label') || String(current + 1);
            }
        }

        var prev = root.querySelector(options.prev);
        var next = root.querySelector(options.next);
        if (prev) {
            prev.addEventListener('click', function () { stopAuto(); show(current - 1, true); });
        }
        if (next) {
            next.addEventListener('click', function () { stopAuto(); show(current + 1, true); });
        }
        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                stopAuto();
                show(Number(dot.getAttribute('data-slide-index')) || 0, true);
            });
        });

        root.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                stopAuto();
                show(current - 1, true);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                stopAuto();
                show(current + 1, true);
            } else if (event.key === 'Home') {
                event.preventDefault();
                stopAuto();
                show(0, true);
            } else if (event.key === 'End') {
                event.preventDefault();
                stopAuto();
                show(slides.length - 1, true);
            }
        });

        var startX = null;
        var startY = null;
        root.addEventListener('pointerdown', function (event) {
            if (event.pointerType === 'mouse') { return; }
            startX = event.clientX;
            startY = event.clientY;
        }, { passive: true });
        root.addEventListener('pointerup', function (event) {
            if (startX === null || startY === null) { return; }
            var dx = event.clientX - startX;
            var dy = event.clientY - startY;
            startX = null;
            startY = null;
            if (Math.abs(dx) < 40 || Math.abs(dx) <= Math.abs(dy)) { return; }
            stopAuto();
            show(current + (dx < 0 ? 1 : -1), true);
        }, { passive: true });

        // --- Автопрокрутка ---
        // Выключена, если посетитель просил меньше движения: карусель, которая
        // едет сама, для него — помеха, а не украшение.
        var delay = Number(root.getAttribute('data-autoplay')) * 1000;
        var timer = null;

        function startAuto() {
            if (timer !== null || !delay || reduceMotion() || document.hidden) { return; }
            timer = window.setInterval(function () { show(current + 1, false); }, delay);
        }

        function stopAuto() {
            if (timer === null) { return; }
            window.clearInterval(timer);
            timer = null;
        }

        if (delay && !reduceMotion()) {
            // Пауза, пока посетитель читает слайд: курсор над ним, фокус внутри
            // или вкладка ушла в фон.
            root.addEventListener('mouseenter', stopAuto);
            root.addEventListener('mouseleave', startAuto);
            root.addEventListener('focusin', stopAuto);
            root.addEventListener('focusout', function (event) {
                if (!root.contains(event.relatedTarget)) { startAuto(); }
            });
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) { stopAuto(); } else { startAuto(); }
            });
            startAuto();
        }
    }

    /**
     * Случайный порядок кадров. Перемешивает браузер, а не сервер: страницы
     * кэшируются общим ключом, и порядок, выбранный при сборке, застыл бы для
     * всех посетителей до сброса кэша. Без JS карусель остаётся рабочей — она
     * просто идёт в порядке редактора.
     */
    function shuffleSlides(root) {
        var track = root.querySelector('.block-slider__track');
        if (!track) { return; }
        var slides = Array.prototype.slice.call(track.querySelectorAll('.block-slider__slide'));
        if (slides.length < 2) { return; }

        for (var i = slides.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = slides[i];
            slides[i] = slides[j];
            slides[j] = tmp;
        }
        slides.forEach(function (slide, index) {
            // Активным становится первый после перемешивания, иначе на экране
            // остался бы кадр, который редактор поставил первым.
            slide.classList.toggle('is-active', index === 0);
            slide.setAttribute('aria-hidden', index === 0 ? 'false' : 'true');
            var img = slide.querySelector('img');
            if (img && index === 0) { img.removeAttribute('loading'); }
            track.appendChild(slide);
        });
    }

    document.querySelectorAll('.block-slider').forEach(function (slider) {
        var start = function () {
            if (slider.hasAttribute('data-slider-shuffle')) {
                shuffleSlides(slider);
            }
            initSlider(slider, {
                slide: '.block-slider__slide',
                prev: '.block-slider__prev',
                next: '.block-slider__next'
            });
        };

        start();
    });

    document.querySelectorAll('[data-hero-slider]').forEach(function (hero) {
        initSlider(hero, {
            slide: '.block-hero__slide',
            prev: '[data-hero-prev]',
            next: '[data-hero-next]'
        });
    });
})();
