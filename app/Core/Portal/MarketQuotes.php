<?php

declare(strict_types=1);

namespace App\Core\Portal;

use App\Core\DateFormatter;
use App\Models\Setting;

/**
 * Котировки сырья для полосы курсов портала: хлопок, пряжа и прочее.
 *
 * Значения вводит редакция. Биржевые данные ICE бесплатно и легально
 * автоматически не получить, поэтому автозагрузки здесь нет. Предыдущее значение
 * запоминается само: на сохранении новая цифра сравнивается с прежней, и прежняя
 * становится базой изменения. Редактору не нужно вести вторую колонку руками.
 */
final class MarketQuotes
{
    private const SETTING = 'market_quotes';

    public const MAX_ITEMS = 8;

    /** Набор по умолчанию: строки уже названы, редакция вводит только цифры. */
    public const DEFAULTS = [
        ['names' => ['ru' => 'Хлопок ICE', 'uz' => 'Paxta ICE', 'en' => 'ICE Cotton'], 'unit' => '¢/lb', 'value' => '', 'prev' => '', 'updated' => ''],
        ['names' => ['ru' => 'Хлопковое волокно', 'uz' => 'Paxta tolasi', 'en' => 'Cotton lint'], 'unit' => '$/т', 'value' => '', 'prev' => '', 'updated' => ''],
        ['names' => ['ru' => 'Пряжа Ne 30/1', 'uz' => 'Ip Ne 30/1', 'en' => 'Yarn Ne 30/1'], 'unit' => '$/кг', 'value' => '', 'prev' => '', 'updated' => ''],
    ];

    /**
     * Сохранённые строки как есть, для формы.
     *
     * @return array{items: list<array<string, mixed>>, source: string}
     */
    public static function all(): array
    {
        $raw = (string) Setting::get(self::SETTING, '');
        if ($raw === '') {
            return ['items' => self::DEFAULTS, 'source' => ''];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['items' => self::DEFAULTS, 'source' => ''];
        }
        $items = [];
        foreach (is_array($data['items'] ?? null) ? $data['items'] : [] as $item) {
            if (is_array($item)) {
                $items[] = self::clean($item);
            }
        }

        return ['items' => array_slice($items, 0, self::MAX_ITEMS), 'source' => self::str($data['source'] ?? '', 120)];
    }

    /**
     * Сохранение формы. Изменившееся значение сдвигает прежнее в базу изменения
     * и ставит дату; неизменное оставляет и то и другое.
     *
     * @param array<mixed> $items
     */
    public static function save(array $items, string $source, ?int $now = null): void
    {
        $now ??= time();
        $old = [];
        foreach (self::all()['items'] as $i => $item) {
            $old[$i] = $item;
        }
        $out = [];
        $i = 0;
        foreach ($items as $item) {
            if (!is_array($item) || count($out) >= self::MAX_ITEMS) {
                continue;
            }
            $row = self::clean($item);
            if (implode('', $row['names']) === '' && $row['value'] === '') {
                continue;
            }
            $before = self::matching($old, $row, $i);
            $row['prev'] = $before['prev'] ?? '';
            $row['updated'] = $before['updated'] ?? '';
            if ($row['value'] !== '' && $row['value'] !== ($before['value'] ?? '')) {
                $row['prev'] = (string) ($before['value'] ?? '');
                $row['updated'] = DateFormatter::format($now, 'Y-m-d');
            }
            $out[] = $row;
            $i++;
        }
        Setting::set(self::SETTING, json_encode(
            ['items' => $out, 'source' => self::str($source, 120)],
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));
    }

    /**
     * Строки для вывода: только со значением, название на языке страницы.
     *
     * @return list<array{name: string, unit: string, value: float, change: ?float, updated: string}>
     */
    public static function current(string $lang, string $fallbackLang = 'ru'): array
    {
        $out = [];
        foreach (self::all()['items'] as $item) {
            $value = self::number($item['value']);
            $name = $item['names'][$lang] ?? '';
            if ($name === '') {
                $name = $item['names'][$fallbackLang] ?? '';
            }
            if ($value === null || $name === '') {
                continue;
            }
            $prev = self::number($item['prev']);
            $out[] = [
                'name' => $name,
                'unit' => $item['unit'],
                'value' => $value,
                'change' => $prev !== null && $prev != 0.0 ? ($value - $prev) / $prev * 100 : null,
                'updated' => $item['updated'],
            ];
        }

        return $out;
    }

    public static function source(): string
    {
        return self::all()['source'];
    }

    /** «5,6» и «1 240» набирают как в документе. */
    public static function number(mixed $v): ?float
    {
        $s = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], trim((string) $v));

        return $s !== '' && is_numeric($s) ? (float) $s : null;
    }

    /** Значение в виде, в каком его печатает сайт: два знака, если дробное. */
    public static function format(float $v): string
    {
        $decimals = floor($v) == $v ? 0 : 2;

        return number_format($v, $decimals, ',', "\u{00A0}");
    }

    /**
     * @param array<mixed> $item
     * @return array{names: array<string, string>, unit: string, value: string, prev: string, updated: string}
     */
    private static function clean(array $item): array
    {
        $names = [];
        foreach (is_array($item['names'] ?? null) ? $item['names'] : [] as $code => $name) {
            if (is_string($code) && preg_match('/^[a-z]{2,5}$/', $code)) {
                $names[$code] = self::str($name, 60);
            }
        }
        $value = self::str($item['value'] ?? '', 20);
        $updated = self::str($item['updated'] ?? '', 10);

        return [
            'names' => $names,
            'unit' => self::str($item['unit'] ?? '', 16),
            'value' => self::number($value) === null ? '' : $value,
            'prev' => self::number($item['prev'] ?? '') === null ? '' : self::str($item['prev'], 20),
            'updated' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $updated) ? $updated : '',
        ];
    }

    /**
     * Прежняя строка той же котировки: по названию, иначе по месту.
     *
     * @param array<int, array<string, mixed>> $old
     * @param array{names: array<string, string>} $row
     * @return array<string, mixed>
     */
    private static function matching(array $old, array $row, int $i): array
    {
        foreach ($old as $item) {
            foreach ($row['names'] as $code => $name) {
                if ($name !== '' && ($item['names'][$code] ?? '') === $name) {
                    return $item;
                }
            }
        }

        return $old[$i] ?? [];
    }

    private static function str(mixed $v, int $max): string
    {
        return is_string($v) || is_numeric($v) ? mb_substr(trim((string) $v), 0, $max) : '';
    }
}
