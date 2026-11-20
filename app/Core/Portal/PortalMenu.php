<?php

declare(strict_types=1);

namespace App\Core\Portal;

use App\Core\Icon;
use App\Core\Logger;
use App\Models\MenuItem;

/**
 * Основное меню в шапке портала: пункты верхнего уровня, их подпункты,
 * мега-меню («Колонок» у пункта), иконки и метки из раздела «Меню».
 *
 * Данные считает класс, шапка (`portal/_top.php`) их печатает. Пункт-
 * разделитель верхнего уровня в полосу не выводится (полоса сама делит пункты
 * чертой); внутри подменю разделитель — черта между группами подпунктов.
 */
final class PortalMenu
{
    /**
     * Цвета меток. Ключи — те же, что принимает админка
     * (`MenuItem::cleanBadgeColor`); тона темнее, чем у прежней темы, потому
     * что подпись на метке белая и обязана держать 4.5:1.
     */
    public const BADGE_COLORS = ['red', 'green', 'blue', 'orange', 'purple'];

    /**
     * @return list<array{title:string, url:string, current:bool, icon:string,
     *     iconOnly:bool, badge:?array{text:string, color:string, pos:string},
     *     cols:int, children:list<array<string, mixed>>}>
     */
    public static function items(string $lang, callable $isCurrent): array
    {
        try {
            $rows = MenuItem::activeForLang($lang);
        } catch (\Throwable $e) {
            Logger::swallowed('Шапка портала: меню не загрузилось', $e);

            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (!empty($row['is_divider']) || !empty($row['parent_id'])) {
                continue;
            }
            $item = self::entry($row, $lang, $isCurrent);
            $children = [];
            foreach ((array) ($row['children'] ?? []) as $child) {
                if ((int) ($child['is_active'] ?? 1) !== 1) {
                    continue;
                }
                if (!empty($child['is_divider'])) {
                    // Две черты подряд и черта по краям списка ничего не делят.
                    if ($children !== [] && empty($children[array_key_last($children)]['divider'])) {
                        $children[] = ['divider' => true];
                    }
                    continue;
                }
                $children[] = self::entry($child, $lang, $isCurrent);
            }
            if ($children !== [] && !empty($children[array_key_last($children)]['divider'])) {
                array_pop($children);
            }
            $item['children'] = $children;
            $item['cols'] = $children === [] ? 0 : MenuItem::megaColumns($row['mega_columns'] ?? 0);
            foreach ($children as $child) {
                if (!empty($child['current'])) {
                    $item['current'] = true;
                }
            }
            $out[] = $item;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{title:string, url:string, current:bool, icon:string,
     *     iconOnly:bool, badge:?array{text:string, color:string, pos:string},
     *     cols:int, children:list<array<string, mixed>>}
     */
    private static function entry(array $row, string $lang, callable $isCurrent): array
    {
        $url = MenuItem::resolveUrl($row, $lang);
        $icon = self::icon($row);
        $badgeText = MenuItem::cleanBadgeText($row['badge_text'] ?? null);

        return [
            'title' => (string) ($row['title'] ?? ''),
            'url' => $url,
            'current' => (bool) $isCurrent($url),
            'icon' => $icon,
            'iconOnly' => $icon !== '' && !empty($row['hide_title']),
            'badge' => $badgeText === null ? null : [
                'text' => $badgeText,
                'color' => MenuItem::cleanBadgeColor($row['badge_color'] ?? null),
                // «По центру» в админке — над пунктом; в полосе меню иначе не поставить.
                'pos' => MenuItem::cleanBadgePos($row['badge_pos'] ?? null),
            ],
            'cols' => 0,
            'children' => [],
        ];
    }

    /**
     * Иконка пункта: своя картинка (и светлый вариант для тёмной темы)
     * главнее значка каталога.
     *
     * @param array<string, mixed> $row
     */
    private static function icon(array $row): string
    {
        $image = MenuItem::cleanIconImage($row['icon_image'] ?? null);
        if ($image !== null) {
            $light = MenuItem::cleanIconImage($row['icon_image_light'] ?? null);
            $img = static fn (string $src, string $mod): string => '<img class="pt-nav__img' . $mod . '" src="'
                . htmlspecialchars($src, ENT_QUOTES) . '" alt="" height="18" decoding="async">';

            return '<span class="pt-nav__icon" aria-hidden="true">' . $img($image, $light !== null ? ' pt-nav__img--std' : '')
                . ($light !== null ? $img($light, ' pt-nav__img--light') : '') . '</span>';
        }
        $name = Icon::cleanName($row['icon_svg'] ?? '');

        return $name === '' ? '' : '<span class="pt-nav__icon" aria-hidden="true">' . Icon::render($name, 18) . '</span>';
    }

    /**
     * Подпись пункта: метка, иконка и название (при «только иконка» название
     * остаётся для диктора).
     *
     * @param array<string, mixed> $item запись из items() или её подпункт
     */
    public static function label(array $item): string
    {
        $e = static fn (mixed $s): string => htmlspecialchars(is_scalar($s) ? (string) $s : '', ENT_QUOTES);
        $badge = is_array($item['badge'] ?? null) ? $item['badge'] : [];
        $badgePos = (string) ($badge['pos'] ?? '');
        $badgeHtml = $badge === [] ? '' : '<span class="pt-badge pt-badge--' . $e($badge['color'] ?? 'red')
            . ' pt-badge--' . $e($badgePos) . '">' . $e($badge['text'] ?? '') . '</span>';
        $title = !empty($item['iconOnly'])
            ? '<span class="pt-visually-hidden">' . $e($item['title'] ?? '') . '</span>'
            : '<span>' . $e($item['title'] ?? '') . '</span>';
        $icon = is_string($item['icon'] ?? null) ? $item['icon'] : '';

        return $badgePos === 'left'
            ? $badgeHtml . $icon . $title
            : $icon . $title . $badgeHtml;
    }
}
