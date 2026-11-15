<?php

declare(strict_types=1);

namespace App\Core\Portal;

use App\Core\DateFormatter;
use App\Core\UrlGuard;
use App\Models\Setting;

/**
 * Рекламные зоны шаблона портала. Мест ровно пять, и они объявлены здесь
 * одним списком: форма админки рисует поля по нему, вьюхи спрашивают зону по
 * ключу. Хранятся одной настройкой JSON — записей пять, индексировать нечего.
 *
 * В зоне может быть несколько креативов (`items`), у каждого — свой период
 * показа. Когда в периоде их несколько, они сменяются: случайный при каждой
 * загрузке или по очереди через заданное время (`rotate`, `interval`).
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

    /** Поля креатива и их предел длины в символах. */
    public const FIELDS = ['sponsor' => 60, 'title' => 160, 'text' => 240, 'cta' => 40, 'url' => 500, 'image' => 500];

    /**
     * Как сменяются креативы зоны, если в периоде их несколько: случайный при
     * каждой загрузке страницы или по очереди через заданное число секунд.
     */
    public const ROTATIONS = [
        'load' => 'Случайный при каждой загрузке страницы',
        'interval' => 'По очереди, через заданное время',
    ];

    /** Креативов в зоне: больше — это уже не ротация, а лотерея. */
    public const MAX_ITEMS = 10;

    public const INTERVAL_MIN = 5;
    public const INTERVAL_MAX = 300;
    public const INTERVAL_DEFAULT = 15;

    /**
     * Все зоны как есть (для формы): у каждой enabled, pages, rotate,
     * interval и список креативов.
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
     * В `items` — креативы, чей период ещё не закончился: и идущие сейчас, и
     * будущие. Страница кэшируется, поэтому выбор «что сейчас» делает браузер
     * (`portal.js` по `data-ad-from/to`), а сервер лишь отмечает `active` —
     * его видно без скрипта. Ни одного годного креатива — null.
     *
     * @return array<string, mixed>|null
     */
    public static function zone(string $key, string $place = 'other', ?int $now = null): ?array
    {
        if (!isset(self::ZONES[$key])) {
            return null;
        }
        $zone = self::all()[$key];
        if (!$zone['enabled'] || !in_array($place, $zone['pages'], true)) {
            return null;
        }
        $now ??= time();
        $items = [];
        foreach ($zone['items'] as $item) {
            if ($item['title'] === '' || $item['url'] === '' || ($key === 'feed' && $item['image'] === '')) {
                continue;
            }
            $to = self::stamp($item['to']);
            if ($to !== null && $to <= $now) {
                continue;
            }
            $from = self::stamp($item['from']);
            $item['from_ts'] = $from;
            $item['to_ts'] = $to;
            $item['active'] = $from === null || $from <= $now;
            $item['external'] = (bool) preg_match('~^https?://~i', (string) $item['url']);
            $items[] = $item;
        }
        if ($items === []) {
            return null;
        }
        // Без скрипта виден ровно один креатив — первый идущий сейчас.
        $shown = false;
        foreach ($items as $i => $item) {
            $items[$i]['shown'] = !$shown && $item['active'];
            $shown = $shown || $item['active'];
        }

        return [
            'key' => $key,
            'mark' => self::ZONES[$key]['mark'],
            'rotate' => $zone['rotate'],
            'interval' => $zone['interval'],
            'items' => $items,
        ];
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

    /** Отметка времени начала/конца показа; пусто — без границы. */
    public static function stamp(string $value): ?int
    {
        if ($value === '') {
            return null;
        }
        $ts = DateFormatter::format($value, 'U');

        return $ts === '' ? null : (int) $ts;
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
        $rotate = $input['rotate'] ?? 'load';
        $zone['rotate'] = is_string($rotate) && isset(self::ROTATIONS[$rotate]) ? $rotate : 'load';
        $interval = is_numeric($input['interval'] ?? null) ? (int) $input['interval'] : self::INTERVAL_DEFAULT;
        $zone['interval'] = max(self::INTERVAL_MIN, min(self::INTERVAL_MAX, $interval));

        // Запись старше ротации хранила один креатив прямо в зоне.
        $rawItems = is_array($input['items'] ?? null) ? $input['items'] : [array_intersect_key($input, self::FIELDS)];
        $items = [];
        foreach ($rawItems as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $item = self::item($raw);
            // Пустая строка репитера — не креатив.
            if ($item['title'] === '' && $item['url'] === '' && $item['image'] === '' && $item['sponsor'] === '') {
                continue;
            }
            $items[] = $item;
            if (count($items) >= self::MAX_ITEMS) {
                break;
            }
        }
        $zone['items'] = $items;

        return $zone;
    }

    /**
     * @param array<mixed> $raw
     * @return array<string, string>
     */
    private static function item(array $raw): array
    {
        $item = [];
        foreach (self::FIELDS as $field => $limit) {
            $value = $raw[$field] ?? '';
            $value = is_string($value) ? trim(strip_tags($value)) : '';
            $item[$field] = mb_substr($value, 0, $limit);
        }
        // Ссылки идут в href: javascript: и прочее чужое отбрасывается.
        foreach (['url', 'image'] as $field) {
            if ($item[$field] !== '' && !UrlGuard::isSafeLink($item[$field])) {
                $item[$field] = '';
            }
        }
        // Период показа: поле формы datetime-local («2026-11-20T09:00»).
        foreach (['from', 'to'] as $field) {
            $value = $raw[$field] ?? '';
            $value = is_string($value) ? trim($value) : '';
            $item[$field] = preg_match('/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})/', $value, $m) === 1 && self::stamp($m[1] . ' ' . $m[2]) !== null
                ? $m[1] . ' ' . $m[2]
                : '';
        }

        return $item;
    }
}
