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
$brand = \App\Core\SettingsValidator::hexColor(Setting::get('color_accent', ''), '#005650');
$brandDeep = \App\Core\SettingsValidator::hexColor(Setting::get('color_primary', ''), '#0b4b42');

$homeUrl = Locale::url('/', $currentLang);
$tagline = t('Новости лёгкой промышленности Центральной Азии');
$wordmark = trim(Setting::get('pwa_short_name', ''));
$wordmark = $wordmark !== '' ? $wordmark : (string) preg_replace('/\.news$/i', '', $siteName);

$rubrics = NewsCategory::all($currentLang, true, NewsCategory::AXIS_RUBRIC);
$countries = NewsCategory::all($currentLang, true, NewsCategory::AXIS_COUNTRY);
$requestPath = rtrim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
$isCurrent = static function (string $url) use ($requestPath): bool {
    return $requestPath !== '' && $requestPath === rtrim((string) parse_url($url, PHP_URL_PATH), '/');
};

$langLinks = [];
foreach (Language::site() as $language) {
    $code = (string) $language['code'];
    $langLinks[] = [
        'code' => $code,
        'label' => Language::shortLabel($language),
        'url' => Locale::url(Locale::alternatePath($code), $code),
        'current' => $code === $currentLang,
    ];
}
$currentLangLabel = '';
foreach ($langLinks as $langLink) {
    if ($langLink['current']) {
        $currentLangLabel = $langLink['label'];
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLang, ENT_QUOTES) ?>" data-theme="<?= htmlspecialchars($defaultTheme, ENT_QUOTES) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<script src="<?= htmlspecialchars(Asset::url('/assets/js/theme-init.js'), ENT_QUOTES) ?>"></script>
<?= \App\Core\SeoHelper::faviconsHtml($appUrl) ?>
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
<style>:root{--brand:<?= $brand ?>;--brand-deep:<?= $brandDeep ?>}</style>
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
</head>
<body class="pt">
<a class="pt-skip skip-link" href="#main-content"><?= htmlspecialchars(t('Перейти к содержанию'), ENT_QUOTES) ?></a>

<header class="pt-mast">
    <div class="pt-mast__hem" aria-hidden="true"></div>
    <div class="pt-wrap pt-mast__row">
        <a class="pt-logo" href="<?= htmlspecialchars($homeUrl, ENT_QUOTES) ?>" aria-label="<?= htmlspecialchars($siteName . ' — ' . t('на главную'), ENT_QUOTES) ?>">
            <span class="pt-logo__word"><?= htmlspecialchars($wordmark, ENT_QUOTES) ?></span>
            <span class="pt-logo__desc"><?= htmlspecialchars($tagline, ENT_QUOTES) ?></span>
        </a>
        <div class="pt-mast__bar">
            <nav class="pt-nav" aria-label="<?= htmlspecialchars(t('Рубрики'), ENT_QUOTES) ?>">
                <?php foreach ($rubrics as $rubric): ?>
                    <?php $rubricUrl = Locale::url(NewsCategory::path($rubric)); ?>
                    <a href="<?= htmlspecialchars($rubricUrl, ENT_QUOTES) ?>"<?= $isCurrent($rubricUrl) ? ' aria-current="page"' : '' ?>><?= htmlspecialchars((string) $rubric['name'], ENT_QUOTES) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="pt-tools">
                <?php if (count($langLinks) > 1): ?>
                <details class="pt-lang">
                    <summary class="pt-btn" aria-label="<?= htmlspecialchars(t('Язык'), ENT_QUOTES) ?>"><?= htmlspecialchars($currentLangLabel, ENT_QUOTES) ?></summary>
                    <ul class="pt-lang__menu">
                        <?php foreach ($langLinks as $langLink): ?>
                            <li><a href="<?= htmlspecialchars($langLink['url'], ENT_QUOTES) ?>" hreflang="<?= htmlspecialchars($langLink['code'], ENT_QUOTES) ?>"<?= $langLink['current'] ? ' aria-current="true"' : '' ?>><?= htmlspecialchars($langLink['label'], ENT_QUOTES) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
                <?php endif; ?>
                <a class="pt-btn pt-btn--search" href="<?= htmlspecialchars(Locale::url('search'), ENT_QUOTES) ?>" data-pt-search aria-label="<?= htmlspecialchars(t('Поиск по сайту'), ENT_QUOTES) ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <span><?= htmlspecialchars(t('Поиск'), ENT_QUOTES) ?></span>
                </a>
                <button type="button" class="pt-btn pt-btn--icon" data-pt-theme aria-label="<?= htmlspecialchars(t('Тёмная тема'), ENT_QUOTES) ?>">
                    <svg class="pt-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.9 13.5A9 9 0 0 1 10.5 3.1 9 9 0 1 0 20.9 13.5Z"/></svg>
                    <svg class="pt-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2"/></svg>
                </button>
            </div>
        </div>
        <?php if ($countries !== []): ?>
        <nav class="pt-countries" aria-label="<?= htmlspecialchars(t('Страны'), ENT_QUOTES) ?>">
            <span class="pt-countries__label"><?= htmlspecialchars(t('Страны'), ENT_QUOTES) ?></span>
            <?php foreach ($countries as $country): ?>
                <?php $countryUrl = Locale::url(NewsCategory::path($country)); ?>
                <a href="<?= htmlspecialchars($countryUrl, ENT_QUOTES) ?>"<?= $isCurrent($countryUrl) ? ' aria-current="page"' : '' ?>><b><?= htmlspecialchars(Portal::countryCode($country), ENT_QUOTES) ?></b><?= htmlspecialchars((string) $country['name'], ENT_QUOTES) ?></a>
            <?php endforeach; ?>
            <span class="pt-countries__date"><?= htmlspecialchars(\App\Core\DateFormatter::long('now', $currentLang), ENT_QUOTES) ?></span>
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
