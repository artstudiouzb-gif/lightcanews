<?php

use App\Core\Csrf;
use App\Core\Portal\MarketQuotes;

$pageTitle = 'Котировки';
$activeNav = 'quotes';
require __DIR__ . '/../layout/header.php';

/** @var array{items: list<array<string, mixed>>, source: string} $quotes */
/** @var list<array<string, mixed>> $languages */
/** @var int $ratesAt */
$esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);
$rows = $quotes['items'];
// Пара пустых строк под новые котировки: заполнили — строка добавилась.
for ($pad = 0; $pad < 2 && count($rows) < MarketQuotes::MAX_ITEMS; $pad++) {
    $rows[] = ['names' => [], 'unit' => '', 'value' => '', 'prev' => '', 'updated' => ''];
}
?>
<div class="admin-builder-workspace">
    <div class="header-builder__group">
        <h3>Курсы ЦБ Узбекистана</h3>
        <p class="form-hint"><?= $ratesAt > 0 ? 'Обновлены ' . $esc(\App\Core\DateFormatter::format($ratesAt, 'd.m.Y H:i')) . '.' : 'Ещё ни разу не загружались.' ?> Курсы обновляются сами: раз в <?= (int) (\App\Core\CurrencyRates::REFRESH_AFTER / 3600) ?> часа после захода посетителя на сайт, а при настроенном cron — по расписанию.</p>
        <form method="post" action="/admin/quotes/rates">
            <?= Csrf::field() ?>
            <button type="submit" class="btn">Обновить курсы сейчас</button>
        </form>
    </div>
    <p class="form-hint">Котировки идут в полосе «Курсы валют» на главной рядом с курсами ЦБ. Введите новое значение: прежнее запомнится само, и сайт покажет изменение в процентах и дату обновления. Строка без значения на сайт не выводится; чтобы удалить строку, очистите её название и значение. Число можно писать как в документе: «71,25» или «1 840».</p>
    <form method="post" action="/admin/quotes" class="form-grid">
        <?= Csrf::field() ?>
        <?php foreach ($rows as $i => $row): ?>
            <?php $n = 'items[' . $i . ']'; $id = 'quote-' . $i; ?>
            <fieldset class="bf-group">
                <legend><?= $esc((string) (($row['names']['ru'] ?? '') !== '' ? $row['names']['ru'] : 'Новая котировка')) ?></legend>
                <?php foreach ($languages as $lang): ?>
                    <?php $code = (string) $lang['code']; ?>
                    <div class="form-field">
                        <label for="<?= $id ?>-name-<?= $esc($code) ?>">Название (<?= $esc((string) $lang['name']) ?>)</label>
                        <input id="<?= $id ?>-name-<?= $esc($code) ?>" type="text" name="<?= $n ?>[names][<?= $esc($code) ?>]" maxlength="60" value="<?= $esc((string) ($row['names'][$code] ?? '')) ?>">
                    </div>
                <?php endforeach; ?>
                <div class="form-field">
                    <label for="<?= $id ?>-value">Значение</label>
                    <input id="<?= $id ?>-value" type="text" inputmode="decimal" name="<?= $n ?>[value]" maxlength="20" value="<?= $esc((string) $row['value']) ?>">
                    <?php if ($row['prev'] !== '' || $row['updated'] !== ''): ?>
                        <span class="form-hint">Прежнее: <?= $esc($row['prev'] !== '' ? (string) $row['prev'] : '—') ?><?= $row['updated'] !== '' ? ' · обновлено ' . $esc((string) $row['updated']) : '' ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-field">
                    <label for="<?= $id ?>-unit">Единица</label>
                    <input id="<?= $id ?>-unit" type="text" name="<?= $n ?>[unit]" maxlength="16" value="<?= $esc((string) $row['unit']) ?>" placeholder="$/кг">
                </div>
            </fieldset>
        <?php endforeach; ?>
        <div class="form-field">
            <label for="quote-source">Источник</label>
            <input id="quote-source" type="text" name="source" maxlength="120" value="<?= $esc($quotes['source']) ?>" placeholder="ICE, UZEX, данные редакции">
            <span class="form-hint">Печатается мелко под котировками.</span>
        </div>
        <div class="form-actions"><button type="submit" class="btn btn--primary">Сохранить</button></div>
    </form>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
