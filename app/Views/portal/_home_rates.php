<?php
/**
 * Секция «Курсы валют».
 * Общая для фиксированной главной и блока «Секция портала».
 *
 * @var array<string, mixed> $sec      Portal::section()
 * @var string               $secTitle свой заголовок секции ('' — по умолчанию)
 */

use App\Core\Locale;

$rates = $sec['rates'] ?? null;
?>
<?php if ($rates !== null): ?>
<section class="pt-fx" aria-label="<?= htmlspecialchars(t('Курсы валют к доллару США'), ENT_QUOTES) ?>" data-no-translit>
    <div class="pt-wrap pt-fx__row">
        <p class="pt-fx__label"><?= htmlspecialchars(t('Курс ЦБ Узбекистана на') . ' ' . $rates['date'], ENT_QUOTES) ?></p>
        <dl class="pt-fx__list">
            <?php foreach ($rates['rates'] as $fxCode => $fxRate): ?>
                <div class="pt-fx__item"><dt>USD/<?= htmlspecialchars((string) $fxCode, ENT_QUOTES) ?></dt><dd><?= number_format((float) $fxRate, 2, ',', "\u{00A0}") ?></dd></div>
            <?php endforeach; ?>
        </dl>
    </div>
</section>
<?php endif; ?>
