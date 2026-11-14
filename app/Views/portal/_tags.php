<?php
/**
 * Строка меток карточки: коды стран, рубрика, дата.
 *
 * @var array<string, mixed> $tagCard
 * @var bool $tagDate
 */
?>
<span class="pt-tags">
    <?php if (($tagCard['badge'] ?? '') !== ''): ?><span class="pt-badge"><?= htmlspecialchars((string) $tagCard['badge'], ENT_QUOTES) ?></span><?php endif; ?>
    <?php foreach ((array) ($tagCard['countries'] ?? []) as $tagCode): ?><span class="pt-code"><?= htmlspecialchars((string) $tagCode, ENT_QUOTES) ?></span><?php endforeach; ?>
    <?php if (($tagCard['rubric'] ?? '') !== ''): ?><span class="pt-rubric"><?= htmlspecialchars((string) $tagCard['rubric'], ENT_QUOTES) ?></span><?php endif; ?>
    <?php if (!empty($tagDate) && ($tagCard['date'] ?? '') !== ''): ?><time class="pt-meta" datetime="<?= htmlspecialchars((string) $tagCard['datetime'], ENT_QUOTES) ?>"><?= htmlspecialchars((string) $tagCard['date'], ENT_QUOTES) ?></time><?php endif; ?>
</span>
<?php $tagDate = false; ?>
