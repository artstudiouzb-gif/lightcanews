<?php
/**
 * Секция «Точка зрения».
 * Общая для фиксированной главной и блока «Секция портала».
 *
 * @var array<string, mixed> $sec      Portal::section()
 * @var string               $secTitle свой заголовок секции ('' — по умолчанию)
 */

use App\Core\Locale;

$voices = (array) ($sec['voices'] ?? []);
?>
<?php if (!empty($voices['items'])): ?>
<section class="pt-wrap pt-sec" aria-labelledby="pt-voices-title">
    <header class="pt-sec-head"><h2 id="pt-voices-title"><?= htmlspecialchars($secTitle !== '' ? $secTitle : (string) $voices['title'], ENT_QUOTES) ?></h2><a class="pt-more" href="<?= htmlspecialchars((string) $voices['url'], ENT_QUOTES) ?>"><?= htmlspecialchars(t('Все колонки'), ENT_QUOTES) ?></a></header>
    <div class="pt-row3">
        <?php foreach ($voices['items'] as $card): ?>
            <a class="pt-opinion" href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES) ?>">
                <span class="pt-opinion__top">
                    <?php if ($card['author'] !== ''): ?>
                    <span class="pt-opinion__author">
                        <?php if ($card['author_photo'] !== ''): ?><?= \App\Core\Media::picture((string) $card['author_photo'], '', null, null, 'pt-opinion__avatar', true, '40px') ?><?php endif; ?>
                        <span><strong><?= htmlspecialchars((string) $card['author'], ENT_QUOTES) ?></strong><?php if ($card['author_role'] !== ''): ?><span class="pt-meta"><?= htmlspecialchars((string) $card['author_role'], ENT_QUOTES) ?></span><?php endif; ?></span>
                    </span>
                    <?php endif; ?>
                    <span class="pt-opinion__badge"><?= htmlspecialchars(t('Колонка'), ENT_QUOTES) ?></span>
                </span>
                <span class="pt-hl">«<?= htmlspecialchars((string) $card['title'], ENT_QUOTES) ?>»</span>
                <span class="pt-opinion__foot"><span class="pt-meta"><?= htmlspecialchars(sprintf(t('%d мин чтения'), (int) $card['reading']), ENT_QUOTES) ?></span><span class="pt-rubric"><?= htmlspecialchars(t('Читать'), ENT_QUOTES) ?></span></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
