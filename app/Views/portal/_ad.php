<?php
/**
 * Рекламная зона портала. Пустая или выключенная зона не печатает ничего.
 * Ожидает $adKey — ключ из PortalAds::ZONES.
 *
 * @var string $adKey
 */

use App\Core\Media;
use App\Core\Portal\PortalAds;

$ad = PortalAds::zone($adKey);
if ($ad === null) {
    return;
}
$adE = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);
$adRel = 'sponsored noopener' . ($ad['external'] ? '" target="_blank' : '');
$adHref = $adE((string) $ad['url']) . '" rel="' . $adRel;
$adMark = $adE(t((string) $ad['mark']));
$adSponsor = (string) $ad['sponsor'];
$adText = (string) $ad['text'];
$adCta = (string) $ad['cta'];
?>
<?php if ($adKey === 'feed'): ?>
<li class="pt-feed__ad">
    <a class="pt-feed-ad" href="<?= $adHref ?>">
        <?= Media::picture((string) $ad['image'], '', null, null, 'pt-feed-ad__img', true, '96px') ?>
        <span>
            <span class="pt-feed-ad__top"><span><?= $adE($adSponsor) ?></span><span class="pt-ad-mark"><?= $adMark ?></span></span>
            <span class="pt-feed-ad__title"><?= $adE((string) $ad['title']) ?></span>
        </span>
    </a>
</li>
<?php elseif ($adKey === 'partner'): ?>
<article class="pt-partner" aria-label="<?= $adMark ?>">
    <span class="pt-partner__mark"><?= $adMark ?></span>
    <a class="pt-partner__link" href="<?= $adHref ?>">
        <?php if ((string) $ad['image'] !== ''): ?><span class="pt-ph pt-ph--16x9"><?= Media::picture((string) $ad['image'], '', null, null, '', true, '(max-width: 720px) 100vw, 360px') ?></span><?php endif; ?>
        <span class="pt-hl pt-partner__title"><?= $adE((string) $ad['title']) ?></span>
        <?php if ($adText !== ''): ?><span class="pt-partner__text"><?= $adE($adText) ?></span><?php endif; ?>
        <?php if ($adCta !== ''): ?><span class="pt-partner__cta"><?= $adE($adCta) ?></span><?php endif; ?>
    </a>
</article>
<?php else: ?>
<?php $adBand = $adKey === 'top'; ?>
<?php if ($adBand): ?><div class="pt-ad-band"><?php endif; ?>
<div class="pt-ad pt-ad--<?= $adE($adKey) ?><?= in_array($adKey, ['top', 'mid'], true) ? ' pt-wrap' : '' ?>">
    <p class="pt-ad__meta"><span class="pt-ad-mark"><?= $adMark ?></span></p>
    <a class="pt-ad__box" href="<?= $adHref ?>">
        <?php if ($adSponsor !== ''): ?><span class="pt-ad__brand"><?= $adE($adSponsor) ?></span><?php endif; ?>
        <span class="pt-ad__copy">
            <strong><?= $adE((string) $ad['title']) ?></strong>
            <?php if ($adText !== ''): ?><span><?= $adE($adText) ?></span><?php endif; ?>
        </span>
        <?php if ($adCta !== ''): ?><span class="pt-ad__cta"><?= $adE($adCta) ?></span><?php endif; ?>
    </a>
</div>
<?php if ($adBand): ?></div><?php endif; ?>
<?php endif; ?>
