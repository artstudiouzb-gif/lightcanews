<?php
/**
 * Главная портала: собирается из новостей, а не из блоков конструктора —
 * у делового портала первый экран это лента, и её порядок задаёт редакция
 * датой публикации и отметкой «Выбор редакции».
 *
 * @var array<string, mixed> $home   Portal::home()
 * @var string $metaTitle
 * @var string $metaDescription
 */

use App\Core\Locale;

$lead = $home['lead'] ?? null;
$grid = (array) ($home['grid'] ?? []);
$also = (array) ($home['also'] ?? []);
$feed = (array) ($home['feed'] ?? []);
$picks = (array) ($home['picks'] ?? []);
$regions = (array) ($home['regions'] ?? []);
$voices = (array) ($home['voices'] ?? []);
$rates = \App\Core\CurrencyRates::current();
$newsUrl = Locale::url('news');

require __DIR__ . '/_top.php';
?>
<h1 class="pt-visually-hidden"><?= htmlspecialchars($metaTitle !== '' ? $metaTitle : t('Главное в отрасли'), ENT_QUOTES) ?></h1>

<div class="pt-wrap pt-home-top">
    <section class="pt-lead-ed" aria-labelledby="pt-lead-title">
        <header class="pt-sec-head pt-sec-head--sm">
            <h2 id="pt-lead-title"><?= htmlspecialchars(t('Главное в отрасли'), ENT_QUOTES) ?></h2>
            <a class="pt-more" href="<?= htmlspecialchars($newsUrl, ENT_QUOTES) ?>"><?= htmlspecialchars(t('Все новости'), ENT_QUOTES) ?></a>
        </header>

        <?php if ($lead === null): ?>
            <p class="pt-empty"><?= htmlspecialchars(t('Пока нет опубликованных новостей.'), ENT_QUOTES) ?></p>
        <?php else: ?>
            <a class="pt-pkg" href="<?= htmlspecialchars((string) $lead['url'], ENT_QUOTES) ?>">
                <div class="pt-pkg__text">
                    <?php $tagCard = $lead; $tagDate = true; require __DIR__ . '/_tags.php'; ?>
                    <h3 class="pt-pkg__title pt-hl"><?= htmlspecialchars((string) $lead['title'], ENT_QUOTES) ?></h3>
                    <?php if ($lead['excerpt'] !== ''): ?><p class="pt-pkg__lead"><?= htmlspecialchars((string) $lead['excerpt'], ENT_QUOTES) ?></p><?php endif; ?>
                </div>
                <?php $photoCard = $lead; $photoRatio = '4x3'; $photoSizes = '(max-width: 720px) 100vw, 480px'; $photoEager = true; require __DIR__ . '/_photo.php'; ?>
            </a>
        <?php endif; ?>

        <?php if ($grid !== []): ?>
        <div class="pt-grid3">
            <?php foreach ($grid as $card): ?>
                <a class="pt-ncard" href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES) ?>">
                    <?php $photoCard = $card; $photoRatio = '4x3'; $photoSizes = '(max-width: 720px) 100vw, 300px'; require __DIR__ . '/_photo.php'; ?>
                    <?php $tagCard = $card; require __DIR__ . '/_tags.php'; ?>
                    <span class="pt-hl"><?= htmlspecialchars((string) $card['title'], ENT_QUOTES) ?></span>
                    <time class="pt-meta" datetime="<?= htmlspecialchars((string) $card['datetime'], ENT_QUOTES) ?>"><?= htmlspecialchars((string) $card['date'], ENT_QUOTES) ?></time>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($also !== []): ?>
        <section class="pt-also" aria-labelledby="pt-also-title">
            <header class="pt-sec-head pt-sec-head--sm">
                <h2 id="pt-also-title"><?= htmlspecialchars(t('Также в центре внимания'), ENT_QUOTES) ?></h2>
            </header>
            <div class="pt-also__grid">
                <?php foreach ($also as $card): ?>
                    <a class="pt-also__item" href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES) ?>">
                        <?php $tagCard = $card; $tagDate = true; require __DIR__ . '/_tags.php'; ?>
                        <span class="pt-hl"><?= htmlspecialchars((string) $card['title'], ENT_QUOTES) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </section>

    <aside class="pt-feed" aria-labelledby="pt-feed-title">
        <header class="pt-sec-head pt-sec-head--sm">
            <h2 id="pt-feed-title"><?= htmlspecialchars(t('Лента новостей'), ENT_QUOTES) ?></h2>
        </header>
        <ol class="pt-feed__list">
            <?php foreach ($feed as $card): ?>
                <li>
                    <a href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES) ?>">
                        <time datetime="<?= htmlspecialchars((string) $card['datetime'], ENT_QUOTES) ?>"><?= htmlspecialchars($card['is_today'] ? (string) $card['time'] : (string) $card['day'], ENT_QUOTES) ?></time>
                        <span>
                            <?php if ($card['rubric'] !== ''): ?><span class="pt-feed__rubric"><?= htmlspecialchars((string) $card['rubric'], ENT_QUOTES) ?></span><?php endif; ?>
                            <span class="pt-hl"><?= htmlspecialchars((string) $card['title'], ENT_QUOTES) ?></span>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ol>
        <a class="pt-btn pt-btn--wide" href="<?= htmlspecialchars($newsUrl, ENT_QUOTES) ?>"><?= htmlspecialchars(t('Вся лента'), ENT_QUOTES) ?></a>
    </aside>
</div>

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

<?php if ($picks !== []): ?>
<section class="pt-picks" aria-labelledby="pt-picks-title">
    <div class="pt-wrap">
        <header class="pt-sec-head"><h2 id="pt-picks-title"><?= htmlspecialchars(t('Выбор редакции'), ENT_QUOTES) ?></h2></header>
        <div class="pt-picks__row">
            <?php foreach ($picks as $card): ?>
                <a class="pt-pcard" href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES) ?>">
                    <?php $photoCard = $card; $photoSizes = '(max-width: 720px) 100vw, 25vw'; require __DIR__ . '/_photo.php'; ?>
                    <span class="pt-pcard__body">
                        <span class="pt-pcard__meta"><span><?= htmlspecialchars((string) $card['rubric'], ENT_QUOTES) ?></span><span><?= htmlspecialchars(sprintf(t('%d мин чтения'), (int) $card['reading']), ENT_QUOTES) ?></span></span>
                        <span class="pt-hl"><?= htmlspecialchars((string) $card['title'], ENT_QUOTES) ?></span>
                        <?php if ($card['excerpt'] !== ''): ?><span class="pt-pcard__text"><?= htmlspecialchars((string) $card['excerpt'], ENT_QUOTES) ?></span><?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($regions !== []): ?>
<section class="pt-wrap pt-sec" aria-labelledby="pt-reg-title" data-pt-regions>
    <header class="pt-sec-head"><h2 id="pt-reg-title"><?= htmlspecialchars(t('Нити региона'), ENT_QUOTES) ?></h2><span class="pt-meta"><?= htmlspecialchars(t('По странам'), ENT_QUOTES) ?></span></header>
    <div class="pt-filters" role="group" aria-label="<?= htmlspecialchars(t('Страны'), ENT_QUOTES) ?>" hidden>
        <button type="button" aria-pressed="true" data-pt-region="all"><?= htmlspecialchars(t('Все страны'), ENT_QUOTES) ?></button>
        <?php foreach ($regions as $i => $region): ?>
            <button type="button" aria-pressed="false" data-pt-region="<?= (int) $i ?>"><b><?= htmlspecialchars((string) $region['code'], ENT_QUOTES) ?></b><?= htmlspecialchars((string) $region['name'], ENT_QUOTES) ?></button>
        <?php endforeach; ?>
    </div>
    <div class="pt-regions">
        <?php foreach ($regions as $i => $region): ?>
            <?php $regionLead = $region['items'][0]; ?>
            <article class="pt-region" data-pt-region-card="<?= (int) $i ?>">
                <a class="pt-region__hero" href="<?= htmlspecialchars((string) $regionLead['url'], ENT_QUOTES) ?>" tabindex="-1" aria-hidden="true">
                    <?php $photoCard = $regionLead; $photoRatio = '16x9'; $photoSizes = '(max-width: 720px) 100vw, 33vw'; require __DIR__ . '/_photo.php'; ?>
                    <span class="pt-region__id"><b><?= htmlspecialchars((string) $region['code'], ENT_QUOTES) ?></b><?= htmlspecialchars((string) $region['name'], ENT_QUOTES) ?></span>
                </a>
                <div class="pt-region__body">
                    <h3 class="pt-region__lead"><a href="<?= htmlspecialchars((string) $regionLead['url'], ENT_QUOTES) ?>"><span class="pt-hl"><?= htmlspecialchars((string) $regionLead['title'], ENT_QUOTES) ?></span></a></h3>
                    <span class="pt-meta"><?= htmlspecialchars((string) $regionLead['date'], ENT_QUOTES) ?></span>
                    <?php if (count($region['items']) > 1): ?>
                    <ul>
                        <?php foreach (array_slice($region['items'], 1) as $card): ?>
                            <li><a href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES) ?>"><span><?= htmlspecialchars((string) $card['title'], ENT_QUOTES) ?></span><span class="pt-meta"><?= htmlspecialchars((string) $card['date'], ENT_QUOTES) ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <a class="pt-region__foot" href="<?= htmlspecialchars((string) $region['url'], ENT_QUOTES) ?>"><?= htmlspecialchars(t('Открыть ленту') . ': ' . $region['name'], ENT_QUOTES) ?></a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($voices['items'])): ?>
<section class="pt-wrap pt-sec" aria-labelledby="pt-voices-title">
    <header class="pt-sec-head"><h2 id="pt-voices-title"><?= htmlspecialchars((string) $voices['title'], ENT_QUOTES) ?></h2><a class="pt-more" href="<?= htmlspecialchars((string) $voices['url'], ENT_QUOTES) ?>"><?= htmlspecialchars(t('Все колонки'), ENT_QUOTES) ?></a></header>
    <div class="pt-row3">
        <?php foreach ($voices['items'] as $card): ?>
            <a class="pt-opinion" href="<?= htmlspecialchars((string) $card['url'], ENT_QUOTES) ?>">
                <span class="pt-opinion__badge"><?= htmlspecialchars(t('Колонка'), ENT_QUOTES) ?></span>
                <span class="pt-hl">«<?= htmlspecialchars((string) $card['title'], ENT_QUOTES) ?>»</span>
                <span class="pt-opinion__foot"><span class="pt-meta"><?= htmlspecialchars(sprintf(t('%d мин чтения'), (int) $card['reading']), ENT_QUOTES) ?></span><span class="pt-rubric"><?= htmlspecialchars(t('Читать'), ENT_QUOTES) ?></span></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="pt-subscribe" aria-labelledby="pt-sub-title">
    <div class="pt-wrap pt-subscribe__row">
        <div class="pt-subscribe__intro">
            <h2 id="pt-sub-title"><?= htmlspecialchars(t('Новости отрасли — туда, где вы их читаете'), ENT_QUOTES) ?></h2>
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

<?php require __DIR__ . '/_bottom.php'; ?>
