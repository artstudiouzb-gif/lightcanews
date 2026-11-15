<?php
/**
 * «Секция портала» — часть главной LightCA.News блоком конструктора. Данные
 * секция берёт сама (Portal::section), разметка общая с фиксированной главной
 * (`app/Views/portal/_home_<секция>.php`).
 *
 * @var array $data
 * @var int $blockId
 */

use App\Core\Portal\Portal;

/** @var array{section: string, heading: string, limit: int, facts?: string, cta_title?: string, cta_note?: string, cta_text?: string, cta_url?: string} $data */

// Значения уже приведены схемой (BlockFieldSchema::apply): секция — из
// закрытого списка, число — в своих пределах.
$secKey = $data['section'];
$sec = Portal::section($secKey, \App\Core\Locale::current(), $data['limit']);
$secTitle = trim($data['heading']);
// Id заголовков секции: блоков одной секции на странице бывает несколько.
$secUid = 'b' . (int) ($blockId ?? 0);
// Ключевые цифры «Число | подпись», не больше трёх.
$secFacts = [];
foreach (preg_split('/\R/u', (string) ($data['facts'] ?? '')) ?: [] as $factLine) {
    [$factValue, $factCaption] = array_pad(array_map('trim', explode('|', $factLine, 2)), 2, '');
    if ($factValue !== '' && count($secFacts) < 3) {
        $secFacts[] = ['value' => $factValue, 'caption' => $factCaption];
    }
}
$secCta = [
    'title' => trim((string) ($data['cta_title'] ?? '')),
    'note' => trim((string) ($data['cta_note'] ?? '')),
    'text' => trim((string) ($data['cta_text'] ?? '')),
    'url' => trim((string) ($data['cta_url'] ?? '')),
];
if ($secCta['title'] === '' || ($secCta['url'] !== '' && !\App\Core\UrlGuard::isSafeLink($secCta['url']))) {
    $secCta = null;
}
// Рекламные зоны внутри секций спрашивают тип страницы — это главная.
$adPlace = 'home';
require dirname(__DIR__, 2) . '/app/Views/portal/_home_' . $secKey . '.php';
