<?php
/**
 * Секция «Курсы валют»: курсы ЦБ и котировки сырья.
 * Общая для фиксированной главной и блока «Секция портала».
 *
 * @var array<string, mixed> $sec      Portal::section()
 * @var string               $secTitle свой заголовок секции ('' — по умолчанию)
 */

use App\Core\DateFormatter;
use App\Core\Portal\MarketQuotes;

$rates = $sec['rates'] ?? null;
$quotes = is_array($sec['quotes'] ?? null) ? $sec['quotes'] : [];
$quotesSource = (string) ($sec['quotes_source'] ?? '');
?>
<?php if ($rates !== null || $quotes !== []): ?>
<section class="pt-fx" aria-label="<?= htmlspecialchars(t('Курсы валют и котировки'), ENT_QUOTES) ?>" data-no-translit>
    <div class="pt-wrap pt-fx__row">
        <?php if ($rates !== null): ?>
            <p class="pt-fx__label"><?= htmlspecialchars(t('Курс ЦБ Узбекистана на') . ' ' . $rates['date'], ENT_QUOTES) ?></p>
            <dl class="pt-fx__list">
                <?php foreach ($rates['rates'] as $fxCode => $fxRate): ?>
                    <div class="pt-fx__item"><dt>USD/<?= htmlspecialchars((string) $fxCode, ENT_QUOTES) ?></dt><dd><?= number_format((float) $fxRate, 2, ',', "\u{00A0}") ?></dd></div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
        <?php if ($quotes !== []): ?>
            <p class="pt-fx__label"><?= htmlspecialchars(t('Сырьё'), ENT_QUOTES) ?></p>
            <dl class="pt-fx__list">
                <?php foreach ($quotes as $q): ?>
                    <?php
                    $qChange = $q['change'];
                    $qDir = $qChange === null || abs($qChange) < 0.005 ? 'flat' : ($qChange > 0 ? 'up' : 'down');
                    $qDate = $q['updated'] !== '' ? DateFormatter::format($q['updated'], 'd.m') : '';
                    ?>
                    <div class="pt-fx__item pt-fx__item--quote"<?= $qDate !== '' ? ' title="' . htmlspecialchars(t('Обновлено') . ' ' . $qDate, ENT_QUOTES) . '"' : '' ?>>
                        <dt><?= htmlspecialchars($q['name'], ENT_QUOTES) ?></dt>
                        <dd>
                            <?= MarketQuotes::format($q['value']) ?><?php if ($q['unit'] !== ''): ?> <span class="pt-fx__unit"><?= htmlspecialchars($q['unit'], ENT_QUOTES) ?></span><?php endif; ?>
                            <?php if ($qChange !== null): ?>
                                <span class="pt-fx__chg pt-fx__chg--<?= $qDir ?>"><span aria-hidden="true"><?= ['up' => '▲', 'down' => '▼', 'flat' => '•'][$qDir] ?></span> <?= ($qChange > 0 ? '+' : ($qChange < 0 ? '−' : '')) . number_format(abs($qChange), 1, ',', '') ?>&nbsp;%</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                <?php endforeach; ?>
            </dl>
            <?php if ($quotesSource !== ''): ?>
                <p class="pt-fx__src"><?= htmlspecialchars(t('Источник') . ': ' . $quotesSource, ENT_QUOTES) ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
