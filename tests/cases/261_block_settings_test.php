<?php

declare(strict_types=1);

use App\Core\BlockTypeRegistry;

// Настройки блоков: у каждой должен быть потребитель на выводе, поле в форме и
// ветка сохранения. Иначе настройка «есть», редактор её переключает, а на сайте
// ничего не меняется — так уже случалось с фирменным знаком карточек актов.

test('Каждое поле блока читается на выводе', function () {
    $renderer = (string) file_get_contents(APP_ROOT . '/app/Core/BlockRenderer.php');
    // Настройка бывает потребляемой не на выводе, а при сохранении: «Тип
    // сборки» коллажа сам считает сетку и места элементов, и шаблон получает
    // уже посчитанные номера ячеек — спрашивать раскладку ему нечего.
    // Нормализатор здесь такой же законный потребитель, как шаблон: если
    // ключа нет и там, настройка не делает ничего нигде, а это ровно тот
    // дефект, ради которого тест написан.
    $normalizers = '';
    foreach (glob(APP_ROOT . '/app/Core/BlockData/*Normalizer.php') ?: [] as $file) {
        $normalizers .= (string) file_get_contents($file);
    }
    $orphans = [];

    foreach (BlockTypeRegistry::defaults() as $type => $defaults) {
        $tplPath = APP_ROOT . '/templates/blocks/' . $type . '.php';
        $tpl = is_file($tplPath) ? (string) file_get_contents($tplPath) : '';
        // Партиалы карточек лежат рядом: шаблон их подключает, поля читают они.
        foreach (glob(APP_ROOT . '/templates/blocks/partials/*.php') ?: [] as $partial) {
            $tpl .= (string) file_get_contents($partial);
        }
        foreach (array_keys($defaults) as $key) {
            $used = str_contains($tpl, "'" . $key . "'")
                || str_contains($tpl, '"' . $key . '"')
                || str_contains($renderer, "'" . $key . "'")
                || str_contains($normalizers, "'" . $key . "'");
            if (!$used) {
                $orphans[] = $type . '.' . $key;
            }
        }
    }

    assert_same([], $orphans, 'настройки без потребителя: ' . implode(', ', $orphans));
});

test('Поля, зависящие от варианта, помечены для формы', function () {
    $editor = block_editor_markup();
    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/admin.js');

    assert_contains('data-field-when="variant"', $editor, 'неприменимые поля помечены');
    assert_contains('data-field-when', $js, 'разметку обрабатывает скрипт админки');
    // Без JS поле остаётся видимым: скрытие — подсказка, а не условие сохранения.
    assert_not_contains('data-field-when-required', $editor);
});
