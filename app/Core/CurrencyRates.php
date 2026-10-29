<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

/**
 * Курсы валют региона к доллару США — плашка в шапке портала.
 *
 * Источник — открытый JSON Центрального банка Узбекистана: он публикует курс
 * каждой валюты в сумах, и курсы соседей к доллару получаются делением
 * (KZT за 1 USD = сумов за доллар / сумов за тенге). Ключа и квоты нет.
 *
 * **Публичная страница в сеть не ходит** (CLAUDE.md, «Скорость публичной
 * страницы»): плашку курсов однажды уже собирала шапка, и чужой таймаут
 * платил каждый посетитель. Поэтому курсы забирает воркер
 * (`app/Console/rates_worker.php`), а шапка читает сохранённую копию.
 * Копия старше недели не показывается вовсе: устаревший курс на новостном
 * сайте хуже, чем его отсутствие.
 */
final class CurrencyRates
{
    public const SOURCE = 'https://cbu.uz/ru/arkhiv-kursov-valyut/json/';

    /** Валюты плашки в порядке вывода: сум и соседи по региону. */
    public const CURRENCIES = ['UZS', 'KZT', 'KGS', 'TJS', 'TMT'];

    private const SETTING = 'currency_rates';

    /** Копия старше этого не показывается. */
    private const MAX_AGE = 7 * 86400;

    /**
     * Курсы к доллару из ответа ЦБ. null — ответ не разобран или в нём нет
     * доллара: без него пересчитать соседей не через что.
     *
     * @return array{date: string, rates: array<string, float>}|null
     */
    public static function parse(string $json): ?array
    {
        $rows = json_decode($json, true);
        if (!is_array($rows)) {
            return null;
        }
        $perUnit = [];
        $date = '';
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $code = strtoupper(trim((string) ($row['Ccy'] ?? '')));
            $rate = (float) str_replace(',', '.', (string) ($row['Rate'] ?? '0'));
            $nominal = max(1.0, (float) ($row['Nominal'] ?? 1));
            if ($code === '' || $rate <= 0) {
                continue;
            }
            $perUnit[$code] = $rate / $nominal;
            if ($code === 'USD') {
                $date = trim((string) ($row['Date'] ?? ''));
            }
        }
        $usd = $perUnit['USD'] ?? 0.0;
        if ($usd <= 0) {
            return null;
        }

        $rates = [];
        foreach (self::CURRENCIES as $code) {
            if ($code === 'UZS') {
                $rates[$code] = round($usd, 2);
            } elseif (isset($perUnit[$code])) {
                $rates[$code] = round($usd / $perUnit[$code], 2);
            }
        }

        return ['date' => $date, 'rates' => $rates];
    }

    /**
     * Забирает курсы и сохраняет копию. Только из воркера или панели: здесь
     * сетевой запрос.
     *
     * @return array{ok: bool, error: string}
     */
    public static function refresh(): array
    {
        $response = Http::getSafeRemote(self::SOURCE, ['Accept: application/json'], 15);
        $parsed = (int) $response['status'] === 200 ? self::parse((string) $response['body']) : null;
        if ($parsed === null) {
            $error = $response['error'] !== '' ? $response['error'] : 'ответ ЦБ не разобран (HTTP ' . (int) $response['status'] . ')';
            IntegrationStatus::fail('rates', $error, 'курсы валют');

            return ['ok' => false, 'error' => $error];
        }

        Setting::set(self::SETTING, json_encode(
            $parsed + ['fetched_at' => time()],
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));
        IntegrationStatus::ok('rates', 'курсы валют');

        return ['ok' => true, 'error' => ''];
    }

    /**
     * Сохранённая копия, если она свежая.
     *
     * @return array{date: string, rates: array<string, float>, fetched_at: int}|null
     */
    public static function current(): ?array
    {
        $data = json_decode((string) Setting::get(self::SETTING, ''), true);
        if (!is_array($data) || !is_array($data['rates'] ?? null) || $data['rates'] === []) {
            return null;
        }
        $fetched = (int) ($data['fetched_at'] ?? 0);
        if ($fetched <= 0 || time() - $fetched > self::MAX_AGE) {
            return null;
        }
        $rates = [];
        foreach (self::CURRENCIES as $code) {
            if (isset($data['rates'][$code]) && is_numeric($data['rates'][$code])) {
                $rates[$code] = (float) $data['rates'][$code];
            }
        }

        return $rates === [] ? null : ['date' => (string) ($data['date'] ?? ''), 'rates' => $rates, 'fetched_at' => $fetched];
    }

    /** Плашка для шапки; пусто, если свежей копии нет. */
    public static function html(): string
    {
        $current = self::current();
        if ($current === null) {
            return '';
        }
        $items = '';
        foreach ($current['rates'] as $code => $rate) {
            // Неразрывный пробел между разрядами: «11 965,51» не должно
            // переноситься посередине числа.
            $value = number_format($rate, 2, ',', "\u{00A0}");
            $items .= '<span class="hdr-rates__item">' . $code . '<b>' . $value . '</b></span>';
        }
        $title = $current['date'] !== '' ? ' title="' . htmlspecialchars(t('Курс ЦБ Узбекистана на') . ' ' . $current['date'], ENT_QUOTES) . '"' : '';

        return '<div class="hdr-rates" aria-label="' . htmlspecialchars(t('Курсы валют к доллару США'), ENT_QUOTES) . '"' . $title . ' data-no-translit>'
            . '<span class="hdr-rates__label">' . htmlspecialchars(t('За 1 USD'), ENT_QUOTES) . '</span>' . $items . '</div>';
    }
}
