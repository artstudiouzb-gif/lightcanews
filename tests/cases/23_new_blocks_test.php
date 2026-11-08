<?php

declare(strict_types=1);

use App\Core\BlockRenderer;
use App\Core\Database;

/** Хелпер: рендер одиночного блока по типу и данным (без БД). */
function render_block(string $type, array $data): string
{
    $out = BlockRenderer::render([
        'id' => 1,
        'type' => $type,
        'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
        'custom_css' => null,
    ]);

    return $out['html'];
}

test('Блок testimonials рендерит цитаты и авторов (группа 4)', function () {
    $html = render_block('testimonials', [
        'title' => 'Отзывы',
        'items' => [
            ['quote' => 'Отличная работа', 'name' => 'Иван', 'company' => 'ООО Ромашка', 'photo' => ''],
        ],
    ]);
    assert_contains('block-testimonials', $html);
    assert_contains('Отличная работа', $html);
    assert_contains('Иван', $html);
    assert_contains('ООО Ромашка', $html);
});

test('Блок counters рендерит числа с data-counter-target (группа 4)', function () {
    $html = render_block('counters', [
        'title' => 'Цифры',
        'items' => [['value' => 500, 'suffix' => '+', 'label' => 'клиентов']],
    ]);
    assert_contains('data-counter-target="500"', $html);
    assert_contains('клиентов', $html);
    assert_contains('counter__suffix', $html);
});

