<?php

use App\Core\AdminUi;
use App\Core\Csrf;
use App\Models\NewsCategory;

/** @var string $token */
/** @var bool $missingFile */
/** @var array<string, array{name:string, count:int}> $source */
/** @var list<array{id:int, slug:string, name:string, axis:string, names:list<string>}> $sections */
/** @var array<string, int> $auto */

$pageTitle = 'Рубрики из старого сайта';
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
<?php elseif ($source === []): ?>
<div class="form-card">
    <?= AdminUi::cardHeader('В файле нет рубрик', 'info-circle') ?>
    <p>У опубликованных записей этого экспорта не указано ни одной рубрики — связывать нечего.</p>
</div>
<?php else: ?>
<form method="post" action="/admin/news/import/categories" class="form-card">
    <?= Csrf::field() ?>
    <input type="hidden" name="token" value="<?= $e($token) ?>">
    <?= AdminUi::cardHeader('Рубрика старого сайта → раздел портала', 'category') ?>
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
