<?php
/**
 * Верх страницы нового шаблона портала: <head> и шапка.
 *
 * Служебная часть <head> (canonical, hreflang, Open Graph, микроразметка,
 * ранняя тема) — те же механизмы, что у прежнего шаблона: это не оформление,
 * а обязательства перед поиском и соцсетями. Стили — только свои
 * (`portal.css`), бандл прежней темы сюда не подключается.
 *
 * @var string $metaTitle
 * @var string $metaDescription
 * @var string|null $ogImage
 * @var string|null $ogType
 * @var string|null $publishedAt
 * @var string|null $modifiedAt
 * @var string|null $portalNav активный пункт рубрик (адрес)
 */

use App\Core\Asset;
use App\Core\Locale;
use App\Core\Portal\Portal;
use App\Models\Language;
use App\Models\NewsCategory;
use App\Models\Setting;

$currentLang = Locale::current();
$siteName = Setting::getLocalized('site_name', $currentLang, 'LightCA.News');
$appUrl = \App\Core\AppUrl::base();
$metaTitle = trim((string) ($metaTitle ?? ''));
$metaDescription = trim((string) ($metaDescription ?? ''));
if ($metaDescription === '') {
    $metaDescription = Setting::getLocalized('default_meta_description', $currentLang, '');
}
$fullTitle = $metaTitle === '' ? $siteName
    : (str_contains(mb_strtolower($metaTitle), mb_strtolower($siteName)) ? $metaTitle : $metaTitle . ' — ' . $siteName);
$canonicalUrl = \App\Core\SeoHelper::canonicalUrl(
    $appUrl,
    Locale::url(Locale::path()),
    (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY) ?? '')
);
$defaultTheme = Setting::get('default_theme', 'light');
$defaultTheme = in_array($defaultTheme, ['light', 'dark', 'auto'], true) ? $defaultTheme : 'light';

$hrefLangs = Language::site();
$contentLangs = Locale::contentLangs();
if ($contentLangs !== null) {
    $hrefLangs = array_values(array_filter($hrefLangs, static fn (array $l): bool => in_array((string) $l['code'], $contentLangs, true)));
}
$ogAlternate = array_values(array_map(static fn (array $l): string => (string) $l['code'], $hrefLangs));

$defaultOg = Setting::get('default_og_image', '');
$ogImageRaw = (string) (($ogImage ?? '') !== '' ? $ogImage : $defaultOg);
if ($ogImageRaw !== '' && !preg_match('#^https?://#', $ogImageRaw)) {
    $ogImageRaw = $appUrl . $ogImageRaw;
}

// Цвета приходят из «Дизайна»: акцент и основной цвет настраиваются в
// админке, здесь — только их запасные значения на случай пустой настройки.
$brand = \App\Core\SettingsValidator::hexColor(Setting::get('color_accent', ''), \App\Core\DesignSettings::DEFAULT_ACCENT);
$brandDeep = \App\Core\SettingsValidator::hexColor(Setting::get('color_primary', ''), \App\Core\DesignSettings::DEFAULT_PRIMARY);

$homeUrl = Locale::url('/', $currentLang);
// Подпись, знак, меню и что показывать — из конструктора шапки (вид «Портал»);
// раскладку задаёт шаблон прототипа, зоны конструктора здесь не действуют.
$hdrCfg = \App\Core\HeaderConfig::get();
$hdrPortal = (array) ($hdrCfg['portal'] ?? []);
$hdrShow = static fn (string $key): bool => !array_key_exists($key, $hdrPortal) || !empty($hdrPortal[$key]);
$tagline = trim((string) ($hdrPortal['tagline'] ?? ''));
$tagline = $tagline !== '' ? $tagline : t('Новости лёгкой промышленности Центральной Азии');
$hdrLogo = '';
if (($hdrPortal['logo_mode'] ?? 'text') === 'image') {
    $hdrLogo = trim((string) ($hdrCfg['logo_by_lang'][$currentLang] ?? ''));
    $hdrLogo = $hdrLogo !== '' ? $hdrLogo : trim((string) Setting::get('logo_url', ''));
}
$hdrLiveUrl = (string) ($hdrPortal['live_url'] ?? '');
$hdrLiveText = trim((string) ($hdrPortal['live_text'] ?? ''));
$hdrLiveText = $hdrLiveText !== '' ? $hdrLiveText : t('Онлайн');
$hdrCta = (array) ($hdrCfg['cta'] ?? []);
$hdrCtaUrl = trim((string) ($hdrCta['url'] ?? ''));
$hdrCtaText = trim((string) ($hdrCta['text'] ?? ''));
$hdrCtaOn = !empty($hdrCta['enabled']) && $hdrCtaText !== '' && $hdrCtaUrl !== '' && \App\Core\UrlGuard::isSafeLink($hdrCtaUrl);
// Пункты меню: рубрики, основное меню или оба (основное — только верхний уровень).
$hdrNavMode = (string) ($hdrPortal['nav'] ?? 'rubrics');
$hdrNav = [];
if ($hdrNavMode !== 'rubrics') {
    try {
        foreach (\App\Models\MenuItem::activeForLang($currentLang) as $hdrItem) {
            if (empty($hdrItem['is_divider']) && empty($hdrItem['parent_id'])) {
                $hdrNav[] = [(string) $hdrItem['title'], \App\Models\MenuItem::resolveUrl($hdrItem, $currentLang)];
            }
        }
    } catch (\Throwable $e) {
        \App\Core\Logger::swallowed('Шапка портала: меню не загрузилось', $e);
    }
}
$wordmark = trim(Setting::get('pwa_short_name', ''));
$wordmark = $wordmark !== '' ? $wordmark : (string) preg_replace('/\.news$/i', '', $siteName);

$rubrics = NewsCategory::all($currentLang, true, NewsCategory::AXIS_RUBRIC);
if ($hdrNavMode !== 'menu') {
    foreach ($rubrics as $rubric) {
        $hdrNav[] = [(string) $rubric['name'], Locale::url(NewsCategory::path($rubric))];
    }
}
$countries = $hdrShow('show_countries') ? NewsCategory::all($currentLang, true, NewsCategory::AXIS_COUNTRY) : [];
$requestPath = rtrim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
$isCurrent = static function (string $url) use ($requestPath): bool {
    return $requestPath !== '' && $requestPath === rtrim((string) parse_url($url, PHP_URL_PATH), '/');
};

$uzScript = \App\Core\A11ySettings::fromCookie($_COOKIE[\App\Core\A11ySettings::COOKIE] ?? null)['script'];
$langLinks = [];
foreach (Language::site() as $language) {
    $code = (string) $language['code'];
    $langLinks[] = [
        'code' => $code,
        'url' => (static function (string $code) use ($uzScript): string {
            // `?_lang=` перезаписывает запомненный язык (cookie site_lang):
            // без него ссылка на основной язык (без префикса) уводила обратно
            // на язык из cookie. Материала нет на выбранном языке — адрес тот
            // же, и страница сама скажет, что перевода нет.
            $href = Locale::url(Locale::alternatePath($code), $code) . '?' . \App\Core\LocalePreference::QUERY . '=' . rawurlencode($code);
            // Выбранная посетителем письменность главнее умолчания сайта.
            return $code === 'uz' && \App\Core\UzCyrillic::enabled() ? '/script/' . $uzScript . '?to=' . rawurlencode($href) : $href;
        })($code),
        // Подпись узбекского — в той письменности, в которой он откроется.
        'label' => $code === 'uz' && $uzScript === 'cyrl' ? \App\Core\UzCyrillic::text(Language::shortLabel($language)) : Language::shortLabel($language),
        'current' => $code === $currentLang,
    ];
}
// Переключатель письменности — на узбекских страницах: кириллица по умолчанию,
// латиница по выбору. Подпись не транслитерируется (data-no-translit), иначе
// «Lotincha» на кириллической странице стала бы «Лотинча».
$scriptLink = null;
if ($currentLang === 'uz' && \App\Core\UzCyrillic::enabled()) {
    $here = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $scriptLink = $uzScript === 'cyrl'
        ? ['url' => '/script/latn?to=' . rawurlencode($here), 'label' => 'Lotincha']
        : ['url' => '/script/cyrl?to=' . rawurlencode($here), 'label' => 'Кириллча'];
}
$currentLangLabel = '';
foreach ($langLinks as $langLink) {
    if ($langLink['current']) {
        $currentLangLabel = $langLink['label'];
    }
}
?>
<!DOCTYPE html>
<?php
// Настройки отображения из cookie: атрибуты печатает сервер, чтобы страница
// сразу пришла крупной или контрастной, без мигания.
$a11ySettings = \App\Core\A11ySettings::fromCookie($_COOKIE[\App\Core\A11ySettings::COOKIE] ?? null);
?>
<html lang="<?= htmlspecialchars($currentLang, ENT_QUOTES) ?>" data-theme="<?= htmlspecialchars($defaultTheme, ENT_QUOTES) ?>" <?= \App\Core\A11ySettings::htmlAttributes($a11ySettings) ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<script src="<?= htmlspecialchars(Asset::url('/assets/js/theme-init.js'), ENT_QUOTES) ?>"></script>
<?= \App\Core\SeoHelper::faviconsHtml($appUrl) ?>
<?= \App\Core\Favicon::settingTag(Setting::get('favicon_url', '')) ?>
<?php $ptThemeColor = Setting::get('theme_color', ''); ?>
<?php if ($ptThemeColor !== ''): ?><meta name="theme-color" content="<?= htmlspecialchars($ptThemeColor, ENT_QUOTES) ?>">
<?php endif; ?>
<title><?= htmlspecialchars($fullTitle, ENT_QUOTES) ?></title>
<?php if (!empty($robotsNoindex)): ?>
<meta name="robots" content="noindex, nofollow">
<?php else: ?>
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<?php endif; ?>
<?php if ($metaDescription !== ''): ?>
<meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES) ?>">
<?php endif; ?>
<link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES) ?>">
<?php if (!empty($pagerPrev)): ?>
<link rel="prev" href="<?= htmlspecialchars($appUrl . $pagerPrev, ENT_QUOTES) ?>">
<?php endif; ?>
<?php if (!empty($pagerNext)): ?>
<link rel="next" href="<?= htmlspecialchars($appUrl . $pagerNext, ENT_QUOTES) ?>">
<?php endif; ?>
<?php if (count($hrefLangs) > 1): ?>
<?php foreach ($hrefLangs as $hrefLang): ?>
<link rel="alternate" hreflang="<?= htmlspecialchars((string) $hrefLang['code'], ENT_QUOTES) ?>" href="<?= htmlspecialchars($appUrl . Locale::url(Locale::alternatePath((string) $hrefLang['code']), (string) $hrefLang['code']), ENT_QUOTES) ?>">
<?php endforeach; ?>
<?php $xDefault = Language::defaultCode(); ?>
<link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($appUrl . Locale::url(Locale::alternatePath($xDefault), $xDefault), ENT_QUOTES) ?>">
<?php endif; ?>
<link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars($siteName . ' — ' . t('Новости'), ENT_QUOTES) ?>" href="<?= htmlspecialchars(Locale::url('news/rss.xml', $currentLang), ENT_QUOTES) ?>">
<?= \App\Core\OpenGraphHelper::render($appUrl, $metaTitle !== '' ? $metaTitle : $siteName, $metaDescription, $canonicalUrl, $currentLang, $ogImageRaw, (string) ($ogType ?? 'website'), $publishedAt ?? null, $modifiedAt ?? null, $ogAlternate) ?>
<?= \App\Core\SeoHelper::organizationSchema($appUrl) ?>
<?php if (Locale::path() === '' || Locale::path() === '/'): ?>
<?= \App\Core\SeoHelper::websiteSchema($appUrl, $siteName) ?>
<?php endif; ?>
<link rel="stylesheet" href="<?= htmlspecialchars(Asset::url('/assets/css/medeo-sans.css'), ENT_QUOTES) ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(Asset::url('/assets/css/noto-serif.css'), ENT_QUOTES) ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(Asset::url('/assets/portal/portal.css'), ENT_QUOTES) ?>">
<?php
// Ширина сайта и колонки ленты — из «Дизайна»: значения уже нормализованы
// (DesignSettings::containerWidth/portalFeedWidth), в CSS идут как есть.
$portalWrap = \App\Core\DesignSettings::containerWidth();
$portalFeed = \App\Core\DesignSettings::portalFeedWidth();
$logoPlate = \App\Core\DesignSettings::logoPlate();
?>
<style>:root{--brand:<?= $brand ?>;--brand-deep:<?= $brandDeep ?>;--wrap:<?= htmlspecialchars($portalWrap, ENT_QUOTES) ?><?= $portalFeed !== '' ? ';--pt-feed-w:' . htmlspecialchars($portalFeed, ENT_QUOTES) : '' ?><?= $logoPlate !== '' ? ';--logo-plate:' . $logoPlate : '' ?>}</style>
<?php if (!empty($portalBlocks)): ?>
<?php // Страница из блоков конструктора: свои стили блоков портала, затем
      // собственные файлы типов на странице и scoped CSS блоков. ?>
<link rel="stylesheet" href="<?= htmlspecialchars(Asset::url('/assets/css/tokens.css'), ENT_QUOTES) ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(Asset::url('/assets/portal/blocks.css'), ENT_QUOTES) ?>">
<?= \App\Core\AssetCollector::renderThemeStyles() ?>
<?= \App\Core\AssetCollector::renderStyles() ?>
<link rel="stylesheet" href="<?= htmlspecialchars(Asset::url('/assets/portal/blocks-after.css'), ENT_QUOTES) ?>">
<?php $portalBlockCss = trim((string) ($blockCss ?? '')); ?>
<?php $portalBlockCssUrl = $portalBlockCss !== '' ? \App\Core\GeneratedCss::publish($portalBlockCss, 'page') : null; ?>
<?php if ($portalBlockCssUrl !== null): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($portalBlockCssUrl, ENT_QUOTES) ?>">
<?php endif; ?>
<?php endif; ?>
<link rel="stylesheet" href="<?= htmlspecialchars(Asset::url('/assets/css/a11y.css'), ENT_QUOTES) ?>">
<?php if (!empty($page['custom_css']) && !empty($page['id'])): ?>
<?php
// «Свой CSS» страницы (супер-админ): файлом, чужой домен — в CSP.
$pageCssUrls = \App\Core\CustomAssetHelper::resolveCssUrls((string) $page['custom_css'], (int) $page['id']);
\App\Core\SecurityHeaders::allowPageAssets(\App\Core\CustomAssetHelper::originsOf($pageCssUrls), []);
?>
<?php foreach ($pageCssUrls as $pageCssUrl): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($pageCssUrl, ENT_QUOTES) ?>" data-page-custom-css>
<?php endforeach; ?>
<?php endif; ?>
</head>
<?php $ptAdminBar = \App\Core\AppToolbar::isVisible(); ?>
<body class="pt<?= $ptAdminBar ? ' has-admin-bar' : '' ?><?= !empty($previewNotice) ? ' is-preview' : '' ?>">
<?= $ptAdminBar ? \App\Core\AppToolbar::renderHtml(get_defined_vars()) : '' ?>
<?php if (!empty($previewNotice)): ?>
<div class="pt-preview" role="status"><?= \App\Core\Icon::render('eye', 18) ?> <?= htmlspecialchars(t('Режим предпросмотра — эта версия не опубликована и закрыта от индексации.'), ENT_QUOTES) ?></div>
<?php endif; ?>
<a class="pt-skip skip-link" href="#main-content"><?= htmlspecialchars(t('Перейти к содержанию'), ENT_QUOTES) ?></a>

<header class="pt-mast">
    <div class="pt-mast__hem" aria-hidden="true"></div>
    <div class="pt-wrap pt-mast__row">
        <a class="pt-logo" href="<?= htmlspecialchars($homeUrl, ENT_QUOTES) ?>" aria-label="<?= htmlspecialchars($siteName . ' — ' . t('на главную'), ENT_QUOTES) ?>">
            <?php if ($hdrLogo !== ''): ?>
                <img class="pt-logo__img" src="<?= htmlspecialchars($hdrLogo, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($siteName, ENT_QUOTES) ?>">
            <?php else: ?>
                <span class="pt-logo__word"><?= htmlspecialchars($wordmark, ENT_QUOTES) ?></span>
            <?php endif; ?>
            <span class="pt-logo__desc"><?= htmlspecialchars($tagline, ENT_QUOTES) ?></span>
        </a>
        <div class="pt-mast__bar">
            <nav class="pt-nav" aria-label="<?= htmlspecialchars($hdrNavMode === 'rubrics' ? t('Рубрики') : t('Меню'), ENT_QUOTES) ?>">
                <?php foreach ($hdrNav as [$navTitle, $navUrl]): ?>
                    <a href="<?= htmlspecialchars($navUrl, ENT_QUOTES) ?>"<?= $isCurrent($navUrl) ? ' aria-current="page"' : '' ?>><?= htmlspecialchars($navTitle, ENT_QUOTES) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="pt-tools">
                <?php if ($hdrLiveUrl !== ''): ?>
                    <a class="pt-btn pt-btn--live" href="<?= htmlspecialchars($hdrLiveUrl, ENT_QUOTES) ?>"><span class="pt-live-dot" aria-hidden="true"></span><?= htmlspecialchars($hdrLiveText, ENT_QUOTES) ?></a>
                <?php endif; ?>
                <?php if ($hdrCtaOn): ?>
                    <a class="pt-btn<?= ($hdrCta['style'] ?? 'filled') === 'outline' ? '' : ' pt-btn--cta' ?>" href="<?= htmlspecialchars($hdrCtaUrl, ENT_QUOTES) ?>"><?= htmlspecialchars(t($hdrCtaText), ENT_QUOTES) ?></a>
                <?php endif; ?>
                <?php if ($hdrShow('show_lang') && count($langLinks) > 1): ?>
                <details class="pt-lang">
                    <summary class="pt-btn" aria-label="<?= htmlspecialchars(t('Язык'), ENT_QUOTES) ?>"><?= htmlspecialchars($currentLangLabel, ENT_QUOTES) ?></summary>
                    <ul class="pt-lang__menu">
                        <?php foreach ($langLinks as $langLink): ?>
                            <li><a href="<?= htmlspecialchars($langLink['url'], ENT_QUOTES) ?>" hreflang="<?= htmlspecialchars($langLink['code'], ENT_QUOTES) ?>"<?= $langLink['current'] ? ' aria-current="true"' : '' ?>><?= htmlspecialchars($langLink['label'], ENT_QUOTES) ?></a></li>
                        <?php endforeach; ?>
                        <?php if ($scriptLink !== null): ?>
                            <li><a href="<?= htmlspecialchars($scriptLink['url'], ENT_QUOTES) ?>" rel="nofollow" data-no-translit><?= htmlspecialchars($scriptLink['label'], ENT_QUOTES) ?></a></li>
                        <?php endif; ?>
                    </ul>
                </details>
                <?php endif; ?>
                <?php if ($hdrShow('show_search')): ?>
                <a class="pt-btn pt-btn--search" href="<?= htmlspecialchars(Locale::url('search'), ENT_QUOTES) ?>" data-pt-search aria-label="<?= htmlspecialchars(t('Поиск по сайту'), ENT_QUOTES) ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <span><?= htmlspecialchars(t('Поиск'), ENT_QUOTES) ?></span>
                </a>
                <?php endif; ?>
                <?php if ($hdrShow('show_a11y')): ?>
                <button type="button" class="pt-btn pt-btn--icon a11y-toggle<?= \App\Core\A11ySettings::isActive($a11ySettings) ? ' is-active' : '' ?>" data-a11y-open aria-controls="a11y-panel" aria-expanded="false" aria-label="<?= htmlspecialchars(t('Настройки отображения'), ENT_QUOTES) ?>" title="<?= htmlspecialchars(t('Настройки отображения: размер текста, контраст, интервалы'), ENT_QUOTES) ?>"><?= \App\Core\Icon::render('eye', 18) ?></button>
                <?php endif; ?>
                <?php if ($hdrShow('show_theme')): ?>
                <button type="button" class="pt-btn pt-btn--icon" data-pt-theme aria-label="<?= htmlspecialchars(t('Тёмная тема'), ENT_QUOTES) ?>">
                    <svg class="pt-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.9 13.5A9 9 0 0 1 10.5 3.1 9 9 0 1 0 20.9 13.5Z"/></svg>
                    <svg class="pt-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2"/></svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($countries !== []): ?>
        <nav class="pt-countries" aria-label="<?= htmlspecialchars(t('Страны'), ENT_QUOTES) ?>">
            <span class="pt-countries__label"><?= htmlspecialchars(t('Страны'), ENT_QUOTES) ?></span>
            <?php foreach ($countries as $country): ?>
                <?php $countryUrl = Locale::url(NewsCategory::path($country)); ?>
                <a href="<?= htmlspecialchars($countryUrl, ENT_QUOTES) ?>"<?= $isCurrent($countryUrl) ? ' aria-current="page"' : '' ?>><?php $countryMark = Portal::countryMark($country); ?><?php if ($countryMark !== ''): ?><b><?= $countryMark ?></b><?php endif; ?><?= htmlspecialchars((string) $country['name'], ENT_QUOTES) ?></a>
            <?php endforeach; ?>
            <?php $ptWeather = $hdrShow('show_weather') ? \App\Core\Weather::current((string) ($hdrPortal['weather_city'] ?? '')) : null; ?>
            <?php $ptWeatherFormat = (string) ($hdrPortal['weather_format'] ?? 'full'); ?>
            <?php if ($hdrShow('show_date') || $ptWeather !== null): ?><span class="pt-countries__meta">
            <?php if ($hdrShow('show_date')): ?><span class="pt-countries__date"><?= htmlspecialchars(Portal::headerDate((string) ($hdrPortal['date_format'] ?? 'short'), $currentLang), ENT_QUOTES) ?></span><?php endif; ?>
            <?php if ($ptWeather !== null): ?><span class="pt-countries__weather<?= $hdrShow('show_date') ? '' : ' pt-countries__weather--solo' ?>" title="<?= htmlspecialchars(t($ptWeather['city']) . ': ' . t($ptWeather['label']), ENT_QUOTES) ?>"><?php if ($ptWeatherFormat === 'full'): ?><?= htmlspecialchars(t($ptWeather['city']), ENT_QUOTES) ?> <?php else: ?><span class="pt-visually-hidden"><?= htmlspecialchars(t($ptWeather['city']), ENT_QUOTES) ?>,</span><?php endif; ?><?php if ($ptWeatherFormat !== 'temp'): ?><?= \App\Core\Icon::render($ptWeather['icon'], 16, 'pt-countries__wicon') ?><?php endif; ?><span class="pt-visually-hidden"><?= htmlspecialchars(t($ptWeather['label']), ENT_QUOTES) ?>,</span> <b><?= \App\Core\Weather::format($ptWeather['temp']) ?></b></span><?php endif; ?>
            </span><?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</header>

<dialog class="pt-search" id="site-quick-search-modal" aria-label="<?= htmlspecialchars(t('Поиск по сайту'), ENT_QUOTES) ?>">
    <form class="pt-search__box" action="<?= htmlspecialchars(Locale::url('search'), ENT_QUOTES) ?>" method="get" role="search">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" id="site-quick-search-input" name="q" minlength="2" required autocomplete="off" placeholder="<?= htmlspecialchars(t('Поиск по статьям, рубрикам и тегам'), ENT_QUOTES) ?>" aria-label="<?= htmlspecialchars(t('Поиск по сайту'), ENT_QUOTES) ?>">
        <button type="button" class="pt-search__close" data-pt-search-close>Esc</button>
    </form>
</dialog>

<?php $adKey = 'top'; require __DIR__ . '/_ad.php'; ?>

<main id="main-content">
<?php foreach (\App\Core\Flash::pull() as $flash): ?>
<div class="pt-wrap pt-alert pt-alert--<?= htmlspecialchars((string) $flash['type'], ENT_QUOTES) ?>" role="<?= ($flash['type'] ?? '') === 'error' ? 'alert' : 'status' ?>"><?= htmlspecialchars((string) $flash['message'], ENT_QUOTES) ?></div>
<?php endforeach; ?>
