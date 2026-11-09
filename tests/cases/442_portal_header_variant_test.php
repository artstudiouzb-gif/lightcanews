<?php

declare(strict_types=1);

use App\Core\HeaderConfig;
use App\Models\Setting;

/*
 * Шапка «Портал»: плашка логотипа на кромке, рубрики и страны из категорий.
 * Вид выбирается в конструкторе шапки и по умолчанию включён; прежний
 * конструктор с зонами остаётся видом «Классический».
 */

test('Шапка: вид «Портал» по умолчанию, чужое значение не проходит', function (): void {
    assert_same('portal', HeaderConfig::normalize([])['variant']);
    assert_same('classic', HeaderConfig::normalize(['variant' => 'classic'])['variant']);
    assert_same('portal', HeaderConfig::normalize(['variant' => 'floating'])['variant'], 'вид из закрытого списка');

    $portal = HeaderConfig::normalize(['portal' => [
        'tagline' => '<b>Новости</b> отрасли',
        'live_text' => 'Прямой эфир',
        'live_url' => 'javascript:alert(1)',
    ]])['portal'];
    assert_same('Новости отрасли', $portal['tagline'], 'разметка в подписи не допускается');
    assert_same('Прямой эфир', $portal['live_text']);
    assert_same('', $portal['live_url'], 'опасная ссылка кнопки отбрасывается');
    assert_same('/news/online', HeaderConfig::normalize(['portal' => ['live_url' => '/news/online']])['portal']['live_url']);
});

test('Шапка «Портал»: плашка, рубрики из категорий, страны с кодами, кнопка «Онлайн» (БД)', function (): void {
    ensure_test_db();
    reset_design_state();
    Setting::set('pwa_short_name', 'LightCA');

    $render = static function (array $portal, string $drawer): string {
        return render_view('app/Views/site/_header_portal.php', [
            'hcfg' => HeaderConfig::normalize(['portal' => $portal, 'sticky' => true]),
            'menuItems' => [],
            'countryRows' => [
                ['slug' => 'kazakhstan', 'axis' => 'country', 'name' => 'Казахстан'],
                ['slug' => 'world', 'axis' => 'country', 'name' => 'В мире'],
            ],
            'currentLang' => 'ru',
            'currentReqUri' => '/',
            'logo' => '',
            'siteName' => 'Новостной портал лёгкой промышленности',
            'langHtml' => '',
            'overlaySearchHtml' => '<button type="button" class="site-search-toggle" data-search-toggle></button>',
            'themeToggle' => '',
            'drawerMenu' => $drawer,
            'isNavUrlActive' => static fn (string $url): bool => false,
        ]);
    };

    $html = $render(['live_url' => '/news/online'], '<nav class="site-menu"></nav>');
    assert_contains('class="phdr phdr--sticky"', $html);
    assert_contains('<span class="phdr__logo-word">LightCA</span>', $html, 'в плашке короткое имя, а не полное название');
    assert_contains('Новости лёгкой промышленности Центральной Азии', $html, 'подпись по умолчанию из словаря');
    assert_contains('class="phdr__nav"', $html);
    assert_contains('Отрасли', $html, 'рубрики берутся из категорий');
    assert_contains('>KZ</span>', $html, 'у страны код на ярлыке');
    assert_contains('class="phdr__live" href="/news/online"', $html);
    assert_contains('data-mobile-menu-toggle', $html, 'основное меню — в выдвижной панели');

    $bare = $render([], '');
    assert_not_contains('phdr__live', $bare, 'без адреса кнопки «Онлайн» нет');
    assert_not_contains('data-mobile-menu-toggle', $bare, 'без меню нет и кнопки меню');
});
