<?php

declare(strict_types=1);

test('Конструктор: переключатель языка блоков есть у страницы и не мешает проекту', function (): void {
    $editor = (string) file_get_contents(APP_ROOT . '/app/Views/admin/pages/_block_editor.php');

    // У страницы стек блоков свой на каждый язык — до него нужна кнопка, иначе
    // второй язык доступен только правкой адреса.
    assert_contains('block-lang-switch', $editor);
    // Адрес кнопки собирает BlockOwner: он же решает, в чей раздел вернуть
    // редактора. Литерал `block_lang=` здесь проверять больше нечего — он
    // переехал в помощник вместе с самим адресом (тест 374).
    assert_contains('BlockOwner::editUrlFor($page, $code)', $editor);
    // У проекта языковая версия это отдельная запись, её переключает сайдбар:
    // второй переключатель на той же форме путал бы.
    assert_contains("entity_type'] ?? 'page') === 'page'", $editor);
});
