<?php
/**
 * Секция «Выбор редакции».
 * Общая для фиксированной главной и блока «Секция портала».
 *
 * @var array<string, mixed> $sec      Portal::section()
 * @var string               $secTitle свой заголовок секции ('' — по умолчанию)
 */

use App\Core\Locale;

$picks = (array) ($sec['picks'] ?? []);
?>
<?php if ($picks !== []): ?>
<section class="pt-picks" aria-labelledby="pt-picks-title">
    <div class="pt-wrap">
        <header class="pt-sec-head"><h2 id="pt-picks-title"><?= htmlspecialchars($secTitle !== '' ? $secTitle : t('Выбор редакции'), ENT_QUOTES) ?></h2></header>
        <div class="pt-picks__row">
            <?php foreach ($picks as $card): ?>
                <a class="pt-pcard" href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES) ?>">
                    <?php $photoCard = $card; $photoRatio = '4x3'; $photoSizes = '(max-width: 720px) 100vw, 25vw'; require __DIR__ . '/_photo.php'; ?>
                    <span class="pt-pcard__body">
                        <span class="pt-pcard__meta"><span><?= htmlspecialchars((string) $card['rubric'], ENT_QUOTES) ?></span><span><?= htmlspecialchars(sprintf(t('%d мин чтения'), (int) $card['reading']), ENT_QUOTES) ?></span></span>
                        <span class="pt-hl"><?= htmlspecialchars((string) $card['title'], ENT_QUOTES) ?></span>
                        <?php if ($card['excerpt'] !== ''): ?><span class="pt-pcard__text"><?= htmlspecialchars((string) $card['excerpt'], ENT_QUOTES) ?></span><?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
