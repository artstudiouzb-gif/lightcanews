<?php

declare(strict_types=1);

use App\Core\BlockOwner;

/*
 * Возврат из конструктора — в форму владельца блока. Адрес собирает
 * `BlockOwner`, а не литерал по месту: им пользуются кнопка «Назад к
 * странице», переключатель языка блоков и редиректы после копирования блоков
 * и применения шаблона.
 */

test('Адрес возврата ведёт в форму страницы', function () {
    assert_same('/admin/pages/7/edit', BlockOwner::editUrlFor(['id' => 7, 'entity_type' => 'page']));
    assert_same('/admin/pages/7/edit?block_lang=uz', BlockOwner::editUrlFor(['id' => 7], 'uz'));
});

test('Конструктор и его редиректы не пишут раздел литералом', function () {
    $files = [
        'app/Views/admin/pages/block_form.php',
        'app/Views/admin/pages/_block_editor.php',
        'app/Controllers/Admin/BlockController.php',
        'app/Controllers/Admin/SnippetController.php',
    ];

    foreach ($files as $file) {
        $src = (string) file_get_contents(APP_ROOT . '/' . $file);
        // POST-адреса конструктора общие — они работают с блоками по id
        // записи. Запрещён именно адрес формы: он уводит проект на 404.
        assert_false(
            (bool) preg_match('~/admin/pages/[^\'"]*\{?\$?[^\'"]*/edit~', $src),
            $file . ': адрес формы владельца пишется через BlockOwner'
        );
    }

    $pageController = (string) file_get_contents(APP_ROOT . '/app/Controllers/Admin/PageController.php');
    // Копирование блоков другого языка возвращает туда же, откуда пришли.
    assert_contains('BlockOwner::editUrlFor($owner', $pageController, 'копирование блоков: возврат через BlockOwner');

    $blockController = (string) file_get_contents(APP_ROOT . '/app/Controllers/Admin/BlockController.php');
    assert_contains(
        "header('Location: /admin/blocks/' . (int) \$block['id'] . '/edit?draft_saved=block%3A'",
        $blockController,
        'после сохранения редактор должен остаться в форме текущего блока'
    );
    assert_not_contains(
        "pageEditUrl(\$block) . '&draft_saved=block%3A'",
        $blockController,
        'сохранение не должно автоматически возвращать к странице или проекту'
    );
});
