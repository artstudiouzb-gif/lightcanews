<?php
/**
 * Секция «Партнёрские материалы»: новости с отметкой партнёрского материала.
 * Нет таких новостей — секция не печатается.
 * Общая для фиксированной главной и блока «Секция портала».
 *
 * @var array<string, mixed> $sec      Portal::section()
 * @var string               $secTitle свой заголовок секции ('' — по умолчанию)
 * @var string|null          $secUid
 */

$partners = (array) ($sec['partners'] ?? []);
$secUid = (string) ($secUid ?? 'home');
?>
<?php if ($partners !== []): ?>
<section class="pt-picks pt-picks--partners" aria-labelledby="pt-partners-title-<?= $secUid ?>">
    <div class="pt-wrap">
        <header class="pt-sec-head"><h2 id="pt-partners-title-<?= $secUid ?>"><?= htmlspecialchars($secTitle !== '' ? $secTitle : t('Партнёрские материалы'), ENT_QUOTES) ?></h2></header>
        <div class="pt-picks__row">
            <?php foreach ($partners as $card): ?>
                <a class="pt-pcard" href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES) ?>">
                    <?php $photoCard = $card; $photoRatio = '4x3'; $photoSizes = '(max-width: 720px) 100vw, 25vw'; require __DIR__ . '/_photo.php'; ?>
                    <span class="pt-pcard__body">
                        <span class="pt-pcard__meta"><span class="pt-ad-mark pt-sponsored"><?= htmlspecialchars(t('Партнёрский материал'), ENT_QUOTES) ?></span></span>
                        <span class="pt-hl"><?= htmlspecialchars((string) $card['title'], ENT_QUOTES) ?></span>
                        <?php if ($card['excerpt'] !== ''): ?><span class="pt-pcard__text"><?= htmlspecialchars((string) $card['excerpt'], ENT_QUOTES) ?></span><?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
