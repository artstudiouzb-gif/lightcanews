<?php

declare(strict_types=1);

namespace App\Core\Portal;

use App\Core\DateFormatter;
use App\Core\Locale;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Setting;

/**
 * Новый публичный шаблон портала LightCA.News.
 *
 * Это не перекраска прежней темы госсайта, а отдельный слой: свои вьюхи
 * (`app/Views/portal`), свой CSS (`portal.css`) и свой скрипт (`portal.js`).
 * Старый бандл на этих страницах не подключается вовсе, поэтому правила
 * прежней темы ни во что здесь не вмешиваются.
 *
 * Класс отвечает за данные: из строк новостей собирает карточки, которые
 * вьюхи печатают как есть, — вычислений в разметке нет.
 */
final class Portal
{
    /** Двухбуквенный код страны для меток: ключ — slug раздела-страны. */
    public const COUNTRY_CODES = [
        'uzbekistan' => 'UZ',
        'kazakhstan' => 'KZ',
        'kyrgyzstan' => 'KG',
        'tajikistan' => 'TJ',
        'turkmenistan' => 'TM',
        'world' => 'МИР',
    ];

    /** Рубрика колонок «Точка зрения» — по slug, как её заводит редакция. */
    public const OPINION_SLUG = 'opinion';

    /**
     * Включён ли новый шаблон. Прежний остаётся запасным выходом
     * (`public_template = classic`), пока на новый переведены не все страницы.
     */
    public static function enabled(): bool
    {
        try {
            return Setting::get('public_template', 'portal') !== 'classic';
        } catch (\Throwable) {
            return true;
        }
    }

    /** Как метить страну рядом с названием: коды, флаги или никак. */
    public const COUNTRY_MARKS = ['codes' => 'Коды (UZ, KZ, МИР)', 'flags' => 'Флаги и глобус', 'off' => 'Не показывать'];

    /**
     * Вид меток страны — настройка «Дизайна» `design_country_codes`. Метку
     * отдают два метода (`countryCode` — текст, `countryMark` — разметка), и
     * пустая она нигде не печатается, поэтому переключение действует везде.
     */
    public static function countryMarkMode(): string
    {
        $mode = (string) Setting::get('design_country_codes', 'codes');
        if ($mode === 'on') {
            return 'codes';
        }

        return isset(self::COUNTRY_MARKS[$mode]) ? $mode : 'codes';
    }

    public static function showCountryCodes(): bool
    {
        return self::countryMarkMode() !== 'off';
    }

    /**
     * Флаги нарисованы упрощённо: на размере строки текста гербы и звёзды
     * не читаются, узнаётся сочетание полос и цвета. Эмодзи-флаги Windows не
     * рисует вовсе (выходят те же буквы), поэтому — SVG в разметке.
     */
    private const FLAGS = [
        'uzbekistan' => '<rect width="30" height="20" fill="#1eb53a"/><rect width="30" height="13.6" fill="#fff"/><rect width="30" height="6.4" fill="#0099b5"/><rect y="6.4" width="30" height=".6" fill="#ce1126"/><rect y="13" width="30" height=".6" fill="#ce1126"/><circle cx="5" cy="3.2" r="2.2" fill="#fff"/><circle cx="5.9" cy="3.2" r="2" fill="#0099b5"/>',
        'kazakhstan' => '<rect width="30" height="20" fill="#00afca"/><circle cx="15" cy="9" r="3.4" fill="#fec50c"/><rect x="2" y="1" width="1.6" height="18" fill="#fec50c"/>',
        'kyrgyzstan' => '<rect width="30" height="20" fill="#e8112d"/><circle cx="15" cy="10" r="5" fill="#ffef00"/><circle cx="15" cy="10" r="2.6" fill="#e8112d"/>',
        'tajikistan' => '<rect width="30" height="20" fill="#060"/><rect width="30" height="14.3" fill="#fff"/><rect width="30" height="5.7" fill="#c00"/><path d="M12.6 11.4h4.8l-.6-2.4-1.2 1.2-1.2-1.6-1.2 1.6-1.2-1.2z" fill="#f8c300"/>',
        'turkmenistan' => '<rect width="30" height="20" fill="#00843d"/><rect x="3" width="5" height="20" fill="#d22630"/><circle cx="13.5" cy="5" r="2.6" fill="#fff"/><circle cx="14.6" cy="4.6" r="2.3" fill="#00843d"/>',
    ];

    /** Глобус — «В мире»: страны нет, флага тоже. Цвет — цвет текста. */
    private const GLOBE = '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 3.9 5.6 3.9 9s-1.3 6.4-3.9 9c-2.6-2.6-3.9-5.6-3.9-9S9.4 5.6 12 3z"/>';

    /**
     * Метка страны разметкой: код, флаг или пусто. `$named` — метка стоит
     * без названия рядом (на карточке), тогда флагу нужна подпись для
     * диктора; рядом с названием флаг — украшение.
     *
     * @param array<string, mixed> $category
     */
    public static function countryMark(array $category, bool $named = false): string
    {
        $mode = self::countryMarkMode();
        if ($mode === 'off') {
            return '';
        }
        $slug = (string) ($category['slug'] ?? '');
        $name = (string) ($category['name'] ?? '');
        if ($mode === 'flags' && ($slug === 'world' || isset(self::FLAGS[$slug]))) {
            $a11y = $named
                ? ' role="img" aria-label="' . htmlspecialchars($name, ENT_QUOTES) . '"><title>' . htmlspecialchars($name, ENT_QUOTES) . '</title>'
                : ' aria-hidden="true" focusable="false">';
            return $slug === 'world'
                ? '<svg class="pt-flag pt-flag--globe" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"' . $a11y . self::GLOBE . '</svg>'
                : '<svg class="pt-flag" viewBox="0 0 30 20"' . $a11y . self::FLAGS[$slug] . '</svg>';
        }

        return htmlspecialchars(self::countryCode($category), ENT_QUOTES);
    }

    /** @param array<string, mixed> $category */
    public static function countryCode(array $category): string
    {
        if (!self::showCountryCodes()) {
            return '';
        }
        $slug = (string) ($category['slug'] ?? '');
        // Коды стран — ISO и на всех языках одни; «МИР» — слово, поэтому идёт
        // через словари публичной части (uz.php, en.php), как любая подпись.
        if ($slug === 'world') {
            return t(self::COUNTRY_CODES['world']);
        }

        return self::COUNTRY_CODES[$slug] ?? mb_strtoupper(mb_substr((string) ($category['name'] ?? ''), 0, 2));
    }

    /**
     * Карточки из строк выборки новостей: адрес, заголовок, дата, анонс,
     * обложка, рубрика и коды стран. Рубрики и страны берутся пакетом на всю
     * выборку, а не запросом на каждую карточку.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public static function cards(array $rows, string $lang): array
    {
        if ($rows === []) {
            return [];
        }
        $rubrics = [];
        $countries = [];
        foreach (NewsCategory::all($lang, true) as $category) {
            $id = (int) $category['id'];
            if (($category['axis'] ?? '') === NewsCategory::AXIS_COUNTRY) {
                $countries[$id] = $category;
            } else {
                $rubrics[$id] = $category;
            }
        }
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $countryIds = NewsCategory::countryIdsFor($ids);

        $cards = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $rubric = $rubrics[(int) ($row['category_id'] ?? 0)] ?? null;
            $codes = [];
            $marks = [];
            foreach ((array) ($countryIds[$id] ?? []) as $countryId) {
                if (isset($countries[(int) $countryId])) {
                    $code = self::countryCode($countries[(int) $countryId]);
                    if ($code !== '') {
                        $codes[] = $code;
                        $marks[] = self::countryMark($countries[(int) $countryId], true);
                    }
                }
            }
            $published = (string) ($row['published_at'] ?? '');
            $cards[] = [
                'id' => $id,
                'url' => Locale::url('news/' . (string) $row['slug']),
                'title' => (string) $row['title'],
                'excerpt' => trim((string) ($row['excerpt'] ?? '')),
                'cover' => (string) (News::getCoverImage($row) ?? ''),
                'focal_x' => isset($row['focal_x']) ? (int) $row['focal_x'] : null,
                'focal_y' => isset($row['focal_y']) ? (int) $row['focal_y'] : null,
                'rubric' => $rubric !== null ? (string) $rubric['name'] : '',
                'rubric_url' => $rubric !== null ? Locale::url(NewsCategory::path($rubric)) : '',
                'countries' => $codes,
                // Те же страны разметкой: код или флаг, по настройке «Дизайна».
                'country_marks' => $marks,
                'badge' => trim((string) ($row['badge'] ?? '')),
                'date' => self::date($published, $lang),
                'time' => self::time($published),
                'day' => $published !== '' ? DateFormatter::format($published, 'd.m') : '',
                'day_full' => $published !== '' ? DateFormatter::format($published, 'd.m.Y') : '',
                'datetime' => $published !== '' ? DateFormatter::format($published, 'c') : '',
                'is_today' => $published !== '' && DateFormatter::format($published, 'Y-m-d') === DateFormatter::format('now', 'Y-m-d'),
                'reading' => self::readingMinutes($row),
            ];
        }

        return $cards;
    }

    /** «14 ноября» без года — год у ленты новостей очевиден. */
    public static function date(string $published, string $lang): string
    {
        if ($published === '') {
            return '';
        }
        $long = DateFormatter::long($published, $lang);
        $year = DateFormatter::format($published, 'Y');
        if ($year !== DateFormatter::format('now', 'Y')) {
            return $long;
        }

        return trim((string) preg_replace('/[,\s]*' . preg_quote($year, '/') . '(-yil| г\.)?$/u', '', $long));
    }

    public static function time(string $published): string
    {
        return $published !== '' ? DateFormatter::format($published, 'H:i') : '';
    }

    /**
     * Время чтения по анонсу и лиду: в списке тела новости нет (LIST_COLUMNS).
     *
     * @param array<string, mixed> $row
     */
    private static function readingMinutes(array $row): int
    {
        $text = strip_tags((string) ($row['content'] ?? $row['lead_html'] ?? $row['excerpt'] ?? ''));
        $words = count(preg_split('/\s+/u', trim($text)) ?: []);

        return max(1, (int) ceil($words / 200));
    }

    /**
     * Секции главной портала: ключ → подпись для редактора. Порядок — порядок
     * главной по умолчанию (её и собирает «Собрать главную из блоков»).
     * Секция — это и часть фиксированной главной, и вариант блока «Секция
     * портала»: разметка у них общая (`portal/_home_<ключ>.php`).
     */
    public const SECTIONS = [
        'lead' => 'Главное в отрасли и лента новостей',
        'rates' => 'Курсы валют',
        'picks' => 'Выбор редакции',
        'ad' => 'Широкий рекламный баннер',
        'regions' => 'Нити региона (по странам)',
        'voices' => 'Точка зрения',
        'subscribe' => 'Подписка',
    ];

    /** Сколько материалов секция показывает, если редактор не задал своё. */
    public const SECTION_LIMITS = ['picks' => 4, 'regions' => 4, 'voices' => 3];

    /**
     * Данные одной секции главной.
     *
     * @return array<string, mixed>
     */
    public static function section(string $name, string $lang, int $limit = 0): array
    {
        $limit = $limit > 0 ? min($limit, 12) : (self::SECTION_LIMITS[$name] ?? 0);
        switch ($name) {
            case 'lead':
                $latest = self::cards(News::published(24, 0, $lang), $lang);

                return [
                    'lead' => $latest[0] ?? null,
                    'grid' => array_slice($latest, 1, 3),
                    'also' => array_slice($latest, 4, 4),
                    'feed' => array_slice($latest, 0, 8),
                ];
            case 'rates':
                return ['rates' => \App\Core\CurrencyRates::current()];
            case 'picks':
                return ['picks' => self::cards(News::editorPicks($limit, $lang), $lang)];
            case 'regions':
                $regions = [];
                foreach (NewsCategory::all($lang, true, NewsCategory::AXIS_COUNTRY) as $country) {
                    $items = self::cards(News::published($limit, 0, $lang, (int) $country['id']), $lang);
                    if ($items === []) {
                        continue;
                    }
                    $regions[] = [
                        'code' => self::countryCode($country),
                        'mark' => self::countryMark($country),
                        'name' => (string) $country['name'],
                        'url' => Locale::url(NewsCategory::path($country)),
                        'items' => $items,
                    ];
                }

                return ['regions' => $regions];
            case 'voices':
                $opinion = NewsCategory::findBySlug(self::OPINION_SLUG, true, NewsCategory::AXIS_RUBRIC);
                if ($opinion === null) {
                    return ['voices' => []];
                }
                $opinion = NewsCategory::localize($opinion, $lang);

                return ['voices' => [
                    'title' => (string) $opinion['name'],
                    'url' => Locale::url(NewsCategory::path($opinion)),
                    'items' => self::cards(News::published($limit, 0, $lang, (int) $opinion['id']), $lang),
                ]];
            default:
                return [];
        }
    }

    /**
     * Все секции фиксированной главной разом.
     *
     * @return array<string, mixed>
     */
    public static function home(string $lang): array
    {
        return self::section('lead', $lang)
            + ['rates' => \App\Core\CurrencyRates::current()]
            + self::section('picks', $lang)
            + self::section('regions', $lang)
            + self::section('voices', $lang);
    }
}
