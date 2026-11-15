<?php
/**
 * Секция «Instagram — ролики»: полоса вертикальных карточек 9:16, как
 * Reels/Shorts. Кадры — из сохранённой копии ленты Instagram; карточка ведёт
 * в публикацию (встраивать плеер Instagram нельзя: чужой скрипт на странице и
 * запрос в сеть при каждом показе). Полоса листается вбок пальцем, колесом и
 * клавиатурой — это обычная прокрутка с привязкой, скрипт не нужен.
 * Общая для фиксированной главной и блока «Секция портала».
 *
 * @var array<string, mixed> $sec      Portal::section()
 * @var string               $secTitle свой заголовок секции ('' — по умолчанию)
 * @var string|null          $secUid
 */

use App\Core\InstagramFeed;
use App\Core\Media;

$reels = (array) ($sec['reels'] ?? []);
$secUid = (string) ($secUid ?? 'home');
$reelsE = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);
$reelsProfile = InstagramFeed::profileUrl();
if ($reelsProfile !== '' && !\App\Core\UrlGuard::isSafeLink($reelsProfile)) {
    $reelsProfile = '';
}
?>
<?php if ($reels !== []): ?>
<section class="pt-reels" aria-labelledby="pt-reels-title-<?= $secUid ?>">
    <div class="pt-wrap">
        <header class="pt-sec-head">
            <h2 id="pt-reels-title-<?= $secUid ?>"><?= $reelsE($secTitle !== '' ? $secTitle : t('Мы в Instagram')) ?></h2>
            <?php if ($reelsProfile !== ''): ?><a class="pt-more" href="<?= $reelsE($reelsProfile) ?>" target="_blank" rel="noopener"><?= $reelsE(t('Все публикации в Instagram')) ?></a><?php endif; ?>
        </header>
        <ul class="pt-reels__row" tabindex="0" aria-label="<?= $reelsE(t('Публикации Instagram — прокрутка вбок')) ?>">
            <?php foreach ($reels as $reel): ?>
                <?php
                $reelTitle = trim((string) $reel['title']) !== '' ? (string) $reel['title'] : \App\Core\InstagramPostTitle::fallback((string) $reel['caption']);
                $reelLabel = ($reelTitle !== '' ? $reelTitle : t('Публикация в Instagram')) . ' — ' . t('откроется в Instagram');
                ?>
                <li>
                    <a class="pt-reel" href="<?= $reelsE((string) $reel['permalink']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= $reelsE($reelLabel) ?>">
                        <?= Media::picture((string) $reel['image'], '', null, null, 'pt-reel__img', true, '(max-width: 720px) 45vw, 220px') ?>
                        <?php if ($reel['type'] === 'VIDEO'): ?><span class="pt-reel__play" aria-hidden="true">▶</span><?php endif; ?>
                        <?php if ($reelTitle !== ''): ?><span class="pt-reel__title" aria-hidden="true"><?= $reelsE($reelTitle) ?></span><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>
