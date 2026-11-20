<?php
/*
 * Конец <body>, общий для прежнего шаблона и шаблона портала: push-уведомления,
 * замер скорости у посетителей, счётчики, «Свой JS» страницы и упреждающая
 * загрузка ссылок. Вторая копия разъехалась бы с первой — так портал и
 * остался без всего этого. Подключается require (общая область видимости).
 */
?>
<?php if (\App\Core\WebPush::isEnabled()): ?>
<script type="application/json" id="push-config"><?= json_encode([
    'enabled' => true,
    'autoPrompt' => \App\Models\Setting::get('webpush_auto_prompt', '1') === '1',
    'promptDelay' => (int) \App\Models\Setting::get('webpush_prompt_delay', '15'),
    'labels' => [
        'off' => t('Уведомления о новостях'),
        'on' => t('Уведомления включены'),
        // Карточка предложения подписки рисуется скриптом, поэтому её текст
        // тоже приходит переведённым отсюда, а не литералом в JS.
        'promptText' => t('Будьте в курсе главных событий! Подпишитесь на мгновенные push-уведомления о новых публикациях.'),
        'enable' => t('Включить уведомления'),
        'later' => t('Позже'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
</script>
<script src="<?= htmlspecialchars(\App\Core\Asset::url('/assets/js/push.js'), ENT_QUOTES) ?>" defer></script>
<?php endif; ?>
<?php // Core Web Vitals с реальных посетителей. Лабораторные замеры не
      // показывают INP вовсе, поэтому поле — единственный источник правды
      // по отзывчивости. Отправляется одним sendBeacon при скрытии вкладки;
      // ни адреса страницы, ни идентификатора посетителя не собирается. ?>
<?php if (\App\Core\WebVitals::enabled()): ?>
<script type="application/json" id="web-vitals-config"><?= json_encode([
    'endpoint' => '/_vitals',
    'rate' => \App\Core\WebVitals::sampleRate(),
    'page' => \App\Core\WebVitals::pageKind($viewTemplate ?? ''),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?></script>
<script src="<?= htmlspecialchars(\App\Core\Asset::url('/assets/vendor/web-vitals/web-vitals.js'), ENT_QUOTES) ?>" defer></script>
<script src="<?= htmlspecialchars(\App\Core\Asset::url('/assets/js/web-vitals-report.js'), ENT_QUOTES) ?>" defer></script>
<?php endif; ?>
<?php require __DIR__ . '/_analytics.php'; ?>
<?php if (!empty($page['custom_js']) && !empty($page['id'])): ?>
<?php
$pageJsUrls = \App\Core\CustomAssetHelper::resolveJsUrls((string) $page['custom_js'], (int) $page['id']);
\App\Core\SecurityHeaders::allowPageAssets([], \App\Core\CustomAssetHelper::originsOf($pageJsUrls));
?>
<?php foreach ($pageJsUrls as $pageJsUrl): ?>
<script src="<?= htmlspecialchars($pageJsUrl, ENT_QUOTES) ?>" defer></script>
<?php endforeach; ?>
<?php endif; ?>
<?php // Упреждающая загрузка внутренних страниц (Speculation Rules). Печатается
      // последней: правила касаются ссылок всей страницы, а не окружения. Без
      // nonce намеренно — см. App\Core\SpeculationRules. ?>
<?= \App\Core\SpeculationRules::scriptHtml() ?>
