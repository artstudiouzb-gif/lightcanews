<?php
/**
 * Рекламная зона портала. Пустая или выключенная зона не печатает ничего.
 * Ожидает $adKey — ключ из PortalAds::ZONES.
 *
 * Креативов в зоне бывает несколько: печатаются все, чей период ещё не
 * закончился, а виден один (`hidden` у остальных). Какой — решает
 * `portal.js` по `data-ad-from/to` и способу смены: страница кэшируется, и
 * выбор сервера застыл бы для всех посетителей. Без скрипта виден первый
 * идущий сейчас.
 *
 * @var string $adKey
 * @var string|null $adPlace тип страницы из PortalAds::PLACES; не задан — «остальные»
 */

use App\Core\Media;
use App\Core\Portal\PortalAds;

$adZone = PortalAds::zone($adKey, (string) ($adPlace ?? 'other'));
if ($adZone === null) {
    return;
}
$adE = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);
$adMark = $adE(t((string) $adZone['mark']));
$adBand = $adKey === 'top';
$adWrap = $adKey === 'top' || ($adKey === 'mid' && ($adPlace ?? '') === 'home');
?>
<?php if ($adBand): ?><div class="pt-ad-band"><?php endif; ?>
<?php foreach ($adZone['items'] as $ad):
    $adRel = 'sponsored noopener' . ($ad['external'] ? '" target="_blank' : '');
    $adHref = $adE((string) $ad['url']) . '" rel="' . $adRel;
    $adSponsor = (string) $ad['sponsor'];
    $adText = (string) $ad['text'];
    $adCta = (string) $ad['cta'];
    $adData = ' data-ad="' . $adE($adKey) . '" data-ad-rotate="' . $adE((string) $adZone['rotate']) . '" data-ad-interval="' . (int) $adZone['interval'] . '"'
        . ($ad['from_ts'] !== null ? ' data-ad-from="' . (int) $ad['from_ts'] . '"' : '')
        . ($ad['to_ts'] !== null ? ' data-ad-to="' . (int) $ad['to_ts'] . '"' : '')
        . ($ad['shown'] ? '' : ' hidden');
?>
<?php if ($adKey === 'feed'): ?>
<li class="pt-feed__ad"<?= $adData ?>>
    <a class="pt-feed-ad" href="<?= $adHref ?>">
        <?= Media::picture((string) $ad['image'], '', null, null, 'pt-feed-ad__img', true, '96px') ?>
        <span>
            <span class="pt-feed-ad__top"><span><?= $adE($adSponsor) ?></span><span class="pt-ad-mark"><?= $adMark ?></span></span>
            <span class="pt-feed-ad__title"><?= $adE((string) $ad['title']) ?></span>
        </span>
    </a>
</li>
<?php elseif ($adKey === 'partner'): ?>
<article class="pt-partner" aria-label="<?= $adMark ?>"<?= $adData ?>>
    <span class="pt-partner__mark"><?= $adMark ?></span>
    <a class="pt-partner__link" href="<?= $adHref ?>">
        <?php if ((string) $ad['image'] !== ''): ?><span class="pt-ph pt-ph--16x9"><?= Media::picture((string) $ad['image'], '', null, null, '', true, '(max-width: 720px) 100vw, 360px') ?></span><?php endif; ?>
        <span class="pt-hl pt-partner__title"><?= $adE((string) $ad['title']) ?></span>
        <?php if ($adText !== ''): ?><span class="pt-partner__text"><?= $adE($adText) ?></span><?php endif; ?>
        <?php if ($adCta !== ''): ?><span class="pt-partner__cta"><?= $adE($adCta) ?></span><?php endif; ?>
    </a>
</article>
<?php else: ?>
<div class="pt-ad pt-ad--<?= $adE($adKey) ?><?= $adWrap ? ' pt-wrap' : '' ?>"<?= $adData ?>>
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
<?php endif; ?>
<?php endforeach; ?>
<?php if ($adBand): ?></div><?php endif; ?>
