<?php
/**
 * Секция «Курсы валют»: курсы ЦБ и котировки сырья.
 * Общая для фиксированной главной и блока «Секция портала».
 *
 * Полоса во всю ширину контента: группы «Сум», «Доллар в регионе» и
 * «Сырьё» стоят одной сеткой, ширину группы задаёт число её ячеек
 * (класс pt-fx__group--nN), поэтому ячейки во всех группах одной ширины,
 * а без котировок валюты растягиваются на всю полосу сами.
 *
 * @var array<string, mixed> $sec      Portal::section()
 * @var string               $secTitle свой заголовок секции ('' — по умолчанию)
 */

use App\Core\DateFormatter;
use App\Core\Portal\MarketQuotes;

$rates = $sec['rates'] ?? null;
$quotes = is_array($sec['quotes'] ?? null) ? $sec['quotes'] : [];
$quotesSource = (string) ($sec['quotes_source'] ?? '');
$fxE = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);
// Изменение: стрелка и цвет для глаза, слово для диктора.
$fxChange = static function (?float $change, string $text) use ($fxE): string {
    if ($change === null) {
        return '';
    }
    $dir = abs($change) < 0.005 ? 'flat' : ($change > 0 ? 'up' : 'down');
    $word = ['up' => t('рост'), 'down' => t('снижение'), 'flat' => t('без изменений')][$dir];

    return '<span class="pt-fx__chg pt-fx__chg--' . $dir . '"><span aria-hidden="true">' . ['up' => '▲', 'down' => '▼', 'flat' => '•'][$dir]
        . '</span> <span class="pt-visually-hidden">' . $fxE($word) . '</span> ' . $text . '</span>';
};
$fxNum = static fn (float $v): string => number_format($v, 2, ',', "\u{00A0}");
$fxGroups = [];
if ($rates !== null) {
    foreach ([
        'sum' => [t('Сум · ЦБ Узбекистана'), static fn (string $c): string => $c . '/UZS'],
        'region' => [t('Доллар в регионе'), static fn (string $c): string => 'USD/' . $c],
    ] as $fxKey => [$fxTitle, $fxPair]) {
        $fxCells = [];
        foreach ((array) ($rates[$fxKey] ?? []) as $fxCode => $fxItem) {
            $fxCells[] = [
                'name' => $fxPair((string) $fxCode),
                'value' => $fxNum((float) $fxItem['value']),
                'change' => $fxChange($fxItem['change'], $fxItem['change'] === null ? '' : $fxNum(abs((float) $fxItem['change']))),
            ];
        }
        if ($fxCells !== []) {
            $fxGroups[] = ['title' => $fxTitle, 'note' => $fxKey === 'sum' && $rates['date'] !== '' ? t('на') . ' ' . $rates['date'] : '', 'cells' => $fxCells, 'translit' => false];
        }
    }
}
if ($quotes !== []) {
    $fxCells = [];
    $fxLatest = '';
    foreach ($quotes as $q) {
        $fxLatest = max($fxLatest, (string) $q['updated']);
        $fxCells[] = [
            'name' => (string) $q['name'],
            'value' => MarketQuotes::format($q['value']) . ($q['unit'] !== '' ? ' <span class="pt-fx__unit">' . $fxE($q['unit']) . '</span>' : ''),
            'change' => $fxChange($q['change'], $q['change'] === null ? '' : number_format(abs((float) $q['change']), 1, ',', '') . "\u{00A0}%"),
        ];
    }
    $fxGroups[] = ['title' => t('Сырьё'), 'note' => $fxLatest !== '' ? t('на') . ' ' . DateFormatter::format($fxLatest, 'd.m') : '', 'cells' => $fxCells, 'translit' => true];
}
?>
<?php if ($fxGroups !== []): ?>
<section class="pt-fx" aria-label="<?= $fxE(t('Курсы валют и котировки')) ?>">
    <div class="pt-wrap pt-fx__wrap">
        <div class="pt-fx__grid">
            <?php foreach ($fxGroups as $fxGroup): ?>
            <div class="pt-fx__group pt-fx__group--n<?= min(8, count($fxGroup['cells'])) ?>">
                <p class="pt-fx__head"><?= $fxE($fxGroup['title']) ?><?php if ($fxGroup['note'] !== ''): ?> <span><?= $fxE($fxGroup['note']) ?></span><?php endif; ?></p>
                <dl class="pt-fx__cells"<?= $fxGroup['translit'] ? '' : ' data-no-translit' ?>>
                    <?php foreach ($fxGroup['cells'] as $fxCell): ?>
                    <div class="pt-fx__cell"><dt><?= $fxE($fxCell['name']) ?></dt><dd><span class="pt-fx__val"><?= $fxCell['value'] ?></span><?= $fxCell['change'] ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="pt-fx__foot">
            <?php if ($rates !== null): ?>
            <span><?= $fxE(t('Курсы')) ?>: <a href="https://cbu.uz/" rel="noopener" target="_blank"><?= $fxE(t('ЦБ Узбекистана')) ?></a>, <?= $fxE(t('изменение к прошлому дню')) ?>. <?= $fxE(t('Обновлено в')) ?> <?= $fxE(DateFormatter::format((int) $rates['fetched_at'], 'H:i')) ?>.</span>
            <?php endif; ?>
            <?php if ($quotesSource !== ''): ?>
            <span><?= $fxE(t('Сырьё') . ' — ' . t('Источник') . ': ' . $quotesSource) ?></span>
            <?php endif; ?>
        </p>
    </div>
</section>
<?php endif; ?>
