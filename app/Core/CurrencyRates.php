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

    /** Полоса курсов портала, группа «Сум»: валюты торговых партнёров отрасли к суму. */
    public const SUM_PAIRS = ['USD', 'EUR', 'RUB', 'CNY'];

    /** Группа «Доллар в регионе»: сколько валюты соседей за один доллар. */
    public const REGION_PAIRS = ['KZT', 'KGS', 'TJS', 'TMT'];

    private const SETTING = 'currency_rates';

    /** Копия старше этого не показывается. */
    private const MAX_AGE = 7 * 86400;

    /**
     * Курсы к доллару из ответа ЦБ. null — ответ не разобран или в нём нет
     * доллара: без него пересчитать соседей не через что.
     *
     * Кроме курсов к доллару (`rates`, их читает плашка прежней шапки)
     * отдаёт две группы полосы портала с изменением к прошлому курсу:
     * `sum` — сумов за единицу валюты, `region` — валюты за доллар. Прошлый
     * курс ЦБ отдаёт в том же ответе разницей (`Diff`), поэтому кросс-курс
     * прошлого дня пересчитывается так же, как сегодняшний.
     *
     * @return array{date: string, rates: array<string, float>,
     *     sum: array<string, array{value: float, change: ?float}>,
     *     region: array<string, array{value: float, change: ?float}>}|null
     */
    public static function parse(string $json): ?array
    {
        $rows = json_decode($json, true);
        if (!is_array($rows)) {
            return null;
        }
        $perUnit = [];
        $prevUnit = [];
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
            $diff = $row['Diff'] ?? null;
            if (is_numeric(str_replace(',', '.', (string) $diff))) {
                $prev = $rate - (float) str_replace(',', '.', (string) $diff);
                if ($prev > 0) {
                    $prevUnit[$code] = $prev / $nominal;
                }
            }
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

        $usdPrev = $prevUnit['USD'] ?? null;
        $sum = [];
        foreach (self::SUM_PAIRS as $code) {
            if (!isset($perUnit[$code])) {
                continue;
            }
            $value = round($perUnit[$code], 2);
            $sum[$code] = [
                'value' => $value,
                'change' => isset($prevUnit[$code]) ? round($value - round($prevUnit[$code], 2), 2) : null,
            ];
        }
        $region = [];
        foreach (self::REGION_PAIRS as $code) {
            if (!isset($perUnit[$code])) {
                continue;
            }
            $value = round($usd / $perUnit[$code], 2);
            $region[$code] = [
                'value' => $value,
                'change' => $usdPrev !== null && isset($prevUnit[$code])
                    ? round($value - round($usdPrev / $prevUnit[$code], 2), 2) : null,
            ];
        }

        return ['date' => $date, 'rates' => $rates, 'sum' => $sum, 'region' => $region];
    }

    /**
     * Группа полосы из сохранённой копии. Копия, снятая до появления групп,
     * несёт только `rates`: тогда группы собираются из них, без изменения.
     *
     * @param array<string, mixed> $data
     * @param list<string> $codes
     * @return array<string, array{value: float, change: ?float}>
     */
    private static function group(array $data, string $key, array $codes): array
    {
        $out = [];
        $stored = is_array($data[$key] ?? null) ? $data[$key] : [];
        $legacy = is_array($data['rates'] ?? null) ? $data['rates'] : [];
        foreach ($codes as $code) {
            $item = $stored[$code] ?? null;
            if (is_array($item) && is_numeric($item['value'] ?? null)) {
                $out[$code] = [
                    'value' => (float) $item['value'],
                    'change' => is_numeric($item['change'] ?? null) ? (float) $item['change'] : null,
                ];
                continue;
            }
            // Прежний формат: «UZS» — сумов за доллар, остальное — за доллар.
            $legacyKey = $key === 'sum' ? ($code === 'USD' ? 'UZS' : null) : $code;
            if ($stored === [] && $legacyKey !== null && is_numeric($legacy[$legacyKey] ?? null)) {
                $out[$code] = ['value' => (float) $legacy[$legacyKey], 'change' => null];
            }
        }

        return $out;
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

    /** Копия старше этого — повод обновить её после ответа посетителю. */
    public const REFRESH_AFTER = 3 * 3600;

    /** Неудачная попытка не повторяется чаще: лежащий ЦБ не дёргаем каждым заходом. */
    private const RETRY_AFTER = 3600;

    private const ATTEMPT_SETTING = 'currency_rates_attempt';

    private static bool $scheduled = false;

    /**
     * Обновить и сбросить кэш страниц: курсы печатаются на главной и в шапке,
     * а страницы кэшируются. Одна последовательность на воркер, кнопку панели
     * и обновление после ответа.
     *
     * @return array{ok: bool, error: string}
     */
    public static function update(): array
    {
        Setting::set(self::ATTEMPT_SETTING, (string) time());
        $result = self::refresh();
        if ($result['ok']) {
            Cache::forgetPrefix('page:');
        }

        return $result;
    }

    /**
     * Пора ли обновить: копия старше REFRESH_AFTER (или её нет), а прошлая
     * попытка была не ближе RETRY_AFTER. Читает только настройки — сеть не
     * трогает, поэтому годится для каждого публичного запроса.
     */
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

    /**
     * Обновить после ответа, если пора. Так курсы живут и без cron: первый
     * посетитель после срока получает страницу как обычно, а запрос к ЦБ идёт
     * уже после того, как ответ отдан. Только из веб-запроса — у консоли свой
     * воркер, а тесты наружу не ходят.
     */
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
        // Тот же замок, что у воркера: обновляет кто-то один.
        $lock = ProcessLock::acquire('rates_worker');
        if ($lock === null) {
            return;
        }
        try {
            if (self::isDue()) {
                self::update();
            }
        } catch (\Throwable $e) {
            Logger::swallowed('Курсы валют: обновление после ответа не удалось', $e);
        } finally {
            ProcessLock::release($lock);
        }
    }

    /** Когда сохранена копия (0 — ни разу). */
    public static function fetchedAt(): int
    {
        $data = json_decode((string) Setting::get(self::SETTING, ''), true);

        return is_array($data) ? (int) ($data['fetched_at'] ?? 0) : 0;
    }

    /**
     * Сохранённая копия, если она свежая.
     *
     * @return array{date: string, rates: array<string, float>, fetched_at: int,
     *     sum: array<string, array{value: float, change: ?float}>,
     *     region: array<string, array{value: float, change: ?float}>}|null
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

        return $rates === [] ? null : [
            'date' => (string) ($data['date'] ?? ''),
            'rates' => $rates,
            'fetched_at' => $fetched,
            'sum' => self::group($data, 'sum', self::SUM_PAIRS),
            'region' => self::group($data, 'region', self::REGION_PAIRS),
        ];
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
