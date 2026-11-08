<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\MenuItem;
use App\Models\Page;

/*
 * Адрес цели пункта меню собирает `Page::menuTargetValue()`: ссылка в шапке и
 * синхронизация меню между языками раньше склеивали его по месту и
 * расходились. Второй склейки `'/' . $slug` для пунктов меню быть не должно.
 */

test('Адрес цели пункта меню собирается одним методом', function (): void {
    // Вторая склейка «слэш плюс слаг» — это и есть та копия, которая молча
    // расходится: адрес цели собирается в menuTargetValue().
    $menu = (string) file_get_contents(APP_ROOT . '/app/Models/MenuItem.php');

    assert_not_contains(
        "'/' . (string) \$page['slug']",
        $menu,
        'адрес цели собирает Page::menuTargetValue(), а не голый слаг'
    );
    assert_not_contains(
        "\$source['url_value'] = (string) \$page['slug'];",
        $menu,
        'синхронизация языков тоже сохраняет полный адрес цели'
    );
    assert_contains('Page::menuTargetValue($page)', $menu);
});

