<?php

declare(strict_types=1);

use App\Models\Setting;

// Счётчики и согласие на cookie выводит общий фрагмент site/_analytics.php —
// его подключают и прежний подвал, и подвал портала. Портал прежде не
// подключал ничего, и номер Метрики из настроек на сайт не доходил.

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
