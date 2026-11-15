<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

/**
 * Погода в шапке портала: столицы региона, источник — Open-Meteo (без ключа).
 *
 * Публичный рендер в сеть не ходит: копия лежит в настройке, а обновляет её
 * воркер по cron или запрос после ответа посетителю (как у курсов ЦБ). Все
 * города забираются одним запросом — API принимает список координат.
 */
final class Weather
{
    public const SOURCE = 'https://api.open-meteo.com/v1/forecast';

    /** Город => [подпись (ключ словаря), широта, долгота]. */
    public const CITIES = [
        'tashkent' => ['Ташкент', 41.3111, 69.2797],
        'almaty' => ['Алматы', 43.2380, 76.9450],
        'astana' => ['Астана', 51.1694, 71.4491],
        'bishkek' => ['Бишкек', 42.8746, 74.5698],
        'dushanbe' => ['Душанбе', 38.5598, 68.7870],
        'ashgabat' => ['Ашхабад', 37.9601, 58.3261],
    ];

    public const DEFAULT_CITY = 'tashkent';

    /** Погода меняется за час; чаще ходить незачем. */
    public const REFRESH_AFTER = 3600;

    /** Старше этого копия не показывается: вчерашняя температура хуже никакой. */
    private const MAX_AGE = 6 * 3600;

    private const RETRY_AFTER = 1800;

    private const SETTING = 'weather_current';

    private const ATTEMPT_SETTING = 'weather_attempt';

    private static bool $scheduled = false;

    /**
     * Ответ Open-Meteo на список координат — массив по городам в том же порядке.
     *
     * @return array<string, array{temp: float, code: int}>|null
     */
    public static function parse(string $json): ?array
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }
        // Один город API отдаёт объектом, несколько — списком.
        $list = array_is_list($data) ? $data : [$data];
        $keys = array_keys(self::CITIES);
        $out = [];
        foreach ($list as $i => $row) {
            $current = is_array($row) && is_array($row['current'] ?? null) ? $row['current'] : null;
            if ($current === null || !isset($keys[$i]) || !is_numeric($current['temperature_2m'] ?? null)) {
                continue;
            }
            $out[$keys[$i]] = ['temp' => (float) $current['temperature_2m'], 'code' => (int) ($current['weather_code'] ?? 0)];
        }

        return $out === [] ? null : $out;
    }

    /** Адрес запроса: все города разом. */
    public static function requestUrl(): string
    {
        $lat = $lon = [];
        foreach (self::CITIES as [, $la, $lo]) {
            $lat[] = $la;
            $lon[] = $lo;
        }

        return self::SOURCE . '?' . http_build_query([
            'latitude' => implode(',', $lat),
            'longitude' => implode(',', $lon),
            'current' => 'temperature_2m,weather_code',
            'timezone' => 'auto',
        ]);
    }

    /**
     * Забрать и сохранить. Сеть — только воркер, панель и обновление после ответа.
     *
     * @return array{ok: bool, error: string}
     */
    public static function refresh(): array
    {
        Setting::set(self::ATTEMPT_SETTING, (string) time());
        $response = Http::getSafeRemote(self::requestUrl(), ['Accept: application/json'], 15);
        $parsed = (int) $response['status'] === 200 ? self::parse((string) $response['body']) : null;
        if ($parsed === null) {
            $error = $response['error'] !== '' ? $response['error'] : 'ответ Open-Meteo не разобран (HTTP ' . (int) $response['status'] . ')';
            IntegrationStatus::fail('weather', $error, 'погода');

            return ['ok' => false, 'error' => $error];
        }
        Setting::set(self::SETTING, json_encode(
            ['cities' => $parsed, 'fetched_at' => time()],
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));
        IntegrationStatus::ok('weather', 'погода');
        // Погода печатается в шапке, а страницы кэшируются.
        Cache::forgetPrefix('page:');

        return ['ok' => true, 'error' => ''];
    }

    /**
     * Погода города из свежей копии.
     *
     * @return array{city: string, temp: int, icon: string, label: string}|null
     */
    public static function current(string $city, ?int $now = null): ?array
    {
        $now ??= time();
        $data = json_decode((string) Setting::get(self::SETTING, ''), true);
        if (!is_array($data) || $now - (int) ($data['fetched_at'] ?? 0) > self::MAX_AGE) {
            return null;
        }
        $city = isset(self::CITIES[$city]) ? $city : self::DEFAULT_CITY;
        $row = $data['cities'][$city] ?? null;
        if (!is_array($row) || !is_numeric($row['temp'] ?? null)) {
            return null;
        }
        [$icon, $label] = self::condition((int) ($row['code'] ?? 0));

        return ['city' => self::CITIES[$city][0], 'temp' => (int) round((float) $row['temp']), 'icon' => $icon, 'label' => $label];
    }

    /**
     * Код погоды WMO → значок Tabler и подпись (ключ словаря).
     *
     * @return array{0: string, 1: string}
     */
    public static function condition(int $code): array
    {
        return match (true) {
            $code === 0 => ['sun', 'Ясно'],
            $code <= 2 => ['cloud', 'Переменная облачность'],
            $code === 3 => ['cloud', 'Пасмурно'],
            $code <= 48 => ['cloud-fog', 'Туман'],
            $code <= 67, $code >= 80 && $code <= 82 => ['cloud-rain', 'Дождь'],
            $code <= 77, $code === 85, $code === 86 => ['snowflake', 'Снег'],
            default => ['cloud-storm', 'Гроза'],
        };
    }

    /** «+14°», «−3°», «0°». */
    public static function format(int $temp): string
    {
        return ($temp > 0 ? '+' : ($temp < 0 ? '−' : '')) . abs($temp) . '°';
    }

    public static function isDue(?int $now = null): bool
    {
        $now ??= time();
        $data = json_decode((string) Setting::get(self::SETTING, ''), true);
        $fetched = is_array($data) ? (int) ($data['fetched_at'] ?? 0) : 0;
        if ($now - $fetched < self::REFRESH_AFTER) {
            return false;
        }

        return $now - (int) Setting::get(self::ATTEMPT_SETTING, '0') >= self::RETRY_AFTER;
    }

    /** Обновить после ответа посетителю, если пора (только веб). */
    public static function afterResponse(): void
    {
        if (self::$scheduled || PHP_SAPI === 'cli' || !self::isDue()) {
            return;
        }
        self::$scheduled = true;
        register_shutdown_function([self::class, 'flushAfterResponse']);
    }

    /** @internal вызывается из register_shutdown_function */
    public static function flushAfterResponse(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        }
        $lock = ProcessLock::acquire('weather_worker');
        if ($lock === null) {
            return;
        }
        try {
            if (self::isDue()) {
                self::refresh();
            }
        } catch (\Throwable $e) {
            Logger::swallowed('Погода: обновление после ответа не удалось', $e);
        } finally {
            ProcessLock::release($lock);
        }
    }
}
