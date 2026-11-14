<?php
/**
 * Хлебные крошки портала. Последний пункт — текущая страница, без ссылки.
 * Микроразметку BreadcrumbList печатает тот же SeoHelper, что и раньше.
 *
 * @var list<array{label: string, url?: string}> $crumbs
 */
?>
<nav class="pt-crumbs" aria-label="<?= htmlspecialchars(t('Хлебные крошки'), ENT_QUOTES) ?>">
    <?php foreach ($crumbs as $i => $crumb): ?>
        <?php if ($i > 0): ?><span aria-hidden="true">/</span><?php endif; ?>
        <?php if (isset($crumb['url']) && $i < count($crumbs) - 1): ?>
            <a href="<?= htmlspecialchars((string) $crumb['url'], ENT_QUOTES) ?>"><?= htmlspecialchars($crumb['label'], ENT_QUOTES) ?></a>
        <?php else: ?>
            <span aria-current="page"><?= htmlspecialchars($crumb['label'], ENT_QUOTES) ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
<?= \App\Core\SeoHelper::breadcrumbSchema(\App\Core\AppUrl::base(), array_map(static fn (array $c): array => ['name' => $c['label']] + (isset($c['url']) ? ['url' => $c['url']] : []), $crumbs)) ?>
