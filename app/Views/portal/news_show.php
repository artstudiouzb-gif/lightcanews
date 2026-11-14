<?php
/**
 * Статья портала: три колонки по прототипу — слева оглавление и ход чтения,
 * в центре текст, справа «Читайте также» и самое читаемое. На узком экране
 * левая колонка уходит, правая встаёт под текст.
 *
 * Оглавление собирается из заголовков h2/h3 текста; id им проставляется здесь
 * же. Микроразметка NewsArticle и даты для Open Graph — те же, что раньше.
 *
 * @var array<string, mixed> $news
 * @var list<array<string, mixed>> $gallery
 * @var list<array<string, mixed>> $related
 * @var array<string, mixed>|null $nextNews
 */

use App\Core\DateFormatter;
use App\Core\Locale;
use App\Core\Media;
use App\Core\Portal\Portal;
use App\Models\News;

$lang = Locale::current();
$metaTitle = (string) (($news['meta_title'] ?? '') !== '' ? $news['meta_title'] : $news['title']);
$metaDescription = (string) (($news['meta_description'] ?? '') !== '' ? $news['meta_description'] : ($news['excerpt'] ?? ''));
$ogType = 'article';
$publishedAt = (string) ($news['published_at'] ?? '');
$modifiedAt = (string) ($news['updated_at'] ?? '');
$ogImage = News::getCoverImage($news) ?? '';
$leadHtml = \App\Core\NewsLead::html($news['lead_html'] ?? null, $news['excerpt'] ?? null);

$card = Portal::cards([$news], $lang)[0];
$words = count(preg_split('/\s+/u', trim(strip_tags((string) ($news['content'] ?? '')))) ?: []);
$readMinutes = max(1, (int) ceil($words / 200));

// Оглавление из заголовков текста.
$toc = [];
$contentHtml = \App\Core\SocialEmbed::transform((string) ($news['content'] ?? ''));
$contentHtml = (string) preg_replace_callback(
    '/<h([23])([^>]*)>(.*?)<\/h\1>/su',
    static function (array $m) use (&$toc): string {
        $id = 'sec-' . (count($toc) + 1);
        $label = trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($label !== '') {
            $toc[] = ['id' => $id, 'label' => $label];
        }
        $attrs = (string) preg_replace('/\sid="[^"]*"/', '', $m[2]);

        return '<h' . $m[1] . ' id="' . $id . '"' . $attrs . '>' . $m[3] . '</h' . $m[1] . '>';
    },
    $contentHtml
);

$keyPoints = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($news['key_points'] ?? '')) ?: [])));
$docs = json_decode((string) ($news['docs'] ?? '[]'), true);
$docs = is_array($docs) ? $docs : [];
$videoId = \App\Core\Video::youtubeId($news['video_url'] ?? null);

// Обложка и её подпись — из записи галереи с тем же путём.
$cover = $ogImage;
$coverCaption = '';
$extraPhotos = [];
foreach ($gallery as $image) {
    $path = (string) ($image['path'] ?? '');
    if ($path === $cover) {
        $coverCaption = trim((string) ($image['caption'] ?? '') . ' ' . (string) ($image['credit'] ?? ''));
    } elseif ($path !== '') {
        $extraPhotos[] = $image;
    }
}

$related = array_slice(Portal::cards($related, $lang), 0, 4);
$next = $nextNews !== null ? (Portal::cards([$nextNews], $lang)[0] ?? null) : null;
$popular = Portal::cards(News::mostViewed(30, 5, $lang), $lang);
$hashtags = News::cleanHashtags($news['hashtags'] ?? null);

$base = \App\Core\AppUrl::base();
$pageUrl = $base . Locale::url('news/' . $news['slug'], $lang);
$crumbs = [
    ['label' => t('Главная'), 'url' => Locale::url('/')],
    ['label' => $card['rubric'] !== '' ? $card['rubric'] : t('Новости'), 'url' => $card['rubric_url'] !== '' ? $card['rubric_url'] : Locale::url('news')],
    ['label' => (string) $news['title']],
];

require __DIR__ . '/_top.php';
?>
<div class="pt-wrap pt-art">
    <aside class="pt-art__rail" aria-label="<?= htmlspecialchars(t('Навигация по статье'), ENT_QUOTES) ?>">
        <?php if (count($toc) > 1): ?>
        <div class="pt-art__box">
            <div class="pt-art__box-title"><?= htmlspecialchars(t('Содержание'), ENT_QUOTES) ?></div>
            <ol class="pt-toc" data-pt-toc>
                <?php foreach ($toc as $i => $item): ?>
                    <li><a href="#<?= $item['id'] ?>"><span class="pt-toc__num"><?= sprintf('%02d', $i + 1) ?></span><span><?= htmlspecialchars($item['label'], ENT_QUOTES) ?></span></a></li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php endif; ?>
        <div class="pt-art__box">
            <div class="pt-art__box-title"><?= htmlspecialchars(t('Прочитано'), ENT_QUOTES) ?></div>
            <div class="pt-progress" aria-hidden="true"><div class="pt-progress__fill" data-pt-progress></div></div>
            <div class="pt-progress__label"><span data-pt-progress-pct>0%</span><span><?= htmlspecialchars(sprintf(t('%d мин чтения'), $readMinutes), ENT_QUOTES) ?></span></div>
        </div>
    </aside>

    <article class="pt-art__main" data-pt-article>
        <?php require __DIR__ . '/_crumbs.php'; ?>
        <div class="pt-tags pt-art__tags">
            <?php if ($card['badge'] !== ''): ?><span class="pt-badge"><?= htmlspecialchars((string) $card['badge'], ENT_QUOTES) ?></span><?php endif; ?>
            <?php if ($card['rubric'] !== ''): ?><a class="pt-art__badge" href="<?= htmlspecialchars((string) $card['rubric_url'], ENT_QUOTES) ?>"><?= htmlspecialchars((string) $card['rubric'], ENT_QUOTES) ?></a><?php endif; ?>
            <?php foreach ($card['countries'] as $code): ?><span class="pt-code"><?= htmlspecialchars((string) $code, ENT_QUOTES) ?></span><?php endforeach; ?>
        </div>
        <h1 class="pt-art__title"><?= htmlspecialchars((string) $news['title'], ENT_QUOTES) ?></h1>
        <?php if ($leadHtml !== ''): ?><div class="pt-art__lead"><?= $leadHtml ?></div><?php endif; ?>

        <div class="pt-art__byline">
            <span class="pt-meta"><time datetime="<?= htmlspecialchars((string) $card['datetime'], ENT_QUOTES) ?>"><?= htmlspecialchars(DateFormatter::long($publishedAt, $lang), ENT_QUOTES) ?>, <?= htmlspecialchars((string) $card['time'], ENT_QUOTES) ?></time> · <?= htmlspecialchars(sprintf(t('%d мин чтения'), $readMinutes), ENT_QUOTES) ?></span>
            <button type="button" class="pt-btn" data-pt-share data-url="<?= htmlspecialchars($pageUrl, ENT_QUOTES) ?>" data-done="<?= htmlspecialchars(t('Ссылка скопирована'), ENT_QUOTES) ?>"><?= htmlspecialchars(t('Поделиться'), ENT_QUOTES) ?></button>
        </div>

        <?php if ($videoId !== null): ?>
            <figure class="pt-art__figure">
                <div class="pt-ph pt-ph--16x9 pt-art__video">
                    <iframe src="https://www.youtube-nocookie.com/embed/<?= htmlspecialchars($videoId, ENT_QUOTES) ?>" title="<?= htmlspecialchars((string) $news['title'], ENT_QUOTES) ?>" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            </figure>
        <?php elseif ($cover !== ''): ?>
            <figure class="pt-art__figure">
                <div class="pt-ph pt-ph--16x9"><?= Media::picture($cover, (string) $news['title'], isset($news['focal_x']) ? (int) $news['focal_x'] : null, isset($news['focal_y']) ? (int) $news['focal_y'] : null, '', false, '(max-width: 760px) 100vw, 760px', true) ?></div>
                <?php if ($coverCaption !== ''): ?><figcaption><?= htmlspecialchars($coverCaption, ENT_QUOTES) ?></figcaption><?php endif; ?>
            </figure>
        <?php endif; ?>

        <?php if ($keyPoints !== []): ?>
            <section class="pt-impact" aria-labelledby="pt-impact-title">
                <h2 class="pt-impact__head" id="pt-impact-title"><?= htmlspecialchars(t('Главное'), ENT_QUOTES) ?></h2>
                <ul class="pt-impact__list">
                    <?php foreach ($keyPoints as $point): ?><li><?= htmlspecialchars($point, ENT_QUOTES) ?></li><?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <div class="pt-art__body rich-content"><?= $contentHtml ?></div>

        <?php if ($extraPhotos !== []): ?>
            <div class="pt-art__photos">
                <?php foreach ($extraPhotos as $photo): ?>
                    <figure class="pt-art__figure">
                        <div class="pt-ph"><?= Media::picture((string) $photo['path'], (string) ($photo['alt'] ?? ''), isset($photo['focal_x']) ? (int) $photo['focal_x'] : null, isset($photo['focal_y']) ? (int) $photo['focal_y'] : null, '', true, '(max-width: 760px) 100vw, 380px') ?></div>
                        <?php $photoCaption = trim((string) ($photo['caption'] ?? '') . ' ' . (string) ($photo['credit'] ?? '')); ?>
                        <?php if ($photoCaption !== ''): ?><figcaption><?= htmlspecialchars($photoCaption, ENT_QUOTES) ?></figcaption><?php endif; ?>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($docs !== []): ?>
            <section class="pt-art__docs">
                <h2 class="pt-art__docs-title"><?= htmlspecialchars(t('Документы'), ENT_QUOTES) ?></h2>
                <ul>
                    <?php foreach ($docs as $doc): ?>
                        <?php $docUrl = (string) ($doc['url'] ?? ''); if ($docUrl === '' || !\App\Core\UrlGuard::isSafeLink($docUrl)) { continue; } ?>
                        <li><a href="<?= htmlspecialchars($docUrl, ENT_QUOTES) ?>"><?= htmlspecialchars((string) (($doc['title'] ?? '') !== '' ? $doc['title'] : basename($docUrl)), ENT_QUOTES) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($hashtags !== null): ?>
            <div class="pt-art__hashtags" data-no-translit>
                <?php foreach (explode(' ', $hashtags) as $tag): ?>
                    <a class="pt-art__tag" href="<?= htmlspecialchars(Locale::url('search') . '?q=' . rawurlencode(ltrim($tag, '#')), ENT_QUOTES) ?>"><?= htmlspecialchars($tag, ENT_QUOTES) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($next !== null): ?>
            <a class="pt-art__next" href="<?= htmlspecialchars((string) $next['url'], ENT_QUOTES) ?>">
                <?php $photoCard = $next; $photoSizes = '140px'; require __DIR__ . '/_photo.php'; ?>
                <span>
                    <span class="pt-art__next-kicker"><?= htmlspecialchars(t('Читать далее') . ($next['rubric'] !== '' ? ' · ' . $next['rubric'] : ''), ENT_QUOTES) ?></span>
                    <span class="pt-art__next-title pt-hl"><?= htmlspecialchars((string) $next['title'], ENT_QUOTES) ?></span>
                </span>
            </a>
        <?php endif; ?>
    </article>

    <aside class="pt-art__side">
        <?php $adKey = 'article'; require __DIR__ . '/_ad.php'; ?>
        <?php if ($related !== []): ?>
        <div class="pt-side-card">
            <div class="pt-side-card__head"><?= htmlspecialchars(t('Читайте также'), ENT_QUOTES) ?></div>
            <div class="pt-side-list">
                <?php foreach ($related as $item): ?>
                    <a class="pt-side-item" href="<?= htmlspecialchars((string) $item['url'], ENT_QUOTES) ?>">
                        <span class="pt-side-item__meta"><?= htmlspecialchars(trim($item['rubric'] . ' · ' . $item['date'], ' ·'), ENT_QUOTES) ?></span>
                        <span class="pt-side-item__title"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($popular !== []): ?>
        <div class="pt-side-card">
            <div class="pt-side-card__head"><?= htmlspecialchars(t('Самое читаемое'), ENT_QUOTES) ?></div>
            <ol class="pt-side-list pt-side-list--ranked">
                <?php foreach ($popular as $item): ?>
                    <li><a class="pt-side-item" href="<?= htmlspecialchars((string) $item['url'], ENT_QUOTES) ?>"><span class="pt-side-item__title"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES) ?></span></a></li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php endif; ?>
    </aside>
</div>
<?php $newsOrg = \App\Core\SeoHelper::organizationRef($base); ?>
<?= \App\Core\SchemaOrg::render(\App\Core\SchemaOrg::newsArticle(
    (string) $news['title'],
    $pageUrl,
    $publishedAt,
    \App\Core\SeoHelper::clip($metaDescription, 300),
    $ogImage !== '' ? (preg_match('#^https?://#i', $ogImage) === 1 ? $ogImage : $base . '/' . ltrim($ogImage, '/')) : '',
    $newsOrg,
    $modifiedAt,
    ['@type' => $newsOrg['@type'], '@id' => $newsOrg['@id'], 'name' => $newsOrg['name'], 'url' => $newsOrg['url']],
    $lang,
    (string) $card['rubric']
)) ?>
<?php require __DIR__ . '/_bottom.php'; ?>
