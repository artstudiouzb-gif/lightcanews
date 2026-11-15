<?php
/**
 * Секция «Главное в отрасли» и лента новостей справа.
 * Общая для фиксированной главной и блока «Секция портала».
 *
 * @var array<string, mixed> $sec      Portal::section()
 * @var string               $secTitle свой заголовок секции ('' — по умолчанию)
 */

use App\Core\Locale;

$lead = $sec['lead'] ?? null;
$grid = (array) ($sec['grid'] ?? []);
$also = (array) ($sec['also'] ?? []);
$feed = (array) ($sec['feed'] ?? []);
$newsUrl = Locale::url('news');
?>
<div class="pt-wrap pt-home-top">
    <section class="pt-lead-ed" aria-labelledby="pt-lead-title">
        <header class="pt-sec-head pt-sec-head--sm">
            <h2 id="pt-lead-title"><?= htmlspecialchars($secTitle !== '' ? $secTitle : t('Главное в отрасли'), ENT_QUOTES) ?></h2>
            <a class="pt-more" href="<?= htmlspecialchars($newsUrl, ENT_QUOTES) ?>"><?= htmlspecialchars(t('Все новости'), ENT_QUOTES) ?></a>
        </header>

        <?php if ($lead === null): ?>
            <p class="pt-empty"><?= htmlspecialchars(t('Пока нет опубликованных новостей.'), ENT_QUOTES) ?></p>
        <?php else: ?>
            <a class="pt-pkg" href="<?= htmlspecialchars((string) $lead['url'], ENT_QUOTES) ?>">
                <div class="pt-pkg__text">
                    <?php $tagCard = $lead; $tagDate = true; require __DIR__ . '/_tags.php'; ?>
                    <h3 class="pt-pkg__title pt-hl"><?= htmlspecialchars((string) $lead['title'], ENT_QUOTES) ?></h3>
                    <?php if ($lead['excerpt'] !== ''): ?><p class="pt-pkg__lead"><?= htmlspecialchars((string) $lead['excerpt'], ENT_QUOTES) ?></p><?php endif; ?>
                </div>
                <?php $photoCard = $lead; $photoRatio = '4x3'; $photoSizes = '(max-width: 720px) 100vw, 480px'; $photoEager = true; require __DIR__ . '/_photo.php'; ?>
            </a>
        <?php endif; ?>

        <?php if ($grid !== []): ?>
        <div class="pt-grid3">
            <?php foreach ($grid as $card): ?>
                <a class="pt-ncard" href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES) ?>">
                    <?php $photoCard = $card; $photoRatio = '4x3'; $photoSizes = '(max-width: 720px) 100vw, 300px'; require __DIR__ . '/_photo.php'; ?>
                    <?php $tagCard = $card; require __DIR__ . '/_tags.php'; ?>
                    <span class="pt-hl"><?= htmlspecialchars((string) $card['title'], ENT_QUOTES) ?></span>
                    <time class="pt-meta" datetime="<?= htmlspecialchars((string) $card['datetime'], ENT_QUOTES) ?>"><?= htmlspecialchars((string) $card['date'], ENT_QUOTES) ?></time>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($also !== []): ?>
        <section class="pt-also" aria-labelledby="pt-also-title">
            <header class="pt-sec-head pt-sec-head--sm">
                <h2 id="pt-also-title"><?= htmlspecialchars(t('Также в центре внимания'), ENT_QUOTES) ?></h2>
            </header>
            <div class="pt-also__grid">
                <?php foreach ($also as $card): ?>
                    <a class="pt-also__item" href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES) ?>">
                        <?php $tagCard = $card; $tagDate = true; require __DIR__ . '/_tags.php'; ?>
                        <span class="pt-hl"><?= htmlspecialchars((string) $card['title'], ENT_QUOTES) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </section>

    <aside class="pt-feed" aria-labelledby="pt-feed-title">
        <header class="pt-sec-head pt-sec-head--sm">
            <h2 id="pt-feed-title"><?= htmlspecialchars(t('Лента новостей'), ENT_QUOTES) ?></h2>
        </header>
        <ol class="pt-feed__list">
            <?php foreach ($feed as $feedIndex => $card): ?>
                <li>
                    <a href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES) ?>">
                        <span class="pt-feed__stamp">
                            <time datetime="<?= htmlspecialchars((string) $card['datetime'], ENT_QUOTES) ?>"><?= htmlspecialchars((string) $card['time'], ENT_QUOTES) ?></time>
                            <span class="pt-feed__date"><?= htmlspecialchars((string) $card['day_full'], ENT_QUOTES) ?></span>
                        </span>
                        <span>
                            <?php $feedKicker = trim(htmlspecialchars((string) $card['rubric'], ENT_QUOTES) . ($card['country_marks'] !== [] ? ' · ' . implode(', ', $card['country_marks']) : ''), ' ·'); ?>
                            <?php if ($feedKicker !== ''): ?><span class="pt-feed__rubric"><?= $feedKicker ?></span><?php endif; ?>
                            <span class="pt-hl"><?= htmlspecialchars((string) $card['title'], ENT_QUOTES) ?></span>
                        </span>
                    </a>
                </li>
                <?php if ($feedIndex === 2): $adKey = 'feed'; require __DIR__ . '/_ad.php'; endif; ?>
            <?php endforeach; ?>
        </ol>
        <a class="pt-btn pt-btn--wide" href="<?= htmlspecialchars($newsUrl, ENT_QUOTES) ?>"><?= htmlspecialchars(t('Вся лента'), ENT_QUOTES) ?></a>
        <?php $adKey = 'partner'; require __DIR__ . '/_ad.php'; ?>
    </aside>
</div>
