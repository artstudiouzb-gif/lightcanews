<?php
/**
 * Страница конструктора в шаблоне портала: крошки, заголовок с лидом и блоки.
 * Блоки печатают свои шаблоны (`templates/blocks`), оформление им даёт
 * `portal/blocks.css` — стили прежней темы сюда не подключаются.
 *
 * @var array<string, mixed> $page
 * @var string $content  готовая разметка блоков
 * @var string $blockCss scoped CSS блоков
 * @var array{html: string, position: string}|null $sidebar
 */

use App\Core\Locale;
use App\Models\Page;

$metaTitle = (string) (($page['meta_title'] ?? '') !== '' ? $page['meta_title'] : ($page['title'] ?? ''));
$pageLead = trim((string) ($page['lead'] ?? ''));
$metaDescription = trim((string) ($page['meta_description'] ?? ''));
if ($metaDescription === '') {
    $metaDescription = \App\Core\SeoHelper::clip(trim((string) preg_replace('/\s+/u', ' ', strip_tags($pageLead))), 200);
}
if ($metaDescription === '') {
    $metaDescription = \App\Core\SeoHelper::autoDescription($content);
}
$portalBlocks = true;

$crumbs = [['label' => t('Главная'), 'url' => Locale::url('/')]];
foreach (Page::ancestorTrail($page, Locale::current()) as $ancestor) {
    if (!empty($ancestor['is_home']) || (string) ($ancestor['status'] ?? '') !== 'published') {
        continue;
    }
    $crumbs[] = ['label' => (string) $ancestor['title'], 'url' => Locale::url('/' . ltrim((string) $ancestor['slug'], '/'))];
}
$crumbs[] = ['label' => (string) ($page['title'] ?? '')];
$hideCrumbs = !empty($page['hide_crumbs']);
$hideTitle = !empty($page['hide_title']);
$hasSidebar = $sidebar !== null && trim((string) $sidebar['html']) !== '';

require __DIR__ . '/_top.php';
?>
<div class="pt-wrap pt-page<?= $hasSidebar ? ' pt-page--aside' : '' ?>">
    <?php if (!$hideCrumbs): ?>
        <?php require __DIR__ . '/_crumbs.php'; ?>
    <?php endif; ?>
    <?php if ($hideTitle): ?>
        <h1 class="pt-visually-hidden"><?= htmlspecialchars((string) ($page['title'] ?? ''), ENT_QUOTES) ?></h1>
    <?php else: ?>
        <header class="pt-pagehead">
            <h1 class="pt-pagehead__title"><?= htmlspecialchars((string) ($page['title'] ?? ''), ENT_QUOTES) ?></h1>
            <?php if ($pageLead !== ''): ?><p class="pt-pagehead__lead"><?= nl2br(htmlspecialchars($pageLead, ENT_QUOTES)) ?></p><?php endif; ?>
        </header>
    <?php endif; ?>
    <?php if ($hasSidebar): ?>
        <div class="pt-page__grid">
            <div class="pt-blocks"><?= $content ?></div>
            <aside class="pt-page__aside"><?= $sidebar['html'] ?></aside>
        </div>
    <?php else: ?>
        <div class="pt-blocks"><?= $content ?></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
