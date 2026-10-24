<?php

use App\Core\DateFormatter;
use App\Core\Media;
use App\Core\SectionHead;
use App\Core\TitleMarkup;

/**
 * «Новостная витрина» — разделы главной портала. Раздел выбирается настройкой
 * `layout`, данные собирает BlockRenderer::enrichNewsFront; значения проверены
 * схемой полей, поэтому шаблон читает их как есть.
 *
 * @var array $data
 * @var int $blockId
 */
$layout = (string) $data['layout'];
$news = $data['news'] ?? [];
$panels = $data['panels'] ?? [];
$title = trim((string) ($data['title'] ?? ''));
if ($title === '' && $layout !== 'main') {
    $title = match ($layout) {
        'countries' => t('Страны региона'),
        'popular' => t('Самое читаемое'),
        default => t('Выбор редакции'),
    };
}
$allText = trim((string) ($data['all_text'] ?? ''));
$allUrl = trim((string) ($data['all_url'] ?? ''));
$templateCss = '';

$esc = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES);
// Метка над заголовком называет обе оси раздела: рубрику и страну.
$kicker = static function (array $i) use ($esc): string {
    if ($i['rubric'] === '' && $i['country'] === '') {
        return '';
    }
    $country = $i['country'] !== '' ? '<span class="nf-kicker__country">' . $esc($i['country']) . '</span>' : '';

    return '<span class="nf-kicker">' . $esc($i['rubric']) . $country . '</span>';
};
$cover = static function (array $i, string $sizes, bool $lazy = true) use ($esc): string {
    $img = !empty($i['cover']) ? Media::picture((string) $i['cover'], '', null, null, 'nf-cover__img', $lazy, $sizes) : '';

    return '<span class="nf-cover">' . ($img !== '' ? $img : '<span class="nf-cover__empty" aria-hidden="true"></span>') . '</span>';
};
$date = static fn (array $i): string => $i['published_at'] !== ''
    ? '<time class="nf-date" datetime="' . $esc(DateFormatter::format($i['published_at'], 'c')) . '">' . $esc(DateFormatter::short($i['published_at'])) . '</time>'
    : '';
// В колонке «Последние» свежая новость подписана временем, вчерашняя — датой:
// одно время без даты врало бы о материале прошлой недели.
$today = DateFormatter::format(time(), 'Y-m-d');
$stamp = static function (array $i) use ($today, $esc): string {
    if ($i['published_at'] === '') {
        return '';
    }
    $sameDay = DateFormatter::format($i['published_at'], 'Y-m-d') === $today;

    return '<time class="nf-latest__time" datetime="' . $esc(DateFormatter::format($i['published_at'], 'c')) . '">'
        . $esc(DateFormatter::format($i['published_at'], $sameDay ? 'H:i' : 'd.m')) . '</time>';
};
$head = static function (string $title, string $id) use ($allText, $allUrl): string {
    if ($title === '') {
        return '';
    }
    $link = $allText !== '' && $allUrl !== '' ? SectionHead::allLink($allText, $allUrl) : '';

    return '<div class="section-head nf-head"><h2 class="section-head__title" id="' . $id . '">' . TitleMarkup::html($title) . '</h2>' . $link . '</div>';
};
$uid = 'nf-' . (int) ($blockId ?? 0);
?>
<?php if ($layout === 'main' && $news !== []): ?>
    <?php
    $lead = $news[0];
    $row = array_slice($news, 1, 3);
    $listLimit = (int) $data['limit'];
    $list = array_slice($news, 4, $listLimit);
    $latest = array_slice($news, 4 + $listLimit, (int) $data['latest_limit']);
    ?>
    <div class="nf nf--main<?= $latest === [] ? ' nf--main-wide' : '' ?>">
        <?= $head($title, $uid) ?>
        <div class="nf-main">
            <div class="nf-main__col">
                <article class="nf-lead">
                    <a class="nf-lead__link" href="<?= $esc($lead['url']) ?>">
                        <span class="nf-lead__body">
                            <?= $kicker($lead) ?>
                            <span class="nf-lead__title"><?= $esc($lead['title']) ?></span>
                            <?php if ($lead['excerpt'] !== ''): ?><span class="nf-lead__excerpt"><?= $esc($lead['excerpt']) ?></span><?php endif; ?>
                            <?= $date($lead) ?>
                        </span>
                        <?= $cover($lead, '(max-width: 720px) 100vw, 50vw', false) ?>
                    </a>
                </article>
                <?php if ($row !== []): ?>
                    <div class="nf-row">
                        <?php foreach ($row as $i): ?>
                            <article class="nf-card">
                                <a class="nf-card__link" href="<?= $esc($i['url']) ?>">
                                    <?= $cover($i, '(max-width: 720px) 100vw, 25vw') ?>
                                    <?= $kicker($i) ?>
                                    <span class="nf-card__title"><?= $esc($i['title']) ?></span>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($list !== []): ?>
                    <div class="nf-rows">
                        <?php foreach ($list as $i): ?>
                            <article class="nf-crow">
                                <a class="nf-crow__link" href="<?= $esc($i['url']) ?>">
                                    <?= $cover($i, '120px') ?>
                                    <span class="nf-crow__body">
                                        <?= $kicker($i) ?>
                                        <span class="nf-crow__title"><?= $esc($i['title']) ?></span>
                                    </span>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($latest !== []): ?>
                <aside class="nf-latest" aria-labelledby="<?= $uid ?>-latest">
                    <h2 class="nf-latest__title" id="<?= $uid ?>-latest"><?= $esc(t('Последние новости')) ?></h2>
                    <ol class="nf-latest__list">
                        <?php foreach ($latest as $i): ?>
                            <li class="nf-latest__item"><?= $stamp($i) ?><a class="nf-latest__link" href="<?= $esc($i['url']) ?>"><?= $esc($i['title']) ?></a></li>
                        <?php endforeach; ?>
                    </ol>
                    <?php if ($allText !== '' && $allUrl !== ''): ?>
                        <a class="nf-latest__all btn-fill" href="<?= $esc($allUrl) ?>"><?= $esc($allText) ?></a>
                    <?php endif; ?>
                </aside>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($layout === 'countries' && $panels !== []): ?>
    <div class="nf nf--countries" data-nf-tabs>
        <?= $head($title, $uid) ?>
        <?php // Вкладки — улучшение: без скрипта видны все страны подряд, каждая
              // со своим заголовком, а список вкладок не показывается вовсе. ?>
        <div class="nf-tabs" role="tablist" aria-labelledby="<?= $uid ?>" hidden>
            <?php foreach ($panels as $n => $panel): ?>
                <button type="button" class="nf-tab" role="tab" id="<?= $uid ?>-tab-<?= $n ?>" aria-controls="<?= $uid ?>-panel-<?= $n ?>" aria-selected="<?= $n === 0 ? 'true' : 'false' ?>"<?= $n === 0 ? '' : ' tabindex="-1"' ?>><?= $esc($panel['name']) ?></button>
            <?php endforeach; ?>
        </div>
        <?php foreach ($panels as $n => $panel): ?>
            <?php $cards = array_slice($panel['news'], 0, 2); $rest = array_slice($panel['news'], 2); ?>
            <section class="nf-panel" id="<?= $uid ?>-panel-<?= $n ?>" role="tabpanel" aria-labelledby="<?= $uid ?>-tab-<?= $n ?>">
                <h3 class="nf-panel__title"><a href="<?= $esc($panel['url']) ?>"><?= $esc($panel['name']) ?></a></h3>
                <div class="nf-panel__cards">
                    <?php foreach ($cards as $i): ?>
                        <article class="nf-card nf-card--large">
                            <a class="nf-card__link" href="<?= $esc($i['url']) ?>">
                                <?= $cover($i, '(max-width: 720px) 100vw, 33vw') ?>
                                <span class="nf-card__title"><?= $esc($i['title']) ?></span>
                            </a>
                            <?= $date($i) ?>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if ($rest !== []): ?>
                    <ul class="nf-panel__list">
                        <?php foreach ($rest as $i): ?>
                            <li><a class="nf-panel__link" href="<?= $esc($i['url']) ?>"><?= $esc($i['title']) ?></a><?= $date($i) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a class="nf-panel__more" href="<?= $esc($panel['url']) ?>"><?= $esc(t('Все новости страны')) ?></a>
            </section>
        <?php endforeach; ?>
    </div>
<?php elseif ($layout === 'popular' && $news !== []): ?>
    <div class="nf nf--popular">
        <?= $head($title, $uid) ?>
        <?php // Номер — порядок рейтинга, то есть настоящая последовательность;
              // диктор узнаёт его из самого списка <ol>, цифра ему не нужна. ?>
        <ol class="nf-popular">
            <?php foreach ($news as $n => $i): ?>
                <li class="nf-popular__item"><span class="nf-popular__n" aria-hidden="true"><?= $n + 1 ?></span><a class="nf-popular__link" href="<?= $esc($i['url']) ?>"><?= $esc($i['title']) ?></a></li>
            <?php endforeach; ?>
        </ol>
    </div>
<?php elseif ($layout === 'picks' && $news !== []): ?>
    <?php $big = array_slice($news, 0, 2); $rest = array_slice($news, 2); ?>
    <div class="nf nf--picks">
        <?= $head($title, $uid) ?>
        <div class="nf-picks<?= $rest === [] ? ' nf-picks--cards' : '' ?>">
            <?php foreach ($big as $i): ?>
                <article class="nf-card nf-card--large">
                    <a class="nf-card__link" href="<?= $esc($i['url']) ?>">
                        <?= $cover($i, '(max-width: 720px) 100vw, 33vw') ?>
                        <?= $kicker($i) ?>
                        <span class="nf-card__title"><?= $esc($i['title']) ?></span>
                    </a>
                    <?= $date($i) ?>
                </article>
            <?php endforeach; ?>
            <?php if ($rest !== []): ?>
                <ul class="nf-picks__list">
                    <?php foreach ($rest as $i): ?>
                        <li><?= $kicker($i) ?><a class="nf-picks__link" href="<?= $esc($i['url']) ?>"><?= $esc($i['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <?php // Пустой раздел говорит об этом прямо: блок без единой новости иначе
          // исчезал бы со страницы, и редактор не понял бы, куда он делся. ?>
    <div class="nf nf--<?= $esc($layout) ?>">
        <?= $head($title, $uid) ?>
        <p class="nf-empty"><?= $esc($layout === 'picks' ? t('Редакция пока ничего не отметила.') : t('Новостей пока нет.')) ?></p>
    </div>
<?php endif; ?>
