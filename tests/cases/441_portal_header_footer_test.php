<?php

declare(strict_types=1);

use App\Core\CurrencyRates;
use App\Core\FooterConfig;
use App\Core\HeaderConfig;
use App\Models\NewsCategory;
use App\Models\Setting;

test('Курсы валют: курс соседей к доллару пересчитывается через сум', function (): void {
    // Формат ответа ЦБ: курс в сумах за Nominal единиц валюты.
    $json = json_encode([
        ['Ccy' => 'USD', 'Rate' => '12000.00', 'Nominal' => '1', 'Date' => '29.10.2026'],
        ['Ccy' => 'KZT', 'Rate' => '24.00', 'Nominal' => '1'],
        ['Ccy' => 'KGS', 'Rate' => '1380.00', 'Nominal' => '10'],
        ['Ccy' => 'RUB', 'Rate' => '150.00', 'Nominal' => '1'],
    ], JSON_THROW_ON_ERROR);
    $parsed = CurrencyRates::parse($json);
    assert_true($parsed !== null, 'ответ ЦБ разобран');
    assert_same('29.10.2026', $parsed['date']);
    assert_same(12000.0, $parsed['rates']['UZS'], 'сумов за доллар');
    assert_same(500.0, $parsed['rates']['KZT'], 'тенге за доллар: 12000 / 24');
    assert_same(86.96, $parsed['rates']['KGS'], 'номинал учитывается: 12000 / 138');
    assert_false(isset($parsed['rates']['RUB']), 'на плашку идут только валюты региона');
    assert_true(CurrencyRates::parse('[{"Ccy":"KZT","Rate":"24"}]') === null, 'без доллара пересчитать не через что');
    assert_true(CurrencyRates::parse('не json') === null);
});

test('Курсы валют: шапка показывает только свежую копию (БД)', function (): void {
    ensure_test_db();
    reset_design_state();

    Setting::set('currency_rates', json_encode(['date' => '29.10.2026', 'rates' => ['UZS' => 11965.51, 'KZT' => 448.15], 'fetched_at' => time()], JSON_THROW_ON_ERROR));
    $html = CurrencyRates::html();
    assert_contains('hdr-rates', $html);
    assert_contains("11\u{00A0}965,51", $html, 'разряды через неразрывный пробел, дробь через запятую');
    assert_contains('data-no-translit', $html, 'коды валют не уходят в кириллицу');

    Setting::set('currency_rates', json_encode(['rates' => ['UZS' => 1.0], 'fetched_at' => time() - 30 * 86400], JSON_THROW_ON_ERROR));
    assert_same('', CurrencyRates::html(), 'устаревший курс не показывается вовсе');
    Setting::set('currency_rates', '');
});

test('Шапка и подвал: элементы разделов портала объявлены (БД)', function (): void {
    ensure_test_db();

    assert_true(isset(HeaderConfig::ELEMENTS['countries']), 'элемент «Страны» есть в конструкторе шапки');
    assert_true(isset(HeaderConfig::ELEMENTS['rates']), 'элемент «Курсы валют» есть в конструкторе шапки');
    assert_true(isset(FooterConfig::WIDGETS['rubrics']) && isset(FooterConfig::WIDGETS['countries']), 'виджеты разделов есть в подвале');

    // Конструктор пропускает элементы и виджеты, только если они объявлены.
    $header = HeaderConfig::normalize(['topbar' => ['enabled' => true, 'zones' => ['left' => ['countries'], 'right' => ['rates']]]]);
    assert_same(['countries'], $header['topbar']['zones']['left']);
    assert_same(['rates'], $header['topbar']['zones']['right']);
    $footer = FooterConfig::normalize(['columns' => [['heading' => 'Рубрики', 'widget' => 'rubrics'], ['heading' => 'Страны', 'widget' => 'countries']]]);
    assert_same('rubrics', $footer['columns'][0]['widget']);
    assert_same('countries', $footer['columns'][1]['widget']);

    assert_true(NewsCategory::all(null, true, NewsCategory::AXIS_COUNTRY) !== [], 'стран для чипов хватает');
});
