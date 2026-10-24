<?php

use App\Core\AssetCollector;
use App\Core\Locale;

/** @var array $items */
/** @var int $page */
/** @var int $pages */
/** @var list<array<string,mixed>> $categories рубрики с опубликованными новостями */
/** @var list<array<string,mixed>> $countries страны с опубликованными новостями */
/** @var array<string,mixed>|null $section открытый раздел (рубрика или страна) */
/** @var string $basePath адрес ленты без языкового префикса */
$page = $page ?? 1;
$pages = $pages ?? 1;
$categories = $categories ?? [];
$countries = $countries ?? [];
$section = $section ?? null;
$basePath = $basePath ?? 'news';
$sectionId = $section !== null ? (int) $section['id'] : 0;

/** @var array|null $sectionPage шапка раздела из админки (может отсутствовать) */
$sectionPage = $sectionPage ?? null;
$sectionTitle = $section !== null ? (string) $section['name'] : (string) ($sectionPage['title'] ?? '');
$sectionLead = (string) ($sectionPage['lead'] ?? '');
$sectionMetaTitle = (string) ($sectionPage['meta_title'] ?? '');
$metaTitle = $sectionMetaTitle !== '' ? $sectionMetaTitle : ($sectionTitle !== '' ? $sectionTitle : t('Новости'));
$metaDescription = (string) ($sectionPage['meta_description'] ?? '');
if ($metaDescription === '') {
    $metaDescription = $sectionLead !== ''
    ? $sectionLead
    : t('Официальные новости и аналитические материалы Агентства.');
}
// Стили блоков раздела уезжают в <head> тем же путём, что и у страницы.
$extraHeadCss = (string) ($sectionPage['css'] ?? '');

// Соседние страницы ленты для <link rel="prev|next">: адрес тот же, что у
// полосы страниц, поэтому строим его тем же способом.
$pagerLink = static fn (int $p): string => Locale::url($basePath) . ($p > 1 ? '?page=' . $p : '');
['prev' => $pagerPrev, 'next' => $pagerNext] = \App\Core\Pager::relLinks($page, $pages, $pagerLink);
AssetCollector::requireJs('news');
require __DIR__ . '/_header.php';

$crumbs = $section === null
    ? [['label' => t('Главная'), 'url' => Locale::url('/')], ['label' => t('Новости')]]
    : [
        ['label' => t('Главная'), 'url' => Locale::url('/')],
        ['label' => t('Новости'), 'url' => Locale::url('news')],
        ['label' => (string) $section['name']],
    ];
require __DIR__ . '/_crumbs.php';

?>
<div class="listing" data-listing>
    <div class="listing__head">
        <h1 class="listing__title"><?= htmlspecialchars($sectionTitle !== '' ? $sectionTitle : t('Новости и аналитика'), ENT_QUOTES) ?></h1>
        <?php if ($section === null): ?>
            <p class="listing__lead"><?= htmlspecialchars($sectionLead !== '' ? $sectionLead : t('Официальные сообщения, события и аналитические материалы Агентства.'), ENT_QUOTES) ?></p>
        <?php endif; ?>
    </div>

    <?php // Разделы — обычные ссылки на свои адреса, а не фильтр ленты:
          // у раздела свой заголовок, и подмена одного списка его не меняет.
    $sectionNav = static function (array $items, string $label, bool $withAll) use ($sectionId): void {
        if ($items === []) {
            return;
        } ?>
        <nav class="listing-filter listing-filter--sections" aria-label="<?= htmlspecialchars($label, ENT_QUOTES) ?>">
            <?php if ($withAll): ?>
                <a class="listing-filter__item<?= $sectionId === 0 ? ' is-active' : '' ?>" href="<?= htmlspecialchars(Locale::url('news'), ENT_QUOTES) ?>"<?= $sectionId === 0 ? ' aria-current="page"' : '' ?>><?= htmlspecialchars(t('Все материалы'), ENT_QUOTES) ?></a>
            <?php endif; ?>
            <?php foreach ($items as $c): ?>
                <?php // Иконка раздела — украшение рядом с названием: название
                      // читается и без неё, поэтому значок скрыт от диктора. ?>
                <?php $icon = \App\Core\Icon::cleanName($c['icon'] ?? ''); $current = (int) $c['id'] === $sectionId; ?>
                <a class="listing-filter__item<?= $current ? ' is-active' : '' ?><?= $icon !== '' ? ' listing-filter__item--icon' : '' ?>" href="<?= htmlspecialchars(Locale::url(\App\Models\NewsCategory::path($c)), ENT_QUOTES) ?>"<?= $current ? ' aria-current="page"' : '' ?>><?php if ($icon !== ''): ?><span class="listing-filter__icon" aria-hidden="true"><?= \App\Core\Icon::render($icon, 17, '', 1.8) ?></span><?php endif; ?><?= htmlspecialchars((string) $c['name'], ENT_QUOTES) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php };
    $sectionNav($categories, t('Рубрики'), true);
    $sectionNav($countries, t('Страны'), false);
    ?>

    <?php // Область результатов: её же отдаёт контроллер как фрагмент при
          // AJAX-фильтрации, поэтому разметка живёт в одном партиале. ?>
    <div class="listing__results" data-listing-results>
        <?= \App\Core\View::renderPartial('site/_news_list', compact('items', 'page', 'pages', 'basePath')) ?>
    </div>

    <?php // Блоки страницы-раздела — под лентой: наверху уже её шапка. ?>
    <?php if (!empty($sectionPage['content'])): ?>
        <div class="listing__section-blocks"><?= $sectionPage['content'] ?></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>