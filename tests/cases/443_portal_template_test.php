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
