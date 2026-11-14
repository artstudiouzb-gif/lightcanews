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

test('Портал: код страны — по slug, для незнакомой страны — из названия', function (): void {
    assert_same('UZ', Portal::countryCode(['slug' => 'uzbekistan', 'name' => 'Узбекистан']));
    assert_same('МИР', Portal::countryCode(['slug' => 'world', 'name' => 'В мире']));
    assert_same('МО', Portal::countryCode(['slug' => 'mongolia', 'name' => 'Монголия']));
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
