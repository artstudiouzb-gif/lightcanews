<?php

declare(strict_types=1);

use App\Core\Portal\Portal;
use App\Models\Setting;

/*
 * Новый шаблон портала — отдельный слой, а не перекраска темы госсайта:
 * свои вьюхи, свой CSS и скрипт. Проверяется вывод: главная печатает шапку и
 * подвал портала и не тянет за собой бандл прежней темы.
 */

function portal_home_html(array $home): string
{
    $_SERVER['REQUEST_URI'] = '/';

    return render_view('app/Views/portal/home.php', [
        'home' => $home,
        'metaTitle' => '',
        'metaDescription' => '',
    ]);
}

test('Портал: шаблон включён по умолчанию, прежний — запасной выход', function (): void {
    Setting::set('public_template', '');
    assert_true(Portal::enabled());
    Setting::set('public_template', 'classic');
    assert_true(!Portal::enabled());
    Setting::set('public_template', 'portal');
    assert_true(Portal::enabled());
});

test('Портал: главная собрана своим шаблоном, без бандла прежней темы', function (): void {
    $cards = Portal::cards(sample_news_rows(5), 'ru');
    $html = portal_home_html([
        'lead' => $cards[0], 'grid' => array_slice($cards, 1, 3), 'also' => [],
        'feed' => $cards, 'picks' => [], 'regions' => [], 'voices' => [],
    ]);

    assert_contains('class="pt-mast"', $html);
    assert_contains('class="pt-foot"', $html);
    assert_contains('/assets/portal/portal.css', $html);
    assert_contains('/assets/portal/portal.js', $html);
    assert_not_contains('public.min.css', $html, 'бандл прежней темы на портале не подключается');
    assert_contains('Новость 0', $html);
    assert_contains('href="/news/probe-news-1"', $html);
    // Пустые секции не печатаются рамкой с заголовком.
    assert_not_contains('pt-picks', $html);
    assert_not_contains('data-pt-regions', $html);
});

test('Портал: акцент и основной цвет приходят из «Дизайна»', function (): void {
    Setting::set('color_accent', '#123456');
    Setting::set('color_primary', '#654321');
    $html = portal_home_html(['lead' => null, 'feed' => []]);
    assert_contains('--brand:#123456;--brand-deep:#654321', $html);
    assert_contains('Пока нет опубликованных новостей.', $html);
});

test('Портал: ширина сайта и колонки ленты — из «Дизайна»', function (): void {
    ensure_test_db();
    reset_design_state();
    Setting::set('design_container_custom', '1300px');
    Setting::set('design_portal_feed_width', '340');
    $html = portal_home_html(['lead' => null, 'feed' => []]);
    assert_contains('--wrap:1300px', $html, 'ширина контейнера доходит до портала');
    assert_contains('--pt-feed-w:340px', $html, 'ширина ленты новостей');
    Setting::set('design_portal_feed_width', '9000');
    Setting::set('design_container_custom', '');
    $html = portal_home_html(['lead' => null, 'feed' => []]);
    assert_true(!str_contains($html, '--pt-feed-w'), 'вне пределов — треть, как в прототипе');
    assert_contains('--wrap:1440px', $html, 'без своей ширины — пресет');
});

test('Портал: код страны — по slug, для незнакомой страны — из названия', function (): void {
    assert_same('UZ', Portal::countryCode(['slug' => 'uzbekistan', 'name' => 'Узбекистан']));
    assert_same('МИР', Portal::countryCode(['slug' => 'world', 'name' => 'В мире']), 'на русском — МИР');
    assert_same('МО', Portal::countryCode(['slug' => 'mongolia', 'name' => 'Монголия']));
});

test('Портал: метки стран — коды, флаги или ничего, по настройке «Дизайна»', function (): void {
    ensure_test_db();
    $uz = ['slug' => 'uzbekistan', 'name' => 'Узбекистан'];
    $world = ['slug' => 'world', 'name' => 'В мире'];

    \App\Core\DesignSettings::save(['country_codes' => 'flags']);
    $flag = Portal::countryMark($uz, true);
    assert_contains('<svg class="pt-flag"', $flag, 'флаг вместо кода');
    assert_contains('aria-label="Узбекистан"', $flag, 'флагу без названия рядом нужна подпись');
    assert_contains('aria-hidden="true"', Portal::countryMark($uz), 'рядом с названием флаг — украшение');
    assert_contains('pt-flag--globe', Portal::countryMark($world), '«В мире» — глобус');
    assert_same('МО', Portal::countryMark(['slug' => 'mongolia', 'name' => 'Монголия']), 'страна без флага — код');

    \App\Core\DesignSettings::save(['country_codes' => 'off']);
    assert_same('', Portal::countryMark($uz), 'выключено — пусто');
    assert_same('', Portal::countryCode($uz));

    \App\Core\DesignSettings::save(['country_codes' => 'bogus']);
    assert_same('UZ', Portal::countryMark($uz), 'чужое значение — коды');
});

test('Портал: лента раздела — крупная первая новость, сетка и полоса страниц', function (): void {
    $_SERVER['REQUEST_URI'] = '/news';
    $html = render_view('app/Views/portal/news_index.php', [
        'items' => sample_news_rows(5), 'page' => 1, 'pages' => 3,
        'categories' => [], 'countries' => [], 'section' => null,
        'basePath' => 'news', 'sectionPage' => null,
    ]);
    assert_contains('class="pt-pkg pt-listing__lead-card"', $html);
    assert_same(4, substr_count($html, 'class="pt-ncard"'), 'остальные новости — сеткой');
    assert_contains('class="pt-pager"', $html);
    assert_contains('href="/news?page=2"', $html);
    assert_not_contains('public.min.css', $html);
});

test('Портал: статья — оглавление из заголовков текста и микроразметка', function (): void {
    $_SERVER['REQUEST_URI'] = '/news/probe-news-0';
    $news = sample_news_rows(1)[0] + [
        'content' => '<p>Вступление.</p><h2>Первый раздел</h2><p>Текст.</p><h2>Второй раздел</h2><p>Текст.</p>',
        'meta_title' => '', 'meta_description' => '', 'lead_html' => '', 'key_points' => "Пункт один\nПункт два",
        'hashtags' => '', 'docs' => '[]', 'video_url' => '', 'updated_at' => '2026-07-19 10:00:00', 'lang' => 'ru',
    ];
    $html = render_view('app/Views/portal/news_show.php', [
        'news' => $news, 'gallery' => [], 'related' => [], 'nextNews' => null,
    ]);
    assert_contains('id="sec-1"', $html);
    assert_contains('href="#sec-2"', $html);
    assert_contains('Пункт два', $html);
    assert_contains('"@type":"NewsArticle"', str_replace(' ', '', $html));
    assert_contains('data-pt-article', $html);
});

test('Портал: страница из блоков получает стили блоков портала, а не тему', function (): void {
    $_SERVER['REQUEST_URI'] = '/o-nas';
    $html = render_view('app/Views/portal/page.php', [
        'page' => ['id' => 1, 'title' => 'О нас', 'lead' => 'Лид', 'meta_title' => '', 'meta_description' => '', 'parent_id' => null, 'lang' => 'ru'],
        'content' => '<section class="cms-block cms-block--text"><div class="block-text"><p>Текст</p></div></section>',
        'blockCss' => '', 'sidebar' => null,
    ]);
    assert_contains('/assets/portal/blocks.css', $html);
    assert_contains('class="pt-pagehead__title">О нас', $html);
    assert_contains('class="pt-blocks"', $html);
    assert_not_contains('gov-theme', $html);
});

test('Портал: поиск и альбомы собраны шаблоном портала', function (): void {
    $_SERVER['REQUEST_URI'] = '/search?q=ткань';
    $html = render_view('app/Views/portal/search.php', [
        'query' => 'ткань',
        'results' => [['type' => 'Новость', 'url' => '/news/a', 'title' => 'Ткань года', 'excerpt' => 'Анонс']],
    ]);
    assert_contains('class="pt-results"', $html);
    assert_contains('Ткань года', $html);
    assert_contains('noindex', $html);
    assert_not_contains('public.min.css', $html);

    $_SERVER['REQUEST_URI'] = '/albums';
    $html = render_view('app/Views/portal/albums.php', [
        'albums' => [['slug' => 'expo', 'title' => 'Выставка', 'cover' => '', 'images_count' => 3]],
    ]);
    assert_contains('href="/albums/expo"', $html);
    assert_contains('data-empty', $html);
    assert_not_contains('public.min.css', $html);
});

test('Портал: рекламная зона выводится только включённой и заполненной', function (): void {
    \App\Core\Portal\PortalAds::save([]);
    $cards = Portal::cards(sample_news_rows(5), 'ru');
    $home = ['lead' => $cards[0], 'grid' => [], 'also' => [], 'feed' => $cards, 'picks' => [], 'regions' => [], 'voices' => []];
    $html = portal_home_html($home);
    assert_not_contains('pt-ad', $html, 'выключенная зона не оставляет пустой рамки');

    \App\Core\Portal\PortalAds::save([
        'top' => ['enabled' => '1', 'sponsor' => 'CAITME', 'title' => 'Выставка оборудования', 'url' => 'https://example.org/expo', 'cta' => 'Бейдж'],
        'mid' => ['enabled' => '1', 'title' => 'Без ссылки', 'url' => 'javascript:alert(1)'],
        'feed' => ['enabled' => '1', 'title' => 'Прядильные линии', 'url' => '/expo', 'image' => '/uploads/public/a.jpg', 'sponsor' => 'uztex.uz'],
    ]);
    $html = portal_home_html($home);
    assert_contains('Выставка оборудования', $html);
    assert_contains('rel="sponsored noopener" target="_blank"', $html);
    assert_contains('class="pt-feed__ad"', $html);
    assert_not_contains('javascript:', $html, 'чужая схема ссылки отбрасывается');
    assert_not_contains('Без ссылки', $html, 'зона без ссылки не выводится');
    assert_same(1, substr_count($html, 'class="pt-feed__ad"'), 'объявление в ленте одно');
});

test('Портал: рекламная зона показывается только на отмеченных типах страниц', function (): void {
    $ads = \App\Core\Portal\PortalAds::class;
    $base = ['enabled' => '1', 'title' => 'Баннер', 'url' => 'https://example.org'];
    // Запись без списка страниц получает умолчание: широкий баннер — только главная.
    $ads::save(['mid' => $base]);
    assert_true($ads::zone('mid', 'home') !== null);
    assert_true($ads::zone('mid', 'news_show') === null);
    // Отмеченные страницы; место, которого у зоны нет, отбрасывается.
    $ads::save(['mid' => $base + ['pages' => ['', 'news_index', 'news_show', 'other']]]);
    assert_true($ads::zone('mid', 'home') === null);
    assert_true($ads::zone('mid', 'news_show') !== null);
    assert_same(['news_index', 'news_show'], $ads::all()['mid']['pages']);
    // Ни одной отметки — зона нигде, а не «как по умолчанию».
    $ads::save(['top' => $base + ['pages' => ['']]]);
    assert_true($ads::zone('top', 'home') === null);

    $ads::save(['mid' => $base + ['pages' => ['news_index']]]);
    $_SERVER['REQUEST_URI'] = '/news';
    $html = render_view('app/Views/portal/news_index.php', [
        'items' => sample_news_rows(5), 'page' => 1, 'pages' => 1,
        'categories' => [], 'countries' => [], 'section' => null, 'basePath' => 'news', 'sectionPage' => null,
    ]);
    assert_contains('pt-ad--mid', $html, 'баннер встаёт между первой новостью и сеткой');
});

test('Портал: креативов в зоне несколько, у каждого свой период', function (): void {
    $ads = \App\Core\Portal\PortalAds::class;
    $now = (int) \App\Core\DateFormatter::format('2026-11-15 12:00', 'U');
    $ads::save(['top' => [
        'enabled' => '1', 'rotate' => 'interval', 'interval' => '2', 'pages' => ['home'],
        'items' => [
            ['title' => 'Прошедшая', 'url' => '/a', 'from' => '2026-10-01T00:00', 'to' => '2026-11-01T00:00'],
            ['title' => 'Текущая', 'url' => '/b', 'to' => '2026-12-01T00:00'],
            ['title' => 'Будущая', 'url' => '/c', 'from' => '2026-11-20T09:00'],
            ['title' => '', 'url' => ''],
        ],
    ]]);
    $saved = $ads::all()['top'];
    assert_same(3, count($saved['items']), 'пустая строка репитера не креатив');
    assert_same(5, $saved['interval'], 'интервал не меньше пяти секунд');
    assert_same('2026-11-20 09:00', $saved['items'][2]['from']);
    $zone = $ads::zone('top', 'home', $now);
    assert_true($zone !== null);
    assert_same(['Текущая', 'Будущая'], array_column($zone['items'], 'title'), 'закончившийся креатив не выводится');
    assert_same([true, false], array_column($zone['items'], 'shown'), 'без скрипта виден идущий сейчас');

    // Запись старше ротации: один креатив прямо в зоне.
    \App\Models\Setting::set($ads::SETTING, json_encode(['mid' => ['enabled' => true, 'title' => 'Старый баннер', 'url' => '/old']]));
    assert_same('Старый баннер', $ads::all()['mid']['items'][0]['title']);

    $ads::save(['top' => ['enabled' => '1', 'items' => [
        ['title' => 'Первый', 'url' => '/1'], ['title' => 'Второй', 'url' => '/2'],
    ]]]);
    $cards = Portal::cards(sample_news_rows(2), 'ru');
    $html = portal_home_html(['lead' => $cards[0], 'grid' => [], 'also' => [], 'feed' => $cards, 'picks' => [], 'regions' => [], 'voices' => []]);
    assert_same(2, substr_count($html, 'data-ad="top"'), 'печатаются все креативы зоны');
    assert_same(1, substr_count($html, 'data-ad="top" data-ad-rotate="load" data-ad-interval="15" hidden'), 'виден один');
});

test('Портал: партнёрский материал помечен на карточке и в статье', function (): void {
    $rows = sample_news_rows(2);
    $rows[0]['is_partner'] = 1;
    $cards = Portal::cards($rows, 'ru');
    assert_true($cards[0]['partner'] && !$cards[1]['partner']);
    $html = render_view('app/Views/portal/_tags.php', ['tagCard' => $cards[0], 'tagDate' => false]);
    assert_contains('pt-sponsored', $html);
    assert_contains('Партнёрский материал', $html);
});

test('Портал: статья сохраняет аудио, хронологию, источник, мероприятие и пресс-релиз', function (): void {
    $_SERVER['REQUEST_URI'] = '/news/probe-news-0';
    $news = sample_news_rows(1)[0] + [
        'content' => '<p>Текст.</p>', 'meta_title' => '', 'meta_description' => '', 'lead_html' => '', 'key_points' => '',
        'hashtags' => '', 'docs' => '[]', 'video_url' => 'https://example.org/clip.mp4', 'updated_at' => '2026-07-19 10:00:00', 'lang' => 'ru',
        'audio_url' => '/uploads/public/podcast.mp3', 'audio_title' => 'Подкаст выпуска',
        'source_note' => 'Источник: пресс-служба', 'event_meta' => "Дата: 20 ноября\nМесто: Ташкент",
        'timeline_json' => '[{"date":"2026","title":"Запуск фабрики","text":"Первая очередь"}]',
        'press_release_url' => '/uploads/public/release.pdf', 'poll_question' => '', 'poll_options_json' => '',
    ];
    $html = render_view('app/Views/portal/news_show.php', ['news' => $news, 'gallery' => [], 'related' => [], 'nextNews' => null]);
    assert_contains('<audio src="/uploads/public/podcast.mp3"', $html);
    assert_contains('Подкаст выпуска', $html);
    assert_contains('<video class="pt-art__video-file" src="https://example.org/clip.mp4"', $html, 'ролик-файл играет встроенный плеер');
    assert_contains('Запуск фабрики', $html);
    assert_contains('Источник: пресс-служба', $html);
    assert_contains('<dt>Место</dt><dd>Ташкент</dd>', $html);
    assert_contains('href="/uploads/public/release.pdf"', $html);
});

test('Портал: снимки статьи идут галереей с миниатюрами, а не сеткой', function (): void {
    $_SERVER['REQUEST_URI'] = '/news/probe-news-0';
    $news = sample_news_rows(1)[0];
    $news = array_merge($news, [
        'image' => '/uploads/public/cover.jpg', 'content' => '<p>Текст.</p>', 'meta_title' => '', 'meta_description' => '',
        'lead_html' => '', 'key_points' => '', 'hashtags' => '', 'docs' => '[]', 'video_url' => '', 'updated_at' => '2026-07-19 10:00:00', 'lang' => 'ru',
    ]);
    $gallery = [
        ['path' => '/uploads/public/cover.jpg', 'caption' => 'Обложка', 'credit' => ''],
        ['path' => '/uploads/public/two.jpg', 'caption' => 'Цех', 'credit' => 'Пресс-служба'],
        ['path' => '/uploads/public/three.jpg', 'caption' => '', 'credit' => ''],
    ];
    $html = render_view('app/Views/portal/news_show.php', ['news' => $news, 'gallery' => $gallery, 'related' => [], 'nextNews' => null]);
    assert_contains('data-pt-gallery', $html);
    assert_same(3, substr_count($html, 'class="pt-gal__slide"'), 'обложка первым кадром, затем остальные');
    assert_same(3, substr_count($html, 'data-pt-gal-thumb='), 'у каждого кадра миниатюра');
    assert_contains('Цех Пресс-служба', $html);
    assert_contains('href="/uploads/public/two.jpg" data-pt-gal-zoom', $html, 'кадр без скрипта ведёт на файл');
});

test('Портал: ссылки языкового меню переписывают запомненный язык', function (): void {
    $active = array_map(static fn (array $l): string => (string) $l['code'], \App\Models\Language::site());
    if (count($active) < 2) {
        return;
    }
    $_SERVER['REQUEST_URI'] = '/';
    $html = portal_home_html(['lead' => null, 'feed' => []]);
    // Без ?_lang= ссылка на основной язык уводила обратно на язык из cookie.
    assert_contains('?' . \App\Core\LocalePreference::QUERY . '=ru"', $html);
});

test('Портал: главная собирается из блоков «Секция портала», когда они стоят на странице', function (): void {
    ensure_test_db();
    $_SERVER['REQUEST_URI'] = '/';
    $pdo = \App\Core\Database::pdo();
    $pdo->prepare("INSERT INTO pages (title, slug, status, lang, entity_type, created_at) VALUES ('Главная', :s, 'published', 'ru', 'page', NOW())")
        ->execute([':s' => 'home-' . bin2hex(random_bytes(3))]);
    $pageId = (int) $pdo->lastInsertId();
    \App\Models\Block::create($pageId, 'ru', 'text', 'Старый блок', ['content' => '<p>x</p>'], '');

    $blocks = static fn (): array => \App\Models\Block::forPage($pageId, 'ru', true);
    assert_false(\App\Core\Portal\PortalHome::usesBlocks($blocks()), 'прежние блоки демо-главной не переводят её на конструктор');

    assert_same(count(\App\Core\Portal\Portal::DEFAULT_SECTIONS), \App\Core\Portal\PortalHome::seed($pageId, 'ru'), 'добавлены все секции');
    assert_true(\App\Core\Portal\PortalHome::usesBlocks($blocks()), 'с секциями главная из конструктора');
    assert_same(1 + count(\App\Core\Portal\Portal::DEFAULT_SECTIONS), count($blocks()), 'прежний блок остался без просьбы убрать');

    \App\Core\Portal\PortalHome::seed($pageId, 'ru', true);
    assert_same(count(\App\Core\Portal\Portal::DEFAULT_SECTIONS), count($blocks()), 'с «убрать прежние» — только секции');

    $html = render_view('templates/blocks/portal_section.php', [
        'data' => ['section' => 'lead', 'heading' => 'Свежее', 'limit' => 0],
        'blockId' => 1,
    ]);
    assert_contains('pt-home-top', $html, 'секция печатает разметку главной');
    assert_contains('Свежее', $html, 'свой заголовок секции');
    $sub = render_view('templates/blocks/portal_section.php', [
        'data' => ['section' => 'subscribe', 'heading' => '', 'limit' => 0],
        'blockId' => 2,
    ]);
    assert_contains('pt-subscribe', $sub, 'подписка');
});

test('Портал: элементы прототипа — цифры, вкладки ленты, номера, факт страны, автор, видео', function (): void {
    $cards = Portal::cards(sample_news_rows(6), 'ru');
    $sec = ['lead' => $cards[0], 'grid' => [], 'also' => [], 'feed' => $cards, 'feed_top' => array_slice($cards, 0, 2)];
    $main = render_view('app/Views/portal/_home_main.php', ['sec' => $sec, 'secTitle' => '', 'secFacts' => [['value' => '+18,0%', 'caption' => 'Рост экспорта']]]);
    assert_contains('class="pt-kpi"><strong>+18,0%</strong><span>Рост экспорта</span>', $main, 'ключевые цифры под «Также»');

    $feed = render_view('app/Views/portal/_home_feed.php', ['sec' => $sec, 'secTitle' => '', 'secUid' => 'b1']);
    assert_contains('data-pt-tabs', $feed, 'вкладки «Последние / Главные»');
    assert_contains('id="pt-feed-top-b1" role="tabpanel" aria-labelledby="pt-feed-tab-top-b1" hidden', $feed, 'вкладка «Главные» без скрипта скрыта');
    assert_same(1, substr_count($feed, 'class="is-hot"'), 'свежая новость подсвечена');

    $picks = render_view('app/Views/portal/_home_picks.php', ['sec' => ['picks' => array_slice($cards, 0, 2)], 'secTitle' => '']);
    assert_contains('<span class="pt-pcard__num" aria-hidden="true">02</span>', $picks, 'номера подборки');

    $regions = render_view('app/Views/portal/_home_regions.php', ['sec' => ['regions' => [[
        'fact' => '+18% экспорт', 'code' => 'UZ', 'mark' => 'UZ', 'name' => 'Узбекистан', 'url' => '/c/uz', 'items' => array_slice($cards, 0, 2),
    ]]], 'secTitle' => '']);
    assert_contains('<span class="pt-region__fact">+18% экспорт</span>', $regions, 'факт на фото страны');

    $rows = sample_news_rows(1);
    $rows[0] += ['author_name' => 'Дильноза Каримова', 'author_role' => 'Главный редактор', 'author_photo' => ''];
    $voices = render_view('app/Views/portal/_home_voices.php', ['sec' => ['voices' => ['title' => 'Точка зрения', 'url' => '/x', 'items' => Portal::cards($rows, 'ru')]], 'secTitle' => '']);
    assert_contains('<strong>Дильноза Каримова</strong><span class="pt-meta">Главный редактор</span>', $voices, 'автор колонки');

    $media = render_view('app/Views/portal/_home_media.php', ['sec' => ['media' => [
        ['kind' => 'video', 'title' => 'Ролик', 'url' => 'https://youtu.be/x', 'external' => true, 'cover' => '', 'duration' => '23:29', 'source' => 'YouTube', 'date' => '2026-11-10'],
        ['kind' => 'podcast', 'title' => 'Выпуск', 'url' => '/news/p', 'external' => false, 'cover' => '', 'duration' => '', 'source' => '', 'date' => '2026-11-09'],
    ]], 'secTitle' => 'Made in Uzbekistan', 'secCta' => ['title' => 'Студия', 'note' => '', 'text' => 'Предложить тему', 'url' => 'mailto:a@b.uz']]);
    assert_contains('Made in Uzbekistan', $media);
    assert_contains('target="_blank" rel="noopener"', $media, 'ролик открывается на YouTube');
    assert_contains('data-pt-media-tabs', $media, 'вкладки, когда видов выпусков два');
    assert_contains('pt-media__wave', $media, 'подкаст — волной');
    assert_contains('href="mailto:a@b.uz"', $media, 'плашка студии');
    assert_same('', trim(render_view('app/Views/portal/_home_media.php', ['sec' => ['media' => []], 'secTitle' => ''])), 'без выпусков секции нет');
});

test('Портал: Instagram-ролики — вертикальная полоса, ролики первыми', function (): void {
    ensure_test_db();
    \App\Models\Setting::set('instagram_feed_posts', json_encode([
        ['id' => '1', 'type' => 'IMAGE', 'caption' => 'Фото дня', 'title' => 'Фото дня', 'permalink' => 'https://www.instagram.com/p/AAA/', 'image' => '/uploads/public/a.jpg', 'timestamp' => ''],
        ['id' => '2', 'type' => 'VIDEO', 'caption' => 'Ролик с фабрики', 'title' => 'Ролик с фабрики', 'permalink' => 'https://www.instagram.com/reel/BBB/', 'image' => '/uploads/public/b.jpg', 'timestamp' => ''],
    ]));
    $reels = Portal::reels(8);
    assert_same(['VIDEO', 'IMAGE'], array_column($reels, 'type'), 'ролики первыми, фото добирают полосу');
    $html = render_view('app/Views/portal/_home_reels.php', ['sec' => ['reels' => $reels], 'secTitle' => '']);
    assert_contains('class="pt-reels__row" tabindex="0"', $html, 'полоса доступна с клавиатуры');
    assert_contains('href="https://www.instagram.com/reel/BBB/" target="_blank" rel="noopener noreferrer"', $html);
    assert_same(1, substr_count($html, 'pt-reel__play'), 'значок ролика только у видео');
    assert_same('', trim(render_view('app/Views/portal/_home_reels.php', ['sec' => ['reels' => []], 'secTitle' => ''])), 'без публикаций секции нет');
});

test('Портал: шапка слушает конструктор шапки — знак, меню, кнопки, переключатели', function (): void {
    ensure_test_db();
    $home = ['lead' => null, 'grid' => [], 'also' => [], 'feed' => [], 'picks' => [], 'regions' => [], 'voices' => []];
    $html = portal_home_html($home);
    assert_contains('class="pt-countries"', $html, 'по умолчанию полоса стран есть');
    assert_contains('data-pt-search aria-label', $html);
    assert_not_contains('pt-btn--live', $html, 'без ссылки кнопки «Онлайн» нет');

    $cfg = \App\Core\HeaderConfig::get();
    $cfg['portal'] = ['tagline' => 'Своя подпись', 'live_url' => '/live', 'live_text' => 'Эфир', 'nav' => 'menu',
        'show_countries' => false, 'show_date' => true, 'show_lang' => true, 'show_search' => false, 'show_theme' => false];
    $cfg['cta'] = ['enabled' => true, 'text' => 'Реклама', 'url' => '/ads', 'style' => 'filled'] + (array) $cfg['cta'];
    \App\Core\HeaderConfig::save($cfg);
    $html = portal_home_html($home);
    assert_contains('Своя подпись', $html, 'подпись в плашке');
    assert_contains('href="/live"', $html);
    assert_contains('Эфир', $html, 'кнопка «Онлайн» со своим текстом');
    assert_contains('class="pt-btn pt-btn--cta" href="/ads"', $html, 'кнопка CTA');
    assert_not_contains('class="pt-countries"', $html, 'полосу стран можно скрыть');
    assert_not_contains('data-pt-search aria-label', $html, 'поиск можно скрыть');
    assert_not_contains('data-pt-theme', $html, 'тему можно скрыть');
    assert_contains('aria-label="Меню"', $html, 'меню шапки — основное меню');

    $saved = \App\Core\HeaderConfig::get()['portal'];
    assert_same(false, $saved['show_countries']);
    assert_same('menu', $saved['nav']);
    \App\Core\HeaderConfig::save(['portal' => ['nav' => 'evil', 'logo_mode' => 'x']]);
    $saved = \App\Core\HeaderConfig::get()['portal'];
    assert_same(['rubrics', 'text', true], [$saved['nav'], $saved['logo_mode'], $saved['show_countries']], 'чужое значение — умолчание, запись без переключателя — включено');
});

test('Портал: подвал собирается из колонок конструктора', function (): void {
    ensure_test_db();
    \App\Core\FooterConfig::save(['columns' => [
        ['heading' => '', 'widget' => 'about', 'text' => '<p>Деловое издание</p>'],
        ['heading' => 'Связь', 'widget' => 'contacts', 'widget2' => 'subscribe'],
        ['heading' => 'Мой текст', 'widget' => 'text', 'text' => '<p>Свой блок</p>'],
    ], 'bottom' => '© {year} Тест', 'bottom_links' => [['label' => 'Правила', 'url' => '/rules']]]);
    \App\Models\Setting::set('contact_phone', '+998 71 200 00 00');
    $html = render_view('app/Views/portal/_bottom.php', []);
    assert_contains('pt-foot__grid--c3', $html, 'колонок столько, сколько в конструкторе');
    assert_contains('Деловое издание', $html);
    assert_contains('href="tel:+998712000000"', $html, 'контакты');
    assert_contains('class="pt-foot__subscribe"', $html, 'второй виджет колонки');
    assert_contains('Свой блок', $html);
    assert_contains('Правила', $html, 'ссылки нижней строки');
    assert_not_contains('>Рубрики<', $html, 'колонка, которой нет в конструкторе, не печатается');

    \App\Core\FooterConfig::save(['style' => 'minimal', 'columns' => [['heading' => '', 'widget' => 'about']]]);
    $html = render_view('app/Views/portal/_bottom.php', []);
    assert_not_contains('pt-foot__grid', $html, '«Минимальный» — только нижняя строка');
    assert_contains('pt-foot__bottom', $html);
    assert_same(['about', 'rubrics', 'countries', 'menu'], array_column(\App\Core\FooterConfig::DEFAULTS['columns'], 'widget'), 'набор по умолчанию — как в прототипе');
});

test('Портал: главное, лента и партнёрские материалы — отдельные секции', function (): void {
    $cards = Portal::cards(sample_news_rows(10), 'ru');
    $sec = ['lead' => $cards[0], 'grid' => array_slice($cards, 1, 3), 'also' => [], 'feed' => array_slice($cards, 0, 5)];
    $feed = render_view('app/Views/portal/_home_feed.php', ['sec' => $sec, 'secTitle' => 'Свежее', 'secUid' => 'b7']);
    assert_contains('pt-feed-solo', $feed, 'лента своей обёрткой');
    assert_contains('Свежее', $feed, 'свой заголовок у отдельной ленты');
    assert_contains('id="pt-feed-title-b7"', $feed, 'id заголовка не совпадает у двух блоков');
    assert_same(5, substr_count($feed, 'pt-feed__stamp'), 'длина ленты — из данных секции');
    assert_not_contains('pt-pkg', $feed, 'в ленте нет главного');

    $main = render_view('app/Views/portal/_home_main.php', ['sec' => $sec, 'secTitle' => '']);
    assert_contains('pt-home-main', $main);
    assert_contains('pt-pkg', $main);
    assert_not_contains('pt-feed__list', $main, 'в главном нет ленты');

    $lead = render_view('app/Views/portal/_home_lead.php', ['sec' => $sec, 'secTitle' => '']);
    assert_contains('pt-home-top', $lead);
    assert_not_contains('pt-feed-solo', $lead, 'внутри общей секции у частей нет своих обёрток');

    $rows = sample_news_rows(2);
    $rows[0]['is_partner'] = 1;
    $partners = render_view('app/Views/portal/_home_partners.php', ['sec' => ['partners' => Portal::cards($rows, 'ru')], 'secTitle' => '']);
    assert_contains('Партнёрские материалы', $partners);
    assert_contains('pt-sponsored', $partners);
    assert_same('', trim(render_view('app/Views/portal/_home_partners.php', ['sec' => ['partners' => []], 'secTitle' => ''])), 'пустая секция не печатается');
    assert_true(array_diff(\App\Core\Portal\Portal::DEFAULT_SECTIONS, array_keys(\App\Core\Portal\Portal::SECTIONS)) === [], 'умолчание — из объявленных секций');
});

test('Портал: сохранение «Дизайна» не перекрашивает сайт, подложка логотипа — своя настройка', function (): void {
    ensure_test_db();
    reset_design_state();
    \App\Core\Database::pdo()->exec("DELETE FROM settings WHERE `key` IN ('color_primary','color_accent','design_custom_color_primary','design_custom_color_accent','design_logo_plate','design_palette')");
    Setting::forgetCache();
    $before = portal_home_html(['lead' => null, 'feed' => []]);
    assert_contains('--brand:' . \App\Core\DesignSettings::DEFAULT_ACCENT, $before, 'умолчание портала');

    // Форма присылает то, что показывает: цвета из customAppearance().
    $shown = \App\Core\DesignSettings::customAppearance();
    \App\Core\DesignSettings::save(['palette' => 'custom', 'container' => 'wide', 'color_primary' => $shown['color_primary'], 'color_accent' => $shown['color_accent']]);
    Setting::forgetCache();
    $after = portal_home_html(['lead' => null, 'feed' => []]);
    assert_contains('--brand:' . \App\Core\DesignSettings::DEFAULT_ACCENT . ';--brand-deep:' . \App\Core\DesignSettings::DEFAULT_PRIMARY, $after, 'сохранение ширины не трогает цвета');

    \App\Core\DesignSettings::save(['logo_plate' => '#123456']);
    Setting::forgetCache();
    assert_contains('--logo-plate:#123456', portal_home_html(['lead' => null, 'feed' => []]), 'свой цвет подложки');
    \App\Core\DesignSettings::save(['logo_plate' => '#123456', 'logo_plate_off' => '1']);
    Setting::forgetCache();
    assert_true(!str_contains(portal_home_html(['lead' => null, 'feed' => []]), '--logo-plate'), '«как основной цвет» — переменной нет');
});

test('Портал: котировки сырья — ручной ввод, изменение считается от прежнего значения', function (): void {
    ensure_test_db();
    \App\Core\Database::pdo()->exec("DELETE FROM settings WHERE `key` = 'market_quotes'");
    Setting::forgetCache();
    $mq = \App\Core\Portal\MarketQuotes::class;
    assert_same([], $mq::current('ru'), 'без цифр строк на сайте нет');

    $row = ['names' => ['ru' => 'Хлопок ICE', 'uz' => 'Пахта ICE'], 'unit' => '¢/lb', 'value' => '70'];
    $mq::save([$row, ['names' => ['ru' => ''], 'value' => '']], 'ICE', strtotime('2026-11-10 12:00'));
    Setting::forgetCache();
    $first = $mq::current('ru');
    assert_same(1, count($first), 'пустая строка отброшена');
    assert_same(null, $first[0]['change'], 'первое значение — без изменения');

    $mq::save([['value' => '71,75'] + $row], 'ICE', strtotime('2026-11-15 12:00'));
    Setting::forgetCache();
    $q = $mq::current('uz')[0];
    assert_same('Пахта ICE', $q['name'], 'название на языке страницы');
    assert_same('2026-11-15', $q['updated']);
    assert_true(abs((float) $q['change'] - 2.5) < 0.001, 'изменение от прежних 70');
    $mq::save([['value' => '71,75'] + $row], 'ICE', strtotime('2026-11-16 12:00'));
    Setting::forgetCache();
    assert_same('2026-11-15', $mq::current('en', 'ru')[0]['updated'], 'неизменная цифра не сдвигает дату');
    assert_same('Хлопок ICE', $mq::current('en', 'ru')[0]['name'], 'нет перевода — основной язык');

    $html = render_view('app/Views/portal/_home_rates.php', ['sec' => ['rates' => null, 'quotes' => $mq::current('ru'), 'quotes_source' => 'ICE'], 'secTitle' => '']);
    assert_contains('Хлопок ICE', $html);
    assert_contains("71,75", $html);
    assert_contains('pt-fx__chg--up', $html);
    assert_contains('+2,5', $html);
    assert_contains('Источник: ICE', $html);
    assert_same('', trim(render_view('app/Views/portal/_home_rates.php', ['sec' => ['rates' => null, 'quotes' => []], 'secTitle' => ''])), 'нечего показать — секции нет');
});

test('Курсы ЦБ без cron: обновление после ответа — по сроку копии и не чаще попытки', function (): void {
    ensure_test_db();
    \App\Core\Database::pdo()->exec("DELETE FROM settings WHERE `key` IN ('currency_rates','currency_rates_attempt')");
    Setting::forgetCache();
    $cr = \App\Core\CurrencyRates::class;
    $now = 1_800_000_000;
    assert_true($cr::isDue($now), 'копии нет — пора');

    Setting::set('currency_rates', json_encode(['date' => '15.11.2026', 'rates' => ['UZS' => 12000], 'fetched_at' => $now - 3600]));
    Setting::forgetCache();
    assert_true(!$cr::isDue($now), 'свежая копия — не пора');
    assert_same($now - 3600, $cr::fetchedAt());

    Setting::set('currency_rates', json_encode(['date' => '15.11.2026', 'rates' => ['UZS' => 12000], 'fetched_at' => $now - $cr::REFRESH_AFTER - 1]));
    Setting::set('currency_rates_attempt', (string) ($now - 600));
    Setting::forgetCache();
    assert_true(!$cr::isDue($now), 'недавняя попытка — ЦБ не дёргаем');
    Setting::set('currency_rates_attempt', (string) ($now - 7200));
    Setting::forgetCache();
    assert_true($cr::isDue($now), 'копия устарела, попытка давно — пора');

    // Из консоли (тесты, импорт) наружу не ходим: обновление после ответа — только веб.
    $cr::afterResponse();
    assert_same(false, (new \ReflectionProperty($cr, 'scheduled'))->getValue());
});

test('Погода в шапке портала: копия Open-Meteo, город из конструктора, без копии — ничего', function (): void {
    ensure_test_db();
    reset_design_state();
    \App\Core\Database::pdo()->exec("DELETE FROM settings WHERE `key` IN ('weather_current','weather_attempt','header_config')");
    Setting::forgetCache();
    $w = \App\Core\Weather::class;
    $now = 1_800_000_000;
    assert_same(null, $w::current('tashkent', $now), 'копии нет — погоды нет');
    assert_true($w::isDue($now));

    // Ответ на список координат — массив по городам в порядке CITIES.
    $rows = [];
    foreach (array_keys($w::CITIES) as $i => $key) {
        $rows[] = ['current' => ['temperature_2m' => $i === 0 ? 13.6 : -2.4, 'weather_code' => $i === 0 ? 0 : 71]];
    }
    $parsed = $w::parse((string) json_encode($rows));
    assert_same(['temp' => 13.6, 'code' => 0], $parsed['tashkent']);
    assert_same(71, $parsed['bishkek']['code']);
    assert_same(null, $w::parse('{"error":true}'), 'чужой ответ не принимается');
    assert_contains('latitude=41.3111%2C43.238', $w::requestUrl());

    Setting::set('weather_current', json_encode(['cities' => $parsed, 'fetched_at' => $now - 600]));
    Setting::forgetCache();
    assert_true(!$w::isDue($now), 'свежая копия — не пора');
    $t = $w::current('tashkent', $now);
    assert_same(['city' => 'Ташкент', 'temp' => 14, 'icon' => 'sun', 'label' => 'Ясно'], $t);
    assert_same('−2°', $w::format($w::current('bishkek', $now)['temp']));
    assert_same('snowflake', $w::current('bishkek', $now)['icon']);
    assert_same('Ташкент', $w::current('нет-такого', $now)['city'], 'незнакомый город — умолчание');
    assert_same(null, $w::current('tashkent', $now + 7 * 3600), 'устаревшая копия не показывается');

    $html = portal_home_html(['lead' => null, 'feed' => []]);
    if (str_contains($html, 'pt-countries')) {
        // Шапка печатается на текущем времени: подставим свежую копию.
        Setting::set('weather_current', json_encode(['cities' => $parsed, 'fetched_at' => time()]));
        Setting::forgetCache();
        $html = portal_home_html(['lead' => null, 'feed' => []]);
        assert_contains('pt-countries__weather', $html);
        assert_contains('+14°', $html);
        \App\Core\HeaderConfig::save(['portal' => ['show_weather' => false]]);
        Setting::forgetCache();
        assert_true(!str_contains(portal_home_html(['lead' => null, 'feed' => []]), 'pt-countries__weather'), 'переключатель выключает погоду');
    }
    assert_same('bishkek', \App\Core\HeaderConfig::normalize(['portal' => ['weather_city' => 'bishkek']])['portal']['weather_city']);
    assert_same('tashkent', \App\Core\HeaderConfig::normalize(['portal' => ['weather_city' => 'x']])['portal']['weather_city']);
});
