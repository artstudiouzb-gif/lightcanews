<?php

/*
 * Счётчики аналитики (Яндекс Метрика, Google Analytics), согласие на cookie и
 * глобальный произвольный JS. Общий для прежнего подвала и подвала портала:
 * вторая копия разъехалась бы с первой — так портал и остался без счётчиков.
 * Подключается require из подвала (общая область видимости вьюхи).
 */

use App\Models\Setting;

$cspNonce = \App\Core\SecurityHeaders::nonce();
$analyticsInit = \App\Core\Analytics::hasAny() ? \App\Core\Analytics::initScript() : '';
$consentRequired = Setting::get('cookie_consent_enabled', '0') === '1';
if (!isset($privacyUrl)) {
    $privacyUrl = '';
    $privacyPageId = (int) Setting::get('privacy_policy_page_id', '');
    if ($privacyPageId > 0) {
        $privacyPage = \App\Models\Page::findById($privacyPageId);
        if ($privacyPage && ($privacyPage['status'] ?? '') === 'published') {
            $privacyUrl = \App\Core\Locale::url($privacyPage['slug']);
        }
    }
}
?>
<?php if ($analyticsInit !== ''): ?>
<?php // Код счётчиков инертен (type text/plain); consent.js активирует его,
      // перенося nonce с держателя на создаваемый <script> (CSP). ?>
<script type="text/plain" id="analytics-init" nonce="<?= $cspNonce ?>"><?= $analyticsInit ?></script>
<script type="application/json" id="consent-config"><?= json_encode([
    'required' => $consentRequired,
    'privacyUrl' => $privacyUrl,
    'labels' => [
        'aria' => t('Согласие на использование cookie'),
        'text' => t('Мы используем cookie для аналитики. Продолжая, вы соглашаетесь с их использованием.'),
        'privacy' => t('Политика конфиденциальности'),
        'accept' => t('Принять'),
        'decline' => t('Отклонить'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?></script>
<script src="<?= htmlspecialchars(\App\Core\Asset::url('/assets/js/consent.js'), ENT_QUOTES) ?>" defer></script>
<?php endif; ?>
<?php // Глобальный произвольный JS (группа 6, супер-админ). ?>
<?php $globalJs = Setting::get('custom_js_global', ''); ?>
<?php if (trim($globalJs) !== ''): ?>
<script nonce="<?= $cspNonce ?>"><?= $globalJs ?></script>
<?php endif; ?>
