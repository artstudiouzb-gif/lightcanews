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

    /**
     * Ключ → подпись в админке, размер-ориентир, подпись над зоной на сайте и
     * нужна ли картинка.
     *
     * @var array<string, array{label: string, size: string, mark: string, image: bool}>
     */
    public const ZONES = [
        'top' => ['label' => 'Растяжка под шапкой', 'size' => '1440 × 90', 'mark' => 'Реклама', 'image' => false],
        'feed' => ['label' => 'Объявление в ленте новостей', 'size' => '96 × 64 + строка', 'mark' => 'Реклама', 'image' => true],
        'partner' => ['label' => 'Партнёрский материал под лентой', 'size' => '16:9 + текст', 'mark' => 'Партнёрский материал', 'image' => true],
        'mid' => ['label' => 'Партнёрский блок в середине главной', 'size' => '1200 × 108', 'mark' => 'Партнёрский блок', 'image' => false],
        'article' => ['label' => 'Блок в колонке статьи', 'size' => '300 × 250', 'mark' => 'Реклама', 'image' => false],
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
            $zones[$key] = self::normalize(is_array($raw[$key] ?? null) ? $raw[$key] : []);
        }

        return $zones;
    }

    /**
     * Зона для вывода или null, если показывать нечего.
     *
     * @return array<string, mixed>|null
     */
    public static function zone(string $key): ?array
    {
        if (!isset(self::ZONES[$key])) {
            return null;
        }
        $zone = self::all()[$key];
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
            $clean[$key] = self::normalize(is_array($zones[$key] ?? null) ? $zones[$key] : []);
        }
        Setting::set(self::SETTING, json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<mixed> $input
     * @return array<string, mixed>
     */
    private static function normalize(array $input): array
    {
        $zone = ['enabled' => !empty($input['enabled'])];
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
