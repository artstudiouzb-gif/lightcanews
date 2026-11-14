<?php
/**
 * Материала нет на выбранном языке — предлагаем доступные версии.
 *
 * @var array<int, array{code:string,name:string,url:string}> $alternatives
 */

use App\Core\Locale;

$metaTitle = t('Перевод пока недоступен');
$metaDescription = t('Этот материал ещё не переведён на выбранный язык.');
$robotsNoindex = true;

require __DIR__ . '/_top.php';
?>
<div class="pt-wrap pt-sec pt-notice">
    <p class="pt-rubric"><?= htmlspecialchars(t('Доступен на другом языке'), ENT_QUOTES) ?></p>
    <h1 class="pt-notice__title"><?= htmlspecialchars(t('Перевод пока недоступен'), ENT_QUOTES) ?></h1>
    <p><?= htmlspecialchars(t('Этот материал ещё не переведён на выбранный язык. Вы можете открыть его на другом доступном языке.'), ENT_QUOTES) ?></p>
    <div class="pt-notice__actions">
        <?php foreach ($alternatives as $alternative): ?>
            <a class="pt-btn pt-btn--brand" href="<?= htmlspecialchars($alternative['url'], ENT_QUOTES) ?>" hreflang="<?= htmlspecialchars($alternative['code'], ENT_QUOTES) ?>"><?= htmlspecialchars(t('Посмотреть на языке'), ENT_QUOTES) ?>: <?= htmlspecialchars($alternative['name'], ENT_QUOTES) ?></a>
        <?php endforeach; ?>
        <a class="pt-btn" href="<?= htmlspecialchars(Locale::url('/'), ENT_QUOTES) ?>"><?= htmlspecialchars(t('Вернуться на главную'), ENT_QUOTES) ?></a>
    </div>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
