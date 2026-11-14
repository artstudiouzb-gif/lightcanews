<?php
/**
 * Галерея статьи: крупный кадр, стрелки, счётчик и полоса миниатюр; кадр
 * открывается на весь экран. Без скрипта кадры листаются прокруткой вбок
 * (scroll-snap), миниатюры — ссылки на кадры, а сами кадры — ссылки на файл.
 *
 * @var list<array{path: string, alt: string, caption: string, focal_x: ?int, focal_y: ?int}> $gallerySlides
 * @var bool $galleryEager первый кадр — на первом экране (LCP), грузится сразу
 */

use App\Core\Media;

$gE = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);
$gCount = count($gallerySlides);
$gId = 'ptg' . substr(md5((string) ($gallerySlides[0]['path'] ?? '')), 0, 6);
?>
<section class="pt-gal" data-pt-gallery aria-roledescription="<?= $gE(t('Галерея')) ?>" aria-label="<?= $gE(t('Галерея')) ?>">
    <div class="pt-gal__stage">
        <div class="pt-gal__track" data-pt-gal-track tabindex="0">
            <?php foreach ($gallerySlides as $i => $slide): ?>
                <figure class="pt-gal__slide" id="<?= $gId . '-' . $i ?>" aria-label="<?= $gE(($i + 1) . ' / ' . $gCount) ?>">
                    <a class="pt-gal__frame" href="<?= $gE($slide['path']) ?>" data-pt-gal-zoom target="_blank" rel="noopener">
                        <?= Media::picture($slide['path'], $slide['alt'], $slide['focal_x'], $slide['focal_y'], '', !($galleryEager && $i === 0), '(max-width: 760px) 100vw, 760px', $galleryEager && $i === 0) ?>
                    </a>
                    <?php if ($slide['caption'] !== ''): ?><figcaption><?= $gE($slide['caption']) ?></figcaption><?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
        <?php if ($gCount > 1): ?>
            <button type="button" class="pt-gal__nav pt-gal__nav--prev" data-pt-gal-prev aria-label="<?= $gE(t('Предыдущее фото')) ?>" hidden>‹</button>
            <button type="button" class="pt-gal__nav pt-gal__nav--next" data-pt-gal-next aria-label="<?= $gE(t('Следующее фото')) ?>" hidden>›</button>
            <span class="pt-gal__count" aria-hidden="true"><span data-pt-gal-current>1</span> / <?= $gCount ?></span>
        <?php endif; ?>
    </div>
    <?php if ($gCount > 1): ?>
        <div class="pt-gal__thumbs">
            <?php foreach ($gallerySlides as $i => $slide): ?>
                <a class="pt-gal__thumb<?= $i === 0 ? ' is-active' : '' ?>" href="#<?= $gId . '-' . $i ?>" data-pt-gal-thumb="<?= $i ?>" aria-label="<?= $gE(t('Фото') . ' ' . ($i + 1)) ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>>
                    <?= Media::picture($slide['path'], '', $slide['focal_x'], $slide['focal_y'], '', true, '96px') ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
