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

/** @var array{section: string, heading: string, limit: int} $data */

// Значения уже приведены схемой (BlockFieldSchema::apply): секция — из
// закрытого списка, число — в своих пределах.
$secKey = $data['section'];
$sec = Portal::section($secKey, \App\Core\Locale::current(), $data['limit']);
$secTitle = trim($data['heading']);
// Id заголовков секции: блоков одной секции на странице бывает несколько.
$secUid = 'b' . (int) ($blockId ?? 0);
// Рекламные зоны внутри секций спрашивают тип страницы — это главная.
$adPlace = 'home';
require dirname(__DIR__, 2) . '/app/Views/portal/_home_' . $secKey . '.php';
