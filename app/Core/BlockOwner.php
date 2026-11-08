<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Page;

/**
 * Куда возвращаться после работы с блоком — в форму его владельца.
 *
 * Ссылка «назад к странице», переключатель языка блоков и редиректы после
 * копирования блоков или применения шаблона собирают адрес здесь, а не
 * литералом по месту: адрес формы владельца объявлен один раз.
 */
final class BlockOwner
{
    /**
     * Адрес записи в её разделе админки, без завершающего слэша.
     *
     * @param array<string, mixed> $page строка `pages`
     */
    public static function baseFor(array $page): string
    {
        return '/admin/pages/' . (int) ($page['id'] ?? 0);
    }

    /**
     * Адрес формы владельца; язык блоков необязателен.
     *
     * @param array<string, mixed> $page строка `pages`
     */
    public static function editUrlFor(array $page, string $lang = ''): string
    {
        return self::withLang(self::baseFor($page), $lang);
    }

    /**
     * То же по одному id — для мест, где строки под рукой нет (форма блока
     * знает только `page_id`). Неизвестная запись остаётся в разделе страниц:
     * прежнее поведение, и 404 там честнее выдумывания раздела.
     */
    public static function editUrl(int $pageId, string $lang = ''): string
    {
        $page = Page::findById($pageId) ?? [];
        $page['id'] = $pageId;

        return self::withLang(self::baseFor($page), $lang);
    }

    private static function withLang(string $base, string $lang): string
    {
        return $base . '/edit' . ($lang !== '' ? '?block_lang=' . urlencode($lang) : '');
    }
}
