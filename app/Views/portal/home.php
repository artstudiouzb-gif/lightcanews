<?php
/**
 * Фиксированная главная портала — пока на странице-главной нет блоков
 * «Секция портала». Секции те же, что у блоков (`_home_<ключ>.php`), поэтому
 * переход главной на конструктор не меняет её вида.
 *
 * @var array<string, mixed> $home   Portal::home()
 * @var string $metaTitle
 * @var string $metaDescription
 */

$adPlace = 'home';
require __DIR__ . '/_top.php';
?>
<h1 class="pt-visually-hidden"><?= htmlspecialchars($metaTitle !== '' ? $metaTitle : t('Главное в отрасли'), ENT_QUOTES) ?></h1>
<?php
foreach (\App\Core\Portal\Portal::DEFAULT_SECTIONS as $secKey) {
    $sec = $home;
    $secTitle = '';
    require __DIR__ . '/_home_' . $secKey . '.php';
}
require __DIR__ . '/_bottom.php';
