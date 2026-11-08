<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\ContentEntry;
use App\Models\ContentType;

test('Документы и вакансии: список, поиск, сортировка и счётчик используют текущий язык', function () {
    ensure_test_db();
    $pdo = Database::pdo();
    $suffix = bin2hex(random_bytes(4));
    $typeId = ContentType::create('strict-catalog-' . $suffix, 'Строгий каталог', true);
    ContentType::replaceFields($typeId, [[
        'name' => 'department',
        'label' => 'Отдел',
        'field_type' => 'text',
        'required' => false,
        'options' => [],
    ]]);

    $first = ContentEntry::create(
        $typeId,
        'Zeta RU',
        'zeta-' . $suffix,
        'published',
        ['department' => 'Русский отдел']
    );
    $second = ContentEntry::create(
        $typeId,
        'Alpha RU',
        'alpha-' . $suffix,
        'published',
        ['department' => 'Русский сектор']
    );
    $withoutTranslation = ContentEntry::create(
        $typeId,
        'Only RU',
        'only-ru-' . $suffix,
        'published',
        ['department' => 'Только русский']
    );
    ContentEntry::upsertTranslation($first, 'uz', 'Alfa UZ', ['department' => 'Raqamli bo‘lim']);
    ContentEntry::upsertTranslation($second, 'uz', 'Zebra UZ', ['department' => 'Moliya bo‘limi']);

    assert_same(3, ContentEntry::countTypePublic($typeId, '', 'ru'));
    assert_same(2, ContentEntry::countTypePublic($typeId, '', 'uz'));
    assert_same(1, ContentEntry::countTypePublic($typeId, 'Raqamli', 'uz'));

    $uzRows = ContentEntry::forTypePublic($typeId, '', 'title', 12, 0, 'uz');
    assert_same([$first, $second], array_map(static fn (array $row): int => (int) $row['id'], $uzRows));
    assert_same('Alfa UZ', $uzRows[0]['title']);
    $firstData = json_decode((string) $uzRows[0]['data'], true);
    assert_same('Raqamli bo‘lim', $firstData['department'] ?? null);
    assert_true(
        !in_array($withoutTranslation, array_column($uzRows, 'id'), false),
        'запись без перевода не попадает в UZ-каталог'
    );

    $searchRows = ContentEntry::forTypePublic($typeId, 'Moliya', 'new', 12, 0, 'uz');
    assert_same([$second], array_map(static fn (array $row): int => (int) $row['id'], $searchRows));
    assert_same(['ru', 'uz'], ContentEntry::availableLangs($first));
    assert_same(['ru'], ContentEntry::availableLangs($withoutTranslation));

    $pdo->prepare('DELETE FROM content_types WHERE id = ?')->execute([$typeId]);
});

test('Публичные шаблоны каталога переводят названия типов и подписи полей', function () {
    $root = dirname(__DIR__, 2);
    $index = (string) file_get_contents($root . '/app/Views/site/content_index.php');
    $list = (string) file_get_contents($root . '/app/Views/site/_catalog_list.php');
    $show = (string) file_get_contents($root . '/app/Views/site/content_show.php');

    assert_contains("t((string) \$type['name'])", $index);
    assert_contains("t((string) \$type['description'])", $index);
    assert_contains("t((string) \$f['label'])", $list);
    assert_contains("t((string) \$f['label'])", $show);
});
