<?php
/** @var array $data */
$title = trim((string) ($data['title'] ?? ''));
$allText = trim((string) ($data['all_text'] ?? ''));
$allUrl = trim((string) ($data['all_url'] ?? ''));
$items = $data['items'] ?? [];
// Значения проверены схемой полей (BlockFieldSchema) — читаем как есть.
$cols = (int) $data['columns'];
$variant = (string) $data['variant'];
$searchEnabled = !empty($data['search_enabled']);
$prepared = array_map([\App\Core\DocumentPresenter::class, 'prepare'], is_array($items) ? $items : []);
$kinds = array_values(array_unique(array_filter(array_column($prepared, 'extension'), static fn (string $kind): bool => $kind !== 'other')));
// Хвост последнего ряда растягивается на всю ширину: пять документов в три
// колонки иначе оставляют справа пустую ячейку.
$templateCss = \App\Core\GridBalance::css($blockId, '.docslist-grid', '.doc-card', count($prepared), $cols);
?>
<div class="block-docslist block-docslist--<?= $variant ?>" data-document-list>
    <div class="section-head">
        <?php if ($title !== ''): ?><h2 class="section-head__title"><?= \App\Core\TitleMarkup::html($title) ?></h2><?php endif; ?>
        <?php if ($allText !== '' && $allUrl !== ''): ?><?= \App\Core\SectionHead::allLink($allText, $allUrl) ?><?php endif; ?>
    </div>
    <?php if (empty($items)): ?>
        <p class="block-docslist__empty"><?= htmlspecialchars(t('Документы ещё не добавлены.'), ENT_QUOTES) ?></p>
    <?php else: ?>
        <?php if ($searchEnabled && count($items) >= 4): ?>
            <div class="document-tools" role="search" aria-label="<?= htmlspecialchars(t('Поиск документов'), ENT_QUOTES) ?>">
                <label class="document-tools__search">
                    <span class="visually-hidden"><?= htmlspecialchars(t('Найти документ'), ENT_QUOTES) ?></span>
                    <?= \App\Core\Icon::render('search', 18, 'document-tools__search-icon', 1.8) ?>
                    <input type="search" data-document-query placeholder="<?= htmlspecialchars(t('Найти документ…'), ENT_QUOTES) ?>" autocomplete="off">
                </label>
                <?php if (count($kinds) > 1): ?>
                    <label class="document-tools__filter">
                        <span class="visually-hidden"><?= htmlspecialchars(t('Тип документа'), ENT_QUOTES) ?></span>
                        <select data-document-kind-filter>
                            <option value=""><?= htmlspecialchars(t('Все форматы'), ENT_QUOTES) ?></option>
                            <?php foreach ($kinds as $kind): ?>
                                <option value="<?= htmlspecialchars($kind, ENT_QUOTES) ?>"><?= htmlspecialchars(strtoupper($kind), ENT_QUOTES) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <span class="document-tools__count" data-document-count aria-live="polite"></span>
            </div>
        <?php endif; ?>
        <div class="<?= $variant === 'links' ? 'media-list docslist-links' : 'docslist-grid' ?>">
            <?php foreach ($items as $doc): ?>
                <?php $compact = $variant === 'links'; include __DIR__ . '/partials/document_card.php'; ?>
            <?php endforeach; ?>
        </div>
        <p class="block-docslist__empty" data-document-empty hidden><?= htmlspecialchars(t('Ничего не найдено.'), ENT_QUOTES) ?></p>
    <?php endif; ?>
</div>
