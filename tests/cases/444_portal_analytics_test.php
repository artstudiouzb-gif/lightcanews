<?php

declare(strict_types=1);

use App\Models\Setting;

// Счётчики и согласие на cookie выводит общий фрагмент site/_analytics.php —
// его подключают и прежний подвал, и подвал портала. Портал прежде не
// подключал ничего, и номер Метрики из настроек на сайт не доходил.
// Push-уведомления, замер скорости и «Свой JS» страницы — во фрагменте
// site/_tail.php, его тоже подключают оба шаблона.

test('Счётчики: фрагмент аналитики выводит Метрику, согласие и consent.js отдельным файлом (БД)', function (): void {
    ensure_test_db();
    Setting::set('analytics_ym_id', '12345678');
    Setting::set('custom_js_global', '');

    $html = render_view('app/Views/site/_analytics.php', ['page' => []]);

    assert_contains('id="analytics-init"', $html);
    assert_contains('12345678', $html, 'номер счётчика из настроек попадает в код');
    assert_contains('type="application/json" id="consent-config"', $html, 'настройки согласия — данными, а не исполняемым скриптом');
    assert_true(preg_match('#<script src="[^"]*/assets/js/consent\.js[^"]*" defer></script>#', $html) === 1, 'consent.js подключается файлом с defer');
});

test('Счётчики: без номера счётчика фрагмент ничего не выводит (БД)', function (): void {
    ensure_test_db();
    Setting::set('analytics_ym_id', '');
    Setting::set('analytics_ga_id', '');
    Setting::set('custom_js_global', '');

    assert_same('', trim(render_view('app/Views/site/_analytics.php', ['page' => []])));
});

test('Конец страницы: push-уведомления отдают настройки данными, а не исполняемым скриптом (БД)', function (): void {
    ensure_test_db();
    Setting::set('webpush_enabled', '1');
    Setting::set('analytics_ym_id', '');
    Setting::set('analytics_ga_id', '');
    Setting::set('custom_js_global', '');

    $html = render_view('app/Views/site/_tail.php', ['page' => []]);

    assert_contains('type="application/json" id="push-config"', $html);
    assert_true(preg_match('#<script src="[^"]*/assets/js/push\.js[^"]*" defer></script>#', $html) === 1, 'push.js подключается файлом');
});

test('Меню портала: подпункты, разделители между ними, колонки мега-меню и метка (БД)', function (): void {
    ensure_test_db();
    \App\Core\Database::pdo()->exec("DELETE FROM menu_items WHERE lang = 'ru'");
    $base = ['lang' => 'ru', 'url_type' => 'custom', 'is_active' => 1];
    $parent = \App\Models\MenuItem::create($base + ['title' => 'Отрасли', 'url_value' => '/otrasli']);
    \App\Models\MenuItem::create($base + ['title' => '—', 'url_value' => '#', 'parent_id' => $parent, 'is_divider' => 1]);
    \App\Models\MenuItem::create($base + ['title' => 'Хлопок', 'url_value' => '/hlopok', 'parent_id' => $parent]);
    \App\Models\MenuItem::create($base + ['title' => '—', 'url_value' => '#', 'parent_id' => $parent, 'is_divider' => 1]);
    \App\Models\MenuItem::create($base + ['title' => '—', 'url_value' => '#', 'parent_id' => $parent, 'is_divider' => 1]);
    \App\Models\MenuItem::create($base + ['title' => 'Текстиль', 'url_value' => '/tekstil', 'parent_id' => $parent]);
    \App\Models\MenuItem::create($base + ['title' => '—', 'url_value' => '#', 'parent_id' => $parent, 'is_divider' => 1]);
    \App\Models\MenuItem::create($base + ['title' => 'Аналитика', 'url_value' => '/analitika', 'mega_columns' => 3,
        'badge_text' => 'Новое', 'badge_color' => 'green', 'badge_pos' => 'left']);
    \App\Models\MenuItem::create($base + ['title' => '—', 'url_value' => '#', 'is_divider' => 1]);

    $items = \App\Core\Portal\PortalMenu::items('ru', static fn (string $url): bool => $url === '/tekstil');

    assert_same(['Отрасли', 'Аналитика'], array_column($items, 'title'), 'разделитель верхнего уровня в полосу не идёт');
    $kids = $items[0]['children'];
    assert_same(3, count($kids), 'черта по краям списка и вторая подряд отброшены: пункт, черта, пункт');
    assert_true(!empty($kids[1]['divider']), 'между подпунктами — разделитель');
    assert_true($items[0]['current'], 'текущий подпункт подсвечивает родителя');
    assert_same(0, $items[1]['cols'], 'без подпунктов мега-меню нет');

    $label = \App\Core\Portal\PortalMenu::label($items[1]);
    assert_true(str_starts_with($label, '<span class="pt-badge pt-badge--green pt-badge--left">Новое</span>'), 'метка «слева» стоит перед названием');
});
