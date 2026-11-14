<?php
/**
 * Кадр карточки: картинка с уменьшенными копиями или заглушка того же размера,
 * чтобы карточка без фото не сдвигала соседей по ряду.
 *
 * @var array<string, mixed> $photoCard
 * @var string $photoRatio  класс пропорции: '', '16x9', '4x3'
 * @var string $photoSizes
 * @var bool $photoEager
 */

$photoRatio = $photoRatio ?? '';
$photoClass = 'pt-ph' . ($photoRatio !== '' ? ' pt-ph--' . $photoRatio : '');
$photoHtml = (string) \App\Core\Media::picture(
    (string) ($photoCard['cover'] ?? ''),
    '',
    $photoCard['focal_x'] ?? null,
    $photoCard['focal_y'] ?? null,
    '',
    empty($photoEager),
    $photoSizes ?? '(max-width: 720px) 100vw, 33vw',
    !empty($photoEager)
);
?>
<div class="<?= $photoClass ?>"<?= $photoHtml === '' ? ' data-empty' : '' ?>><?= $photoHtml ?></div>
<?php $photoEager = false; ?>
