<?php
/**
 * Секция «Нити региона» — по странам.
 * Общая для фиксированной главной и блока «Секция портала».
 *
 * @var array<string, mixed> $sec      Portal::section()
 * @var string               $secTitle свой заголовок секции ('' — по умолчанию)
 */

use App\Core\Locale;

$regions = (array) ($sec['regions'] ?? []);
?>
<?php if ($regions !== []): ?>
<section class="pt-wrap pt-sec" aria-labelledby="pt-reg-title" data-pt-regions>
    <header class="pt-sec-head"><h2 id="pt-reg-title"><?= htmlspecialchars($secTitle !== '' ? $secTitle : t('Нити региона'), ENT_QUOTES) ?></h2><span class="pt-meta"><?= htmlspecialchars(t('По странам'), ENT_QUOTES) ?></span></header>
    <div class="pt-filters" role="group" aria-label="<?= htmlspecialchars(t('Страны'), ENT_QUOTES) ?>" hidden>
        <button type="button" aria-pressed="true" data-pt-region="all"><?= htmlspecialchars(t('Все страны'), ENT_QUOTES) ?></button>
        <?php foreach ($regions as $i => $region): ?>
            <button type="button" aria-pressed="false" data-pt-region="<?= (int) $i ?>"><?php if ($region['mark'] !== ''): ?><b><?= $region['mark'] ?></b><?php endif; ?><?= htmlspecialchars((string) $region['name'], ENT_QUOTES) ?></button>
        <?php endforeach; ?>
    </div>
    <div class="pt-regions">
        <?php foreach ($regions as $i => $region): ?>
            <?php $regionLead = $region['items'][0]; ?>
            <article class="pt-region" data-pt-region-card="<?= (int) $i ?>">
                <a class="pt-region__hero" href="<?= htmlspecialchars((string) $regionLead['url'], ENT_QUOTES) ?>" tabindex="-1" aria-hidden="true">
                    <?php $photoCard = $regionLead; $photoRatio = '16x9'; $photoSizes = '(max-width: 720px) 100vw, 33vw'; require __DIR__ . '/_photo.php'; ?>
                    <span class="pt-region__id"><?php if ($region['mark'] !== ''): ?><b><?= $region['mark'] ?></b><?php endif; ?><?= htmlspecialchars((string) $region['name'], ENT_QUOTES) ?></span>
                </a>
                <div class="pt-region__body">
                    <h3 class="pt-region__lead"><a href="<?= htmlspecialchars((string) $regionLead['url'], ENT_QUOTES) ?>"><span class="pt-hl"><?= htmlspecialchars((string) $regionLead['title'], ENT_QUOTES) ?></span></a></h3>
                    <span class="pt-meta"><?= htmlspecialchars((string) $regionLead['date'], ENT_QUOTES) ?></span>
                    <?php if (count($region['items']) > 1): ?>
                    <ul>
                        <?php foreach (array_slice($region['items'], 1) as $card): ?>
                            <li><a href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES) ?>"><span><?= htmlspecialchars((string) $card['title'], ENT_QUOTES) ?></span><span class="pt-meta"><?= htmlspecialchars((string) $card['date'], ENT_QUOTES) ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <a class="pt-region__foot" href="<?= htmlspecialchars((string) $region['url'], ENT_QUOTES) ?>"><?= htmlspecialchars(t('Открыть ленту') . ': ' . $region['name'], ENT_QUOTES) ?></a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
