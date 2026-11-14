<?php
/**
 * Лента новостей портала: общая (/news), рубрика (/rubric/<slug>) и страна
 * (/country/<slug>). Первая новость первой страницы идёт крупно, остальные —
 * сеткой карточек; страницы листаются обычными ссылками.
 *
 * @var list<array<string, mixed>> $items
 * @var int $page
 * @var int $pages
 * @var list<array<string, mixed>> $categories рубрики с опубликованными новостями
 * @var list<array<string, mixed>> $countries  страны с опубликованными новостями
 * @var array<string, mixed>|null $section     открытый раздел
 * @var string $basePath
 * @var array<string, mixed>|null $sectionPage шапка раздела из админки
 */

use App\Core\Locale;
use App\Core\Pager;
use App\Core\Portal\Portal;
use App\Models\NewsCategory;

$lang = Locale::current();
$section = $section ?? null;
$sectionPage = $sectionPage ?? null;
$sectionTitle = $section !== null ? (string) $section['name'] : (string) ($sectionPage['title'] ?? '');
$sectionTitle = $sectionTitle !== '' ? $sectionTitle : t('Новости');
$sectionLead = trim((string) ($sectionPage['lead'] ?? ''));
$metaTitle = (string) (($sectionPage['meta_title'] ?? '') !== '' ? $sectionPage['meta_title'] : $sectionTitle);
$metaDescription = (string) (($sectionPage['meta_description'] ?? '') !== '' ? $sectionPage['meta_description'] : $sectionLead);
$blockCss = (string) ($sectionPage['css'] ?? '');
$sectionBlocks = trim((string) ($sectionPage['content'] ?? ''));
$portalBlocks = $sectionBlocks !== '';

$pageUrl = static fn (int $p): string => Locale::url($basePath) . ($p > 1 ? '?page=' . $p : '');
['prev' => $pagerPrev, 'next' => $pagerNext] = Pager::relLinks($page, $pages, $pageUrl);
if ($page > 1) {
    $metaTitle .= ' — ' . sprintf(t('страница %d'), $page);
}

$cards = Portal::cards($items, $lang);
$lead = $page === 1 ? array_shift($cards) : null;
$isCountry = $section !== null && ($section['axis'] ?? '') === NewsCategory::AXIS_COUNTRY;
$filters = $isCountry ? $countries : $categories;
$sectionId = $section !== null ? (int) $section['id'] : 0;

$crumbs = [['label' => t('Главная'), 'url' => Locale::url('/')]];
if ($section === null) {
    $crumbs[] = ['label' => t('Новости')];
} else {
    $crumbs[] = ['label' => t('Новости'), 'url' => Locale::url('news')];
    $crumbs[] = ['label' => (string) $section['name']];
}

require __DIR__ . '/_top.php';
?>
<div class="pt-wrap pt-sec pt-listing">
    <?php require __DIR__ . '/_crumbs.php'; ?>
    <header class="pt-sec-head pt-sec-head--page">
        <h1><?php if ($isCountry): ?><span class="pt-code pt-code--lg"><?= htmlspecialchars(Portal::countryCode($section), ENT_QUOTES) ?></span> <?php endif; ?><?= htmlspecialchars($sectionTitle, ENT_QUOTES) ?></h1>
    </header>
    <?php if ($sectionLead !== ''): ?><p class="pt-listing__lead"><?= htmlspecialchars($sectionLead, ENT_QUOTES) ?></p><?php endif; ?>

    <?php if (count($filters) > 1): ?>
    <nav class="pt-filters pt-filters--links" aria-label="<?= htmlspecialchars($isCountry ? t('Страны') : t('Рубрики'), ENT_QUOTES) ?>">
        <?php if (!$isCountry): ?>
            <a href="<?= htmlspecialchars(Locale::url('news'), ENT_QUOTES) ?>"<?= $section === null ? ' aria-current="page"' : '' ?>><?= htmlspecialchars(t('Все материалы'), ENT_QUOTES) ?></a>
        <?php endif; ?>
        <?php foreach ($filters as $filter): ?>
            <a href="<?= htmlspecialchars(Locale::url(NewsCategory::path($filter)), ENT_QUOTES) ?>"<?= (int) $filter['id'] === $sectionId ? ' aria-current="page"' : '' ?>>
                <?php if ($isCountry): ?><b><?= htmlspecialchars(Portal::countryCode($filter), ENT_QUOTES) ?></b><?php endif; ?><?= htmlspecialchars((string) $filter['name'], ENT_QUOTES) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <?php if ($lead === null && $cards === []): ?>
        <p class="pt-empty"><?= htmlspecialchars(t('Пока нет опубликованных новостей.'), ENT_QUOTES) ?></p>
    <?php endif; ?>

    <?php if ($lead !== null): ?>
        <a class="pt-pkg pt-listing__lead-card" href="<?= htmlspecialchars((string) $lead['url'], ENT_QUOTES) ?>">
            <div class="pt-pkg__text">
                <?php $tagCard = $lead; $tagDate = true; require __DIR__ . '/_tags.php'; ?>
                <h2 class="pt-pkg__title pt-hl"><?= htmlspecialchars((string) $lead['title'], ENT_QUOTES) ?></h2>
                <?php if ($lead['excerpt'] !== ''): ?><p class="pt-pkg__lead"><?= htmlspecialchars((string) $lead['excerpt'], ENT_QUOTES) ?></p><?php endif; ?>
            </div>
            <?php $photoCard = $lead; $photoRatio = '4x3'; $photoSizes = '(max-width: 720px) 100vw, 560px'; $photoEager = true; require __DIR__ . '/_photo.php'; ?>
        </a>
    <?php endif; ?>

    <?php if ($cards !== []): ?>
    <div class="pt-grid3 pt-listing__grid">
        <?php foreach ($cards as $i => $card): ?>
            <a class="pt-ncard" href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES) ?>">
                <?php $photoCard = $card; $photoRatio = '4x3'; $photoSizes = '(max-width: 720px) 100vw, 33vw'; $photoEager = $lead === null && $i < 3; require __DIR__ . '/_photo.php'; ?>
                <?php $tagCard = $card; require __DIR__ . '/_tags.php'; ?>
                <span class="pt-hl"><?= htmlspecialchars((string) $card['title'], ENT_QUOTES) ?></span>
                <?php if ($card['excerpt'] !== ''): ?><span class="pt-ncard__text"><?= htmlspecialchars((string) $card['excerpt'], ENT_QUOTES) ?></span><?php endif; ?>
                <time class="pt-meta" datetime="<?= htmlspecialchars((string) $card['datetime'], ENT_QUOTES) ?>"><?= htmlspecialchars((string) $card['date'], ENT_QUOTES) ?></time>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($pages > 1): ?>
    <nav class="pt-pager" aria-label="<?= htmlspecialchars(t('Страницы'), ENT_QUOTES) ?>">
        <?php if ($page > 1): ?><a rel="prev" href="<?= htmlspecialchars($pageUrl($page - 1), ENT_QUOTES) ?>" aria-label="<?= htmlspecialchars(t('Предыдущая страница'), ENT_QUOTES) ?>">←</a><?php endif; ?>
        <?php foreach (Pager::items($page, $pages) as $pagerItem): ?>
            <?php if ($pagerItem === null): ?><span class="pt-pager__gap" aria-hidden="true">…</span>
            <?php elseif ($pagerItem === $page): ?><span aria-current="page"><?= (int) $pagerItem ?></span>
            <?php else: ?><a href="<?= htmlspecialchars($pageUrl($pagerItem), ENT_QUOTES) ?>"><?= (int) $pagerItem ?></a><?php endif; ?>
        <?php endforeach; ?>
        <?php if ($page < $pages): ?><a rel="next" href="<?= htmlspecialchars($pageUrl($page + 1), ENT_QUOTES) ?>" aria-label="<?= htmlspecialchars(t('Следующая страница'), ENT_QUOTES) ?>">→</a><?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php if ($sectionBlocks !== ''): ?><div class="pt-blocks"><?= $sectionBlocks ?></div><?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
