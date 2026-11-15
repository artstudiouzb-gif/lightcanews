<?php
/**
 * Секция «Видео и подкасты»: крупный ролик слева, справа ещё три выпуска и
 * плашка студии. Вкладки «Все / Видео / Подкасты» фильтруют список (скрипт
 * портала); без скрипта виден весь список.
 * Общая для фиксированной главной и блока «Секция портала».
 *
 * @var array<string, mixed> $sec      Portal::section()
 * @var string               $secTitle свой заголовок секции ('' — по умолчанию)
 * @var string|null          $secUid
 * @var array{text:string, url:string, title:string, note:string}|null $secCta плашка студии
 */

use App\Core\HeaderConfig;
use App\Core\Media;

$media = (array) ($sec['media'] ?? []);
$secUid = (string) ($secUid ?? 'home');
$mediaE = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);
$mediaLead = $media[0] ?? null;
$mediaRest = array_slice($media, 1);
$mediaKinds = array_count_values(array_column($media, 'kind'));
// «Все выпуски на YouTube» — канал из соцкнопок шапки, второго поля нет.
$mediaYoutube = '';
foreach ((array) (HeaderConfig::get()['social_buttons'] ?? []) as $mediaBtn) {
    if (strtolower((string) ($mediaBtn['network'] ?? '')) === 'youtube' && \App\Core\UrlGuard::isSafeLink((string) ($mediaBtn['url'] ?? ''))) {
        $mediaYoutube = (string) $mediaBtn['url'];
        break;
    }
}
$mediaCta = is_array($secCta ?? null) && ($secCta['text'] ?? '') !== '' && ($secCta['url'] ?? '') !== '' ? $secCta : null;
$mediaLink = static fn (array $item): string => 'href="' . htmlspecialchars((string) $item['url'], ENT_QUOTES) . '"' . ($item['external'] ? ' target="_blank" rel="noopener"' : '');
$mediaLabel = static fn (array $item): string => $item['kind'] === 'podcast' ? t('Подкаст') : t('Видео');
?>
<?php if ($mediaLead !== null): ?>
<section class="pt-media" aria-labelledby="pt-media-title-<?= $secUid ?>" data-pt-media>
    <div class="pt-wrap">
        <header class="pt-sec-head">
            <div>
                <p class="pt-media__kicker"><?= $mediaE(t('Видео и подкасты')) ?></p>
                <h2 id="pt-media-title-<?= $secUid ?>"><?= $mediaE($secTitle !== '' ? $secTitle : t('Видео и подкасты')) ?></h2>
            </div>
            <div class="pt-media__head-right">
                <?php if (count($mediaKinds) > 1): ?>
                <div class="pt-tabs pt-tabs--dark" role="group" aria-label="<?= $mediaE(t('Фильтр медиа')) ?>" hidden data-pt-media-tabs>
                    <button type="button" aria-pressed="true" data-kind="all"><?= $mediaE(t('Все')) ?> (<?= count($media) ?>)</button>
                    <button type="button" aria-pressed="false" data-kind="video"><?= $mediaE(t('Видео')) ?></button>
                    <button type="button" aria-pressed="false" data-kind="podcast"><?= $mediaE(t('Подкасты')) ?></button>
                </div>
                <?php endif; ?>
                <?php if ($mediaYoutube !== ''): ?><a class="pt-media__all" href="<?= $mediaE($mediaYoutube) ?>" target="_blank" rel="noopener"><?= $mediaE(t('Все выпуски на YouTube')) ?></a><?php endif; ?>
            </div>
        </header>
        <div class="pt-media__grid">
            <a class="pt-media__main" <?= $mediaLink($mediaLead) ?> data-kind="<?= $mediaE((string) $mediaLead['kind']) ?>">
                <?php if ($mediaLead['cover'] !== ''): ?><?= Media::picture((string) $mediaLead['cover'], '', null, null, 'pt-media__img', true, '(max-width: 900px) 100vw, 60vw') ?><?php endif; ?>
                <span class="pt-media__top">
                    <span class="pt-media__tag"><?= $mediaE(trim(($mediaLead['source'] !== '' ? $mediaLead['source'] . ' · ' : '') . $mediaLabel($mediaLead))) ?></span>
                    <?php if ($mediaLead['duration'] !== ''): ?><span class="pt-media__dur-pill"><?= $mediaE((string) $mediaLead['duration']) ?></span><?php endif; ?>
                </span>
                <span class="pt-media__bot">
                    <span class="pt-media__play" aria-hidden="true">▶</span>
                    <span class="pt-media__title"><?= $mediaE((string) $mediaLead['title']) ?></span>
                </span>
            </a>
            <div class="pt-media__side">
                <?php if ($mediaRest !== []): ?>
                <ol class="pt-media__list">
                    <?php foreach ($mediaRest as $item): ?>
                        <li data-kind="<?= $mediaE((string) $item['kind']) ?>">
                            <a <?= $mediaLink($item) ?>>
                                <?php if ($item['kind'] === 'podcast'): ?>
                                    <span class="pt-media__wave" aria-hidden="true"><svg viewBox="0 0 64 22" width="60" height="20" fill="currentColor"><rect x="2" y="8" width="4" height="6" rx="1"/><rect x="10" y="3" width="4" height="16" rx="1"/><rect x="18" y="6" width="4" height="10" rx="1"/><rect x="26" y="1" width="4" height="20" rx="1"/><rect x="34" y="5" width="4" height="12" rx="1"/><rect x="42" y="2" width="4" height="18" rx="1"/><rect x="50" y="7" width="4" height="8" rx="1"/><rect x="58" y="4" width="4" height="14" rx="1"/></svg><span><?= $mediaE(t('Подкаст')) ?></span></span>
                                <?php else: ?>
                                    <span class="pt-ph pt-ph--16x9"><?php if ($item['cover'] !== ''): ?><?= Media::picture((string) $item['cover'], '', null, null, '', true, '136px') ?><?php endif; ?><?php if ($item['duration'] !== ''): ?><span class="pt-media__dur"><?= $mediaE((string) $item['duration']) ?></span><?php endif; ?></span>
                                <?php endif; ?>
                                <span>
                                    <span class="pt-media__label"><?= $mediaE($mediaLabel($item)) ?></span>
                                    <span class="pt-hl"><?= $mediaE((string) $item['title']) ?></span>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <?php endif; ?>
                <?php if ($mediaCta !== null): ?>
                <div class="pt-media__cta">
                    <span><strong><?= $mediaE((string) $mediaCta['title']) ?></strong><?php if ($mediaCta['note'] !== ''): ?><span><?= $mediaE((string) $mediaCta['note']) ?></span><?php endif; ?></span>
                    <a class="pt-media__cta-btn" href="<?= $mediaE((string) $mediaCta['url']) ?>"><?= $mediaE((string) $mediaCta['text']) ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
