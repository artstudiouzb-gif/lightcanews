<?php

use App\Core\AdminUi;
use App\Core\Csrf;
use App\Models\NewsCategory;

/** @var string $token */
/** @var bool $missingFile */
/** @var array<string, array{name:string, count:int}> $source */
/** @var list<array{id:int, slug:string, name:string, axis:string, names:list<string>}> $sections */
/** @var array<string, int> $auto */
/** @var array{found:int, lang_changed:int, linked:int, conflicts:int, missing:int, by_lang:array<string,int>}|null $languagePreview */

$pageTitle = 'После импорта: языки и рубрики';
$activeNav = 'news';
$pageActions = '<a href="/admin/news/import" class="btn">' . AdminUi::icon('arrow-left') . 'К импорту</a>';
require __DIR__ . '/../layout/header.php';

$axisLabels = [NewsCategory::AXIS_RUBRIC => 'Рубрика', NewsCategory::AXIS_COUNTRY => 'Страна'];
$e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES);
?>
<?php if ($missingFile): ?>
<div class="form-card">
    <?= AdminUi::cardHeader('Файл экспорта не найден', 'alert-triangle') ?>
    <p>Связка рубрик читает файл экспорта старого сайта. Загрузите его на странице импорта (тот же файл, что и при переносе новостей) и после проверки нажмите «Связать рубрики».</p>
    <div class="form-actions"><a href="/admin/news/import" class="btn btn--primary">Загрузить файл</a></div>
</div>
<?php else: ?>
<?php if ($languagePreview !== null): ?>
<form method="post" action="/admin/news/import/languages" class="form-card">
    <?= Csrf::field() ?>
    <input type="hidden" name="token" value="<?= $e($token) ?>">
    <?= AdminUi::cardHeader('1. Языки и переводы', 'language') ?>
    <p>Язык каждой записи определён по её тексту: русский и узбекский различаются письменностью. Переводы одной новости собираются в пару, как при импорте. Меняются только язык и связь переводов — текст, адреса и ваши правки остаются.</p>
    <p>
        Найдено на сайте: <strong><?= (int) $languagePreview['found'] ?></strong>
        <?php foreach ($languagePreview['by_lang'] as $code => $count): ?>
            · <?= $e(strtoupper((string) $code)) ?>: <strong><?= (int) $count ?></strong>
        <?php endforeach; ?>
        <br>Сменят язык: <strong><?= (int) $languagePreview['lang_changed'] ?></strong>
        · свяжутся переводом: <strong><?= (int) $languagePreview['linked'] ?></strong>
    </p>
    <div class="form-actions">
        <button type="submit" class="btn btn--primary"<?= $languagePreview['lang_changed'] + $languagePreview['linked'] === 0 ? ' disabled' : '' ?>><?= AdminUi::icon('language') ?>Исправить языки</button>
    </div>
</form>
<?php endif; ?>
<?php endif; ?>
<?php if ($missingFile): ?>
<?php elseif ($source === []): ?>
<div class="form-card">
    <?= AdminUi::cardHeader('В файле нет рубрик', 'info-circle') ?>
    <p>У опубликованных записей этого экспорта не указано ни одной рубрики — связывать нечего.</p>
</div>
<?php else: ?>
<form method="post" action="/admin/news/import/categories" class="form-card">
    <?= Csrf::field() ?>
    <input type="hidden" name="token" value="<?= $e($token) ?>">
    <?= AdminUi::cardHeader('2. Рубрика старого сайта → раздел портала', 'category') ?>
    <p>Выберите раздел для каждой рубрики один раз — все перенесённые новости из неё получат его сами. Совпавшие по названию или адресу уже подставлены. Рубрика новости одна (берётся первая подходящая), стран может быть несколько.</p>
        <table class="data-table">
            <thead><tr><th>Рубрика старого сайта</th><th>Записей</th><th>Раздел портала</th></tr></thead>
            <tbody>
            <?php foreach ($source as $slug => $info): ?>
                <tr>
                    <td><strong><?= $e($info['name']) ?></strong><br><small class="form-hint"><?= $e(rawurldecode((string) $slug)) ?></small></td>
                    <td><?= (int) $info['count'] ?></td>
                    <td>
                        <select name="map[<?= $e((string) $slug) ?>]" aria-label="Раздел для рубрики «<?= $e($info['name']) ?>»">
                            <option value="0">— не переносить —</option>
                            <?php foreach ($axisLabels as $axis => $axisLabel): ?>
                                <optgroup label="<?= $e($axisLabel) ?>">
                                    <?php foreach ($sections as $section): ?>
                                        <?php if ($section['axis'] !== $axis) { continue; } ?>
                                        <option value="<?= (int) $section['id'] ?>"<?= ($auto[(string) $slug] ?? 0) === $section['id'] ? ' selected' : '' ?>><?= $e($section['name']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <div class="form-field form-field--checkbox">
        <label><input type="checkbox" name="overwrite" value="1"> Перезаписать уже выбранные разделы</label>
        <span class="form-hint">Без галочки заполняются только новости без рубрики и без стран — правки, сделанные руками, не затрагиваются.</span>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn--primary"><?= AdminUi::icon('link') ?>Связать рубрики</button>
    </div>
</form>
<?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
