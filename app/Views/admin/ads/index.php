<?php

use App\Core\AdminUi;
use App\Core\Csrf;
use App\Core\Portal\PortalAds;

$pageTitle = 'Реклама';
$activeNav = 'ads';
require __DIR__ . '/../layout/header.php';

/** @var array<string, array<string, mixed>> $zones */
$esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);

/**
 * Карточка креатива: одна функция на сохранённые строки и на шаблон новой —
 * две копии разметки разъехались бы при первой правке.
 *
 * @param array<string, mixed> $meta
 * @param array<string, mixed> $item
 */
$creative = static function (string $key, array $meta, string $i, array $item) use ($esc): string {
    $n = 'zones[' . $key . '][items][' . $i . ']';
    $id = 'ad-' . $key . '-' . $i;
    $v = static fn (string $f): string => $esc((string) ($item[$f] ?? ''));
    $dt = static fn (string $f): string => $esc(str_replace(' ', 'T', (string) ($item[$f] ?? '')));
    $html = '<fieldset class="bf-group"><legend>Креатив</legend>';
    $html .= '<div class="form-field"><label for="' . $id . '-from">Показывать с</label><input id="' . $id . '-from" type="datetime-local" name="' . $n . '[from]" value="' . $dt('from') . '"></div>';
    $html .= '<div class="form-field"><label for="' . $id . '-to">по</label><input id="' . $id . '-to" type="datetime-local" name="' . $n . '[to]" value="' . $dt('to') . '"><span class="form-hint">Пусто — без ограничения. Время ташкентское; закончившийся креатив на сайт не выводится.</span></div>';
    $html .= '<div class="form-field"><label for="' . $id . '-sponsor">Рекламодатель</label><input id="' . $id . '-sponsor" type="text" name="' . $n . '[sponsor]" maxlength="60" value="' . $v('sponsor') . '" placeholder="CAITME 2026"><span class="form-hint">' . ($key === 'feed' ? 'Например, домен сайта рекламодателя.' : 'Короткая плашка с названием бренда или события.') . '</span></div>';
    $html .= '<div class="form-field"><label for="' . $id . '-title">Заголовок</label><input id="' . $id . '-title" type="text" name="' . $n . '[title]" maxlength="160" value="' . $v('title') . '"></div>';
    if ($key !== 'feed') {
        $html .= '<div class="form-field"><label for="' . $id . '-text">Текст</label><textarea id="' . $id . '-text" name="' . $n . '[text]" rows="2" maxlength="240">' . $v('text') . '</textarea></div>';
        $html .= '<div class="form-field"><label for="' . $id . '-cta">Надпись на кнопке</label><input id="' . $id . '-cta" type="text" name="' . $n . '[cta]" maxlength="40" value="' . $v('cta') . '" placeholder="Подробнее"></div>';
    }
    $html .= '<div class="form-field"><label for="' . $id . '-url">Ссылка</label><input id="' . $id . '-url" type="text" name="' . $n . '[url]" maxlength="500" value="' . $v('url') . '" placeholder="https://"></div>';
    if ($meta['image']) {
        $html .= AdminUi::imageField($n . '[image]', (string) ($item['image'] ?? ''), ['label' => 'Изображение', 'id' => $id . '-image', 'hint' => $key === 'feed' ? 'Без картинки объявление в ленте не выводится.' : '']);
    }
    $html .= '<button type="button" class="btn btn--small btn--danger" data-repeater-remove>Удалить креатив</button></fieldset>';

    return $html;
};
?>
<div class="admin-builder-workspace">
    <p class="form-hint">Пять зон на страницах портала. В зоне может быть несколько креативов со своим периодом показа: когда в периоде их несколько, они сменяются при каждой загрузке страницы или по очереди. Креатив выводится, когда зона включена и у него есть заголовок и ссылка; выключенная зона на сайте не оставляет пустого места. Ссылка рекламы помечается для поисковиков как спонсорская.</p>
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
                    <label for="ad-<?= $key ?>-rotate">Если креативов в периоде несколько</label>
                    <select id="ad-<?= $key ?>-rotate" name="<?= $n ?>[rotate]">
                        <?php foreach (PortalAds::ROTATIONS as $rot => $rotLabel): ?>
                            <option value="<?= $esc($rot) ?>"<?= $zone['rotate'] === $rot ? ' selected' : '' ?>><?= $esc($rotLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="ad-<?= $key ?>-interval">Смена через, секунд</label>
                    <input id="ad-<?= $key ?>-interval" type="number" name="<?= $n ?>[interval]" min="<?= PortalAds::INTERVAL_MIN ?>" max="<?= PortalAds::INTERVAL_MAX ?>" value="<?= (int) $zone['interval'] ?>">
                    <span class="form-hint">Только для смены по очереди. Пока курсор над рекламой, она не сменяется.</span>
                </div>
                <div data-repeater="ad-<?= $key ?>" data-repeater-max="<?= PortalAds::MAX_ITEMS ?>" data-repeater-next-index="<?= count($zone['items']) ?>">
                    <?php foreach ($zone['items'] as $i => $item): ?>
                        <div class="repeater-row"><?= $creative($key, $meta, (string) $i, $item) ?></div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="ad-<?= $key ?>"><?= $creative($key, $meta, '__INDEX__', []) ?></template>
                <div class="repeater-actions">
                    <button type="button" class="btn btn--small btn--secondary" data-repeater-add="ad-<?= $key ?>"><?= AdminUi::icon('plus') ?> Добавить креатив</button>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="form-actions form-actions--sticky">
            <button type="submit" class="btn btn--primary"><?= AdminUi::icon('save') ?>Сохранить</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
