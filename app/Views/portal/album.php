<?php
/**
 * Фотоальбом в шаблоне портала: снимки сеткой, увеличение — ссылкой на файл.
 *
 * @var array<string, mixed> $album
 * @var list<array<string, mixed>> $images
 * @var string|null $newsUrl
 */

use App\Core\Locale;
use App\Core\Media;

$metaTitle = (string) $album['title'];
$metaDescription = mb_substr(trim((string) ($album['description'] ?? '')), 0, 160);
$crumbs = [
    ['label' => t('Главная'), 'url' => Locale::url('/')],
    ['label' => t('Фотоальбомы'), 'url' => Locale::url('albums')],
    ['label' => (string) $album['title']],
];

require __DIR__ . '/_top.php';
?>
<div class="pt-wrap pt-sec">
    <?php require __DIR__ . '/_crumbs.php'; ?>
    <header class="pt-sec-head pt-sec-head--page"><h1><?= htmlspecialchars((string) $album['title'], ENT_QUOTES) ?></h1></header>
    <?php if (!empty($album['description'])): ?><p class="pt-listing__lead"><?= nl2br(htmlspecialchars((string) $album['description'], ENT_QUOTES)) ?></p><?php endif; ?>
    <?php if (!empty($newsUrl)): ?><p class="pt-listing__lead"><a class="pt-link" href="<?= htmlspecialchars((string) $newsUrl, ENT_QUOTES) ?>"><?= htmlspecialchars(t('Читать новость'), ENT_QUOTES) ?></a></p><?php endif; ?>
    <?php if ($images === []): ?>
        <p class="pt-empty"><?= htmlspecialchars(t('В альбоме пока нет фотографий.'), ENT_QUOTES) ?></p>
    <?php else: ?>
    <div class="pt-album">
        <?php foreach ($images as $img): ?>
            <?php $imgCredit = trim((string) ($img['credit'] ?? '')); ?>
            <figure>
                <a href="<?= htmlspecialchars((string) $img['image_url'], ENT_QUOTES) ?>" target="_blank" rel="noopener">
                    <?= Media::picture((string) $img['image_url'], (string) ($img['caption'] ?: $album['title']), null, null, '', true, '(max-width: 720px) 100vw, 33vw') ?>
                </a>
                <?php if ((string) $img['caption'] !== '' || $imgCredit !== ''): ?>
                    <figcaption class="pt-caption">
                        <?= htmlspecialchars((string) $img['caption'], ENT_QUOTES) ?>
                        <?php if ($imgCredit !== ''): ?><span><?= htmlspecialchars(t('Фото:'), ENT_QUOTES) ?> <?= htmlspecialchars(Media::photoCredit($imgCredit), ENT_QUOTES) ?></span><?php endif; ?>
                    </figcaption>
                <?php endif; ?>
            </figure>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
