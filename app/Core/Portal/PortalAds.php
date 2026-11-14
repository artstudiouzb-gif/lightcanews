<?php

declare(strict_types=1);

namespace App\Core\Portal;

use App\Core\UrlGuard;
use App\Models\Setting;

/**
 * Рекламные зоны шаблона портала. Мест ровно пять, и они объявлены здесь
 * одним списком: форма админки рисует поля по нему, вьюхи спрашивают зону по
 * ключу. Хранятся одной настройкой JSON — записей пять, индексировать нечего.
 *
 * Выключенная или недозаполненная зона не печатается вовсе: пустая рамка с
 * подписью «Реклама» читается как поломка страницы. Ссылка рекламы уходит с
 * rel="sponsored" — так её требуют помечать поисковики.
 */
final class PortalAds
{
    public const SETTING = 'portal_ads';

    /** Типы страниц, на которых бывает реклама. */
    public const PLACES = [
        'home' => 'Главная',
        'news_index' => 'Ленты новостей (все новости, рубрики, страны)',
        'news_show' => 'Страница новости',
        'other' => 'Остальные страницы (поиск, альбомы, страницы)',
    ];

    /**
     * Ключ → подпись в админке, размер-ориентир, подпись над зоной на сайте,
     * нужна ли картинка, где зона вообще может стоять (`places`: тип
     * страницы → место на ней) и где включена по умолчанию.
     *
     * Места заданы шаблоном, а не редактором: «в ленте после третьей новости»
     * существует только на главной, колонка 300×250 — только у статьи.
     * Редактор выбирает, на каких из возможных страниц зона показывается.
     *
     * @var array<string, array{label: string, size: string, mark: string, image: bool, places: array<string, string>, default: list<string>}>
     */
    public const ZONES = [
        'top' => [
            'label' => 'Растяжка под шапкой', 'size' => '1440 × 90', 'mark' => 'Реклама', 'image' => false,
            'places' => ['home' => 'под шапкой', 'news_index' => 'под шапкой', 'news_show' => 'под шапкой', 'other' => 'под шапкой'],
            'default' => ['home', 'news_index', 'news_show', 'other'],
        ],
        'feed' => [
            'label' => 'Объявление в ленте новостей', 'size' => '96 × 64 + строка', 'mark' => 'Реклама', 'image' => true,
            'places' => ['home' => 'в боковой ленте, после третьей новости'],
            'default' => ['home'],
        ],
        'partner' => [
            'label' => 'Партнёрский материал', 'size' => '16:9 + текст', 'mark' => 'Партнёрский материал', 'image' => true,
            'places' => ['home' => 'под боковой лентой', 'news_show' => 'внизу правой колонки'],
            'default' => ['home'],
        ],
        'mid' => [
            'label' => 'Широкий партнёрский баннер', 'size' => '1200 × 108', 'mark' => 'Партнёрский блок', 'image' => false,
            'places' => ['home' => 'между «Выбором редакции» и «Нитями региона»', 'news_index' => 'между первой новостью и сеткой', 'news_show' => 'после текста статьи'],
            'default' => ['home'],
        ],
        'article' => [
            'label' => 'Блок в колонке статьи', 'size' => '300 × 250', 'mark' => 'Реклама', 'image' => false,
            'places' => ['news_show' => 'вверху правой колонки'],
            'default' => ['news_show'],
        ],
    ];

    /** Поля зоны и их предел длины в символах. */
    public const FIELDS = ['sponsor' => 60, 'title' => 160, 'text' => 240, 'cta' => 40, 'url' => 500, 'image' => 500];

    /**
     * Все зоны как есть (для формы): у каждой ключ enabled и поля.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $raw = json_decode((string) Setting::get(self::SETTING, ''), true);
        $raw = is_array($raw) ? $raw : [];

        $zones = [];
        foreach (array_keys(self::ZONES) as $key) {
            $zones[$key] = self::normalize($key, is_array($raw[$key] ?? null) ? $raw[$key] : []);
        }

        return $zones;
    }

    /**
     * Зона для вывода или null, если показывать нечего.
     *
     * @return array<string, mixed>|null
     */
    public static function zone(string $key, string $place = 'other'): ?array
    {
        if (!isset(self::ZONES[$key])) {
            return null;
        }
        $zone = self::all()[$key];
        if (!in_array($place, $zone['pages'], true)) {
            return null;
        }
        if (!$zone['enabled'] || $zone['title'] === '' || $zone['url'] === '') {
            return null;
        }
        if (self::ZONES[$key]['image'] && $key === 'feed' && $zone['image'] === '') {
            return null;
        }
        $zone['key'] = $key;
        $zone['mark'] = self::ZONES[$key]['mark'];
        $zone['external'] = (bool) preg_match('~^https?://~i', (string) $zone['url']);

        return $zone;
    }

    /** @param array<string, mixed> $zones присланное формой: ключ зоны → поля */
    public static function save(array $zones): void
    {
        $clean = [];
        foreach (array_keys(self::ZONES) as $key) {
            $clean[$key] = self::normalize($key, is_array($zones[$key] ?? null) ? $zones[$key] : []);
        }
        Setting::set(self::SETTING, json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<mixed> $input
     * @return array<string, mixed>
     */
    private static function normalize(string $key, array $input): array
    {
        $zone = ['enabled' => !empty($input['enabled'])];
        // Страницы показа: только из возможных для зоны. Ключа нет вовсе
        // (запись старше настройки) — умолчание зоны; пустой список законен
        // и значит «нигде», форма шлёт для этого пустой маркер.
        $places = self::ZONES[$key]['places'];
        $zone['pages'] = array_key_exists('pages', $input) && is_array($input['pages'])
            ? array_values(array_filter(
                array_keys($places),
                static fn (string $place): bool => in_array($place, $input['pages'], true)
            ))
            : self::ZONES[$key]['default'];
        foreach (self::FIELDS as $field => $limit) {
            $value = $input[$field] ?? '';
            $value = is_string($value) ? trim(strip_tags($value)) : '';
            $zone[$field] = mb_substr($value, 0, $limit);
        }
        // Ссылки идут в href: javascript: и прочее чужое отбрасывается.
        foreach (['url', 'image'] as $field) {
            if ($zone[$field] !== '' && !UrlGuard::isSafeLink($zone[$field])) {
                $zone[$field] = '';
            }
        }

        return $zone;
    }
}
