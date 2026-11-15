<?php
/**
 * Секция подписки: канал Telegram из соцкнопок шапки или подписка на почту.
 * Общая для фиксированной главной и блока «Секция портала».
 *
 * @var array<string, mixed> $sec      Portal::section()
 * @var string               $secTitle свой заголовок секции ('' — по умолчанию)
 */

use App\Core\Locale;

?>
<?php
// Канал в Telegram берётся из соцкнопок шапки — тех же, что в подвале. Нет
// канала — остаётся подписка на почту, пустого баннера не бывает.
$tgUrl = '';
foreach ((array) (\App\Core\HeaderConfig::get()['social_buttons'] ?? []) as $tgBtn) {
    $tgCandidate = (string) ($tgBtn['url'] ?? '');
    if (strtolower((string) ($tgBtn['network'] ?? '')) === 'telegram' && $tgCandidate !== '' && \App\Core\UrlGuard::isSafeLink($tgCandidate)) {
        $tgUrl = $tgCandidate;
        break;
    }
}
$tgHandle = preg_match('~t\.me/([A-Za-z0-9_]{3,})~', $tgUrl, $tgMatch) ? '@' . $tgMatch[1] : 'Telegram';
?>
<?php if ($tgUrl !== ''): ?>
<section class="pt-subscribe" aria-labelledby="pt-sub-title">
    <div class="pt-wrap">
        <div class="pt-tg">
            <div class="pt-tg__body">
                <p class="pt-tg__top"><span class="pt-tg__kicker"><?= htmlspecialchars(t('Telegram-канал'), ENT_QUOTES) ?></span><span class="pt-tg__pill"><?= htmlspecialchars(t('Ежедневно'), ENT_QUOTES) ?></span></p>
                <h2 id="pt-sub-title"><?= htmlspecialchars($secTitle !== '' ? $secTitle : t('Новости отрасли — туда, где вы их читаете'), ENT_QUOTES) ?></h2>
                <p><?= htmlspecialchars(t('Оперативные сводки в Telegram: котировки хлопка и пряжи, прямые включения с выставок и новости маркетплейсов.'), ENT_QUOTES) ?></p>
            </div>
            <a class="pt-btn pt-btn--brand pt-tg__cta" href="<?= htmlspecialchars($tgUrl, ENT_QUOTES) ?>" target="_blank" rel="noopener"><?= htmlspecialchars(t('Открыть'), ENT_QUOTES) ?> <?= htmlspecialchars($tgHandle, ENT_QUOTES) ?></a>
        </div>
    </div>
</section>
<?php else: ?>
<section class="pt-subscribe" aria-labelledby="pt-sub-title">
    <div class="pt-wrap pt-subscribe__row">
        <div class="pt-subscribe__intro">
            <h2 id="pt-sub-title"><?= htmlspecialchars($secTitle !== '' ? $secTitle : t('Новости отрасли — туда, где вы их читаете'), ENT_QUOTES) ?></h2>
            <p><?= htmlspecialchars(t('Главное за неделю — сводка цен на хлопок и пряжу, экспорт и маркетплейсы — на почту по понедельникам.'), ENT_QUOTES) ?></p>
        </div>
        <form class="pt-sub-card" method="post" action="<?= htmlspecialchars(Locale::url('subscribe'), ENT_QUOTES) ?>">
            <?= \App\Core\Csrf::field() ?>
            <?= \App\Core\Csrf::honeypotField() ?>
            <input type="hidden" name="source" value="home">
            <label class="pt-sub-card__head" for="pt-sub-email"><?= htmlspecialchars(t('Обзор рынка на почту'), ENT_QUOTES) ?></label>
            <div class="pt-sub-card__row">
                <input id="pt-sub-email" type="email" name="email" required autocomplete="email" placeholder="you@company.uz">
                <button class="pt-btn pt-btn--brand" type="submit"><?= htmlspecialchars(t('Подписаться'), ENT_QUOTES) ?></button>
            </div>
            <label class="pt-sub-card__consent"><input type="checkbox" name="consent" value="1" required> <?= htmlspecialchars((string) \App\Models\Setting::get('form_consent_text', t('Я даю согласие на обработку персональных данных')), ENT_QUOTES) ?></label>
        </form>
    </div>
</section>
<?php endif; ?>
