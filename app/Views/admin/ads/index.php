<?php

use App\Core\AdminUi;
use App\Core\Csrf;
use App\Core\Portal\PortalAds;

$pageTitle = 'Реклама';
$activeNav = 'ads';
require __DIR__ . '/../layout/header.php';

/** @var array<string, array<string, mixed>> $zones */
$esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);
?>
<div class="admin-builder-workspace">
    <p class="form-hint">Пять зон на страницах портала. Зона выводится на отмеченных страницах, когда она включена и у неё есть заголовок и ссылка; выключенная зона на сайте не оставляет пустого места. Ссылка рекламы помечается для поисковиков как спонсорская.</p>
    <form method="post" action="/admin/ads" class="form-grid">
        <?= Csrf::field() ?>
        <?php foreach (PortalAds::ZONES as $key => $meta): ?>
            <?php $zone = $zones[$key]; $n = 'zones[' . $key . ']'; ?>
            <div class="header-builder__group">
                <h3><?= $esc($meta['label']) ?> <span class="form-hint">· <?= $esc($meta['size']) ?> · подпись «<?= $esc($meta['mark']) ?>»</span></h3>
                <div class="form-field form-field--checkbox">
                    <input type="checkbox" id="ad-<?= $key ?>-on" name="<?= $n ?>[enabled]" value="1"<?= $zone['enabled'] ? ' checked' : '' ?>>
                    <label for="ad-<?= $key ?>-on">Показывать</label>
                </div>
                <fieldset class="bf-group">
                    <legend>Где показывать</legend>
                    <input type="hidden" name="<?= $n ?>[pages][]" value="">
                    <?php foreach ($meta['places'] as $place => $spot): ?>
                        <div class="form-field form-field--checkbox">
                            <input type="checkbox" id="ad-<?= $key ?>-<?= $place ?>" name="<?= $n ?>[pages][]" value="<?= $esc($place) ?>"<?= in_array($place, $zone['pages'], true) ? ' checked' : '' ?>>
                            <label for="ad-<?= $key ?>-<?= $place ?>"><?= $esc(PortalAds::PLACES[$place]) ?> <span class="form-hint">— <?= $esc($spot) ?></span></label>
                        </div>
                    <?php endforeach; ?>
                </fieldset>
                <div class="form-field">
                    <label for="ad-<?= $key ?>-sponsor">Рекламодатель</label>
                    <input id="ad-<?= $key ?>-sponsor" type="text" name="<?= $n ?>[sponsor]" maxlength="60" value="<?= $esc((string) $zone['sponsor']) ?>" placeholder="CAITME 2026">
                    <span class="form-hint"><?= $key === 'feed' ? 'Например, домен сайта рекламодателя.' : 'Короткая плашка с названием бренда или события.' ?></span>
                </div>
                <div class="form-field">
                    <label for="ad-<?= $key ?>-title">Заголовок</label>
                    <input id="ad-<?= $key ?>-title" type="text" name="<?= $n ?>[title]" maxlength="160" value="<?= $esc((string) $zone['title']) ?>">
                </div>
                <?php if ($key !== 'feed'): ?>
                <div class="form-field">
                    <label for="ad-<?= $key ?>-text">Текст</label>
                    <textarea id="ad-<?= $key ?>-text" name="<?= $n ?>[text]" rows="2" maxlength="240"><?= $esc((string) $zone['text']) ?></textarea>
                </div>
                <div class="form-field">
                    <label for="ad-<?= $key ?>-cta">Надпись на кнопке</label>
                    <input id="ad-<?= $key ?>-cta" type="text" name="<?= $n ?>[cta]" maxlength="40" value="<?= $esc((string) $zone['cta']) ?>" placeholder="Подробнее">
                </div>
                <?php endif; ?>
                <div class="form-field">
                    <label for="ad-<?= $key ?>-url">Ссылка</label>
                    <input id="ad-<?= $key ?>-url" type="text" name="<?= $n ?>[url]" maxlength="500" value="<?= $esc((string) $zone['url']) ?>" placeholder="https://">
                </div>
                <?php if ($meta['image']): ?>
                    <?= AdminUi::imageField($n . '[image]', (string) $zone['image'], ['label' => 'Изображение', 'id' => 'ad-' . $key . '-image', 'hint' => $key === 'feed' ? 'Без картинки объявление в ленте не выводится.' : '']) ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="form-actions form-actions--sticky">
            <button type="submit" class="btn btn--primary"><?= AdminUi::icon('save') ?>Сохранить</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
