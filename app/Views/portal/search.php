<?php
/**
 * Поиск по сайту в шаблоне портала. Результаты не индексируются.
 *
 * @var string $query
 * @var list<array<string, mixed>> $results
 */

use App\Core\Locale;

$metaTitle = $query !== '' ? (t('Поиск:') . ' ' . $query) : t('Поиск по сайту');
$metaDescription = '';
$robotsNoindex = true;
$crumbs = [['label' => t('Главная'), 'url' => Locale::url('/')], ['label' => t('Поиск по сайту')]];

require __DIR__ . '/_top.php';
?>
<div class="pt-wrap pt-sec pt-search-page">
    <?php require __DIR__ . '/_crumbs.php'; ?>
    <header class="pt-sec-head pt-sec-head--page"><h1><?= htmlspecialchars(t('Поиск по сайту'), ENT_QUOTES) ?></h1></header>
    <form class="pt-search-page__form" method="get" action="<?= htmlspecialchars(Locale::url('search'), ENT_QUOTES) ?>" role="search">
        <input type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES) ?>" placeholder="<?= htmlspecialchars(t('Что вы ищете?'), ENT_QUOTES) ?>" aria-label="<?= htmlspecialchars(t('Поисковый запрос'), ENT_QUOTES) ?>">
        <button class="pt-btn pt-btn--brand" type="submit"><?= htmlspecialchars(t('Найти'), ENT_QUOTES) ?></button>
    </form>

    <?php if ($query !== '' && mb_strlen($query) < 2): ?>
        <p class="pt-empty"><?= htmlspecialchars(t('Введите не менее двух символов.'), ENT_QUOTES) ?></p>
    <?php elseif ($query !== ''): ?>
        <p class="pt-search-page__count">
            <?php if ($results === []): ?>
                <?= htmlspecialchars(t('По запросу'), ENT_QUOTES) ?> «<?= htmlspecialchars($query, ENT_QUOTES) ?>» <?= htmlspecialchars(t('ничего не найдено.'), ENT_QUOTES) ?>
            <?php else: ?>
                <?= htmlspecialchars(t('Найдено результатов:'), ENT_QUOTES) ?> <?= count($results) ?>
            <?php endif; ?>
        </p>
        <?php if ($results !== []): ?>
        <ol class="pt-results">
            <?php foreach ($results as $r): ?>
                <li>
                    <span class="pt-rubric"><?= htmlspecialchars((string) $r['type'], ENT_QUOTES) ?></span>
                    <a class="pt-hl" href="<?= htmlspecialchars((string) $r['url'], ENT_QUOTES) ?>"><?= htmlspecialchars((string) $r['title'], ENT_QUOTES) ?></a>
                    <?php if (!empty($r['excerpt'])): ?><p><?= htmlspecialchars((string) $r['excerpt'], ENT_QUOTES) ?></p><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
