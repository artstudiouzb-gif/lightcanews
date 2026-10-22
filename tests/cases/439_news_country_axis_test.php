<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\News;
use App\Models\NewsCategory;

test('Разделы портала: страна — вторая ось, общая для группы перевода', function (): void {
    ensure_test_db();

    $pdo = Database::pdo();
    $suffix = bin2hex(random_bytes(4));
    $rubric = (int) NewsCategory::create('Рубрика ' . $suffix, 'rubric-' . $suffix);
    $countryA = (int) NewsCategory::create('Страна A ' . $suffix, 'country-a-' . $suffix, axis: NewsCategory::AXIS_COUNTRY);
    $countryB = (int) NewsCategory::create('Страна B ' . $suffix, 'country-b-' . $suffix, axis: NewsCategory::AXIS_COUNTRY);

    $insert = $pdo->prepare(
        "INSERT INTO news (title, slug, category_id, excerpt, content, status, published_at, lang, translation_group_id, created_at)
         VALUES (:title, :slug, :cat, '', '<p>x</p>', 'published', NOW() - INTERVAL 1 MINUTE, :lang, :grp, NOW())"
    );
    $insert->execute([':title' => 'RU ' . $suffix, ':slug' => 'axis-' . $suffix, ':cat' => $rubric, ':lang' => 'ru', ':grp' => null]);
    $ruId = (int) $pdo->lastInsertId();
    $insert->execute([':title' => 'UZ ' . $suffix, ':slug' => 'axis-' . $suffix, ':cat' => $rubric, ':lang' => 'uz', ':grp' => $ruId]);
    $uzId = (int) $pdo->lastInsertId();

    $uzRow = News::findById($uzId);
    assert_true($uzRow !== null && News::groupKeyOf($uzRow) === $ruId, 'ключ группы узбекской версии — русская запись');

    // Рубрика в таблице стран дала бы новости вторую рубрику мимо формы.
    NewsCategory::setCountries(News::groupKeyOf($uzRow), [$countryA, $rubric]);
    assert_same([$ruId => [$countryA]], NewsCategory::countryIdsFor([$ruId]), 'рубрика в страны не попадает');

    $ids = static fn (array $rows): array => array_map(static fn (array $r): int => (int) $r['id'], $rows);
    assert_true(in_array($ruId, $ids(News::published(50, 0, 'ru', $countryA)), true), 'русская лента страны содержит новость');
    assert_true(in_array($uzId, $ids(News::published(50, 0, 'uz', $countryA)), true), 'страна общая для группы: узбекская версия тоже в ленте');
    assert_true(!in_array($ruId, $ids(News::published(50, 0, 'ru', $countryB)), true), 'чужая страна новость не показывает');
    assert_true(in_array($ruId, $ids(News::published(50, 0, 'ru', $rubric)), true), 'рубрика фильтруется по category_id');
    assert_same(1, News::publishedCount($countryA, 'ru'), 'счётчик ленты страны');

    $countryIds = array_map(static fn (array $c): int => (int) $c['id'], News::publishedCategories('ru', NewsCategory::AXIS_COUNTRY));
    assert_true(in_array($countryA, $countryIds, true) && !in_array($countryB, $countryIds, true), 'в навигацию идут страны с новостями');
    $rubricIds = array_map(static fn (array $c): int => (int) $c['id'], News::publishedCategories('ru'));
    assert_true(in_array($rubric, $rubricIds, true) && !in_array($countryA, $rubricIds, true), 'рубрикатор не показывает страны');

    $country = NewsCategory::find($countryA);
    assert_same('country/country-a-' . $suffix, NewsCategory::path((array) $country), 'адрес страны');
    assert_same('rubric/rubric-' . $suffix, NewsCategory::path((array) NewsCategory::find($rubric)), 'адрес рубрики');
    assert_true(NewsCategory::findBySlug('country-a-' . $suffix, true, NewsCategory::AXIS_RUBRIC) === null, 'страна не открывается адресом рубрики');
    assert_true(\App\Models\ContentType::rootUrlConflict('country') !== '', 'корневой адрес /country занят разделом');
});

test('Разделы портала: стандартные рубрики и страны заведены схемой', function (): void {
    ensure_test_db();

    $slugs = static fn (string $axis): array => array_map(
        static fn (array $c): string => (string) $c['slug'],
        NewsCategory::all(null, false, $axis)
    );
    foreach (['news', 'industries', 'analytics', 'technology', 'events', 'marketplaces', 'opinion'] as $slug) {
        assert_true(in_array($slug, $slugs(NewsCategory::AXIS_RUBRIC), true), 'рубрика ' . $slug);
    }
    foreach (['world', 'uzbekistan', 'kazakhstan', 'kyrgyzstan', 'tajikistan', 'turkmenistan'] as $slug) {
        assert_true(in_array($slug, $slugs(NewsCategory::AXIS_COUNTRY), true), 'страна ' . $slug);
    }
});
