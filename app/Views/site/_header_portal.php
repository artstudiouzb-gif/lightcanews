<?php
/**
 * Шапка портала: плашка логотипа висит на кромке и выступает за низ шапки,
 * справа — рубрики (верхний уровень основного меню) и инструменты, ниже —
 * полоса стран. Переменные приходят из _header.php: меню, языки, поиск и тема
 * собираются там одним кодом для обоих видов шапки.
 *
 * @var array<string, mixed> $hcfg
 * @var list<array<string, mixed>> $menuItems
 * @var list<array<string, mixed>> $countryRows
 * @var string $currentLang
 * @var string $currentReqUri
 * @var string $logo
 * @var string $siteName
 * @var string $langHtml
 * @var string $overlaySearchHtml
 * @var string $themeToggle
 * @var string $drawerMenu
 * @var callable(string): bool $isNavUrlActive
 */

use App\Core\Icon;
use App\Core\Locale;
use App\Models\MenuItem;
use App\Models\NewsCategory;

$portal = (array) ($hcfg['portal'] ?? []);
$tagline = trim((string) ($portal['tagline'] ?? ''));
$tagline = $tagline !== '' ? $tagline : t('Новости лёгкой промышленности Центральной Азии');
$liveUrl = trim((string) ($portal['live_url'] ?? ''));
$liveText = trim((string) ($portal['live_text'] ?? ''));
$liveText = $liveText !== '' ? $liveText : t('Онлайн');
$homeUrl = Locale::url('/', $currentLang);
// В плашку идёт короткое имя (оно же у PWA): полное название ведомства или
// издания в квадрат шириной 176px не помещается. Длинному имени — кегль меньше.
$wordmark = trim((string) \App\Models\Setting::get('pwa_short_name', ''));
$wordmark = $wordmark !== '' ? $wordmark : $siteName;
$wordmarkClass = mb_strlen($wordmark) > 9 ? ' phdr__logo-word--long' : '';

// Рубрики — первая ось разделов портала, как страны — вторая: обе строки
// шапки собираются из категорий, и новая рубрика появляется в шапке сама.
// Нет рубрик — верхний уровень основного меню. Основное меню целиком живёт
// в выдвижной панели (кнопка «Меню»).
$navLinks = [];
foreach (NewsCategory::all($currentLang, true, NewsCategory::AXIS_RUBRIC) as $rubricRow) {
    $navLinks[] = [(string) $rubricRow['name'], Locale::url(NewsCategory::path($rubricRow))];
}
if ($navLinks === []) {
    foreach ($menuItems as $item) {
        $navLinks[] = [(string) $item['title'], MenuItem::resolveUrl($item, $currentLang)];
    }
}

// Код страны на ярлыке: адрес ленты и подпись у страны свои на каждом языке,
// а код один — по нему страну узнают в любой из трёх версий сайта.
$countryCodes = [
    'uzbekistan' => 'UZ',
    'kazakhstan' => 'KZ',
    'kyrgyzstan' => 'KG',
    'tajikistan' => 'TJ',
    'turkmenistan' => 'TM',
];
?>
<header class="phdr<?= !empty($hcfg['sticky']) ? ' phdr--sticky' : '' ?>">
    <div class="phdr__hem" aria-hidden="true"></div>
    <div class="phdr__grid">
        <a class="phdr__logo" href="<?= htmlspecialchars($homeUrl, ENT_QUOTES) ?>" aria-label="<?= htmlspecialchars($siteName . ' — ' . t('на главную'), ENT_QUOTES) ?>">
            <?php if ($logo !== ''): ?>
                <img class="phdr__logo-img" src="<?= htmlspecialchars($logo, ENT_QUOTES) ?>" alt="" height="44">
            <?php else: ?>
                <span class="phdr__logo-word<?= $wordmarkClass ?>"><?= htmlspecialchars($wordmark, ENT_QUOTES) ?></span>
            <?php endif; ?>
            <span class="phdr__logo-desc"><?= htmlspecialchars($tagline, ENT_QUOTES) ?></span>
        </a>

        <div class="phdr__bar">
            <?php if ($navLinks !== []): ?>
            <nav class="phdr__nav" aria-label="<?= htmlspecialchars(t('Рубрики'), ENT_QUOTES) ?>">
                <?php foreach ($navLinks as [$navTitle, $navUrl]): ?>
                    <a href="<?= htmlspecialchars($navUrl, ENT_QUOTES) ?>"<?= $isNavUrlActive($navUrl) ? ' aria-current="page"' : '' ?>><?= htmlspecialchars($navTitle, ENT_QUOTES) ?></a>
                <?php endforeach; ?>
            </nav>
            <?php endif; ?>
            <div class="phdr__tools">
                <?php if ($liveUrl !== '' && \App\Core\UrlGuard::isSafeLink($liveUrl)): ?>
                    <a class="phdr__live" href="<?= htmlspecialchars($liveUrl, ENT_QUOTES) ?>"><span class="phdr__live-dot" aria-hidden="true"></span><span class="phdr__live-text"><?= htmlspecialchars($liveText, ENT_QUOTES) ?></span></a>
                <?php endif; ?>
                <?= $langHtml ?>
                <?= $overlaySearchHtml ?>
                <?= $themeToggle ?>
                <?php if ($drawerMenu !== ''): ?>
                    <button type="button" class="phdr__menu" data-mobile-menu-toggle aria-label="<?= htmlspecialchars(t('Меню'), ENT_QUOTES) ?>" aria-controls="site-drawer" aria-expanded="false"><?= Icon::render('menu-2', 18) ?></button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($countryRows !== []): ?>
        <nav class="phdr__countries" aria-label="<?= htmlspecialchars(t('Страны'), ENT_QUOTES) ?>">
            <span class="phdr__countries-label"><?= htmlspecialchars(t('Страны'), ENT_QUOTES) ?></span>
            <?php foreach ($countryRows as $countryRow): ?>
                <?php
                $countryUrl = Locale::url(NewsCategory::path($countryRow));
                $isCurrent = rtrim($currentReqUri, '/') === rtrim((string) parse_url($countryUrl, PHP_URL_PATH), '/');
                $code = $countryCodes[(string) ($countryRow['slug'] ?? '')] ?? '';
                ?>
                <a href="<?= htmlspecialchars($countryUrl, ENT_QUOTES) ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>>
                    <span class="phdr__code" aria-hidden="true"><?= $code !== '' ? htmlspecialchars($code, ENT_QUOTES) : Icon::render('world', 13) ?></span><?= htmlspecialchars((string) $countryRow['name'], ENT_QUOTES) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
    </div>
</header>
