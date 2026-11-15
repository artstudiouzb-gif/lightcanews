<?php
/**
 * Секция «Главное в отрасли» и лента новостей справа — две колонки, ширина
 * ленты из «Дизайна». Части те же, что у отдельных секций «Главное» и
 * «Лента» (`_home_main.php`, `_home_feed.php`): разметка одна на оба способа.
 * Общая для фиксированной главной и блока «Секция портала».
 *
 * @var array<string, mixed> $sec      Portal::section()
 * @var string               $secTitle свой заголовок секции ('' — по умолчанию)
 */

$secInner = true;
?>
<div class="pt-wrap pt-home-top">
<?php require __DIR__ . '/_home_main.php'; ?>
<?php require __DIR__ . '/_home_feed.php'; ?>
</div>
<?php $secInner = false; ?>
