<?php
/**
 * Фотоальбомы в шаблоне портала.
 *
 * @var list<array<string, mixed>> $albums
 */

use App\Core\Locale;
use App\Core\Media;

$metaTitle = t('Фотоальбомы');
$metaDescription = t('Фотогалереи и альбомы мероприятий');
$crumbs = [['label' => t('Главная'), 'url' => Locale::url('/')], ['label' => t('Фотоальбомы')]];

require __DIR__ . '/_top.php';
?>
<div class="pt-wrap pt-sec">
    <?php require __DIR__ . '/_crumbs.php'; ?>
    <header class="pt-sec-head pt-sec-head--page"><h1><?= htmlspecialchars(t('Фотоальбомы'), ENT_QUOTES) ?></h1></header>
    <?php if ($albums === []): ?>
        <p class="pt-empty"><?= htmlspecialchars(t('Альбомов пока нет.'), ENT_QUOTES) ?></p>
    <?php else: ?>
    <div class="pt-grid3">
        <?php foreach ($albums as $album): ?>
            <a class="pt-ncard" href="<?= htmlspecialchars(Locale::url('albums/' . $album['slug']), ENT_QUOTES) ?>">
                <span class="pt-ph pt-ph--4x3"<?= (string) $album['cover'] === '' ? ' data-empty' : '' ?>>
                    <?php if ((string) $album['cover'] !== ''): ?>
                        <?= Media::picture((string) $album['cover'], (string) $album['title'], null, null, '', true, '(max-width: 720px) 100vw, 33vw') ?>
                    <?php endif; ?>
                </span>
                <span class="pt-hl"><?= htmlspecialchars((string) $album['title'], ENT_QUOTES) ?></span>
                <span class="pt-meta"><?= (int) $album['images_count'] ?> <?= htmlspecialchars(t('фото'), ENT_QUOTES) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
