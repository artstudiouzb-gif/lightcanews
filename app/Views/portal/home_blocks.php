<?php
/**
 * Главная портала, собранная в конструкторе: блоки «Секция портала» и любые
 * обычные блоки между ними. Секции несут свою ширину (`pt-wrap`), обычные
 * блоки получают колонку сайта правилом `.pt-home-blocks` в portal.css.
 *
 * @var array<string, mixed> $page
 * @var string $content  готовая разметка блоков
 * @var string $blockCss scoped CSS блоков
 * @var string $metaTitle
 * @var string $metaDescription
 */

$portalBlocks = true;
$adPlace = 'home';
require __DIR__ . '/_top.php';
?>
<h1 class="pt-visually-hidden"><?= htmlspecialchars($metaTitle !== '' ? $metaTitle : t('Главное в отрасли'), ENT_QUOTES) ?></h1>
<div class="pt-blocks pt-home-blocks"><?= $content ?></div>
<?php require __DIR__ . '/_bottom.php'; ?>
