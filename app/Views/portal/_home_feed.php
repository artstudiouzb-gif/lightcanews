<?php
/**
 * Секция «Лента новостей»: последние новости по времени, объявление после третьей и партнёрский материал под лентой.
 * Общая для фиксированной главной и блока «Секция портала».
 *
 * @var array<string, mixed> $sec      Portal::section()
 * @var string               $secTitle свой заголовок секции ('' — по умолчанию)
 * @var bool|null            $secInner внутри общей секции «Главное и лента» — без своей обёртки
 * @var string|null          $secUid   отличает id заголовков, когда секций на странице несколько
 */

use App\Core\Locale;

$feed = (array) ($sec['feed'] ?? []);
$newsUrl = Locale::url('news');
// В общей секции свой заголовок принадлежит «Главному», лента — по умолчанию.
$feedTitle = empty($secInner) && $secTitle !== '' ? $secTitle : t('Лента новостей');
$secUid = (string) ($secUid ?? 'home');
?>
<?php if (empty($secInner)): ?><div class="pt-wrap pt-feed-solo"><?php endif; ?>
    <aside class="pt-feed" aria-labelledby="pt-feed-title-<?= $secUid ?>">
        <header class="pt-sec-head pt-sec-head--sm">
            <h2 id="pt-feed-title-<?= $secUid ?>"><?= htmlspecialchars($feedTitle, ENT_QUOTES) ?></h2>
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
<?php if (empty($secInner)): ?></div><?php endif; ?>
