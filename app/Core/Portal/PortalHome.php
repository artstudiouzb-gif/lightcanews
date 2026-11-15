<?php

declare(strict_types=1);

namespace App\Core\Portal;

use App\Core\Cache;
use App\Core\Database;
use App\Models\Block;

/**
 * Главная портала в конструкторе.
 *
 * Главная собиралась из новостей фиксированным шаблоном. Теперь каждая её
 * секция — блок «Секция портала», и главная переходит на конструктор, как
 * только на странице-главной есть хоть одна такая секция. Признак — сами
 * блоки, а не отдельная настройка: второй переключатель разошёлся бы с тем,
 * что редактор видит в конструкторе. Старые блоки демо-страницы без секций
 * главную не трогают — иначе живой сайт сменил бы вид сам собой.
 */
final class PortalHome
{
    public const TYPE = 'portal_section';

    /** @param array<mixed> $blocks блоки страницы верхнего уровня */
    public static function usesBlocks(array $blocks): bool
    {
        foreach ($blocks as $block) {
            if (is_array($block) && ($block['type'] ?? '') === self::TYPE) {
                return true;
            }
        }

        return false;
    }

    /**
     * Добавляет секции главной в её порядке. Прежние блоки страницы снимаются
     * только по явной просьбе: это блоки, собранные редактором.
     *
     * @return int сколько секций добавлено
     */
    public static function seed(int $pageId, string $lang, bool $replace = false): int
    {
        if ($replace) {
            foreach (Block::forPage($pageId, $lang, true) as $block) {
                Block::delete((int) $block['id']);
            }
        }
        $added = 0;
        foreach (array_keys(Portal::SECTIONS) as $section) {
            Block::create($pageId, $lang, self::TYPE, Portal::SECTIONS[$section], [
                'section' => $section,
                'heading' => '',
                'limit' => 0,
                '_spacing' => 'none',
            ], '');
            $added++;
        }
        Cache::clearPageCache($pageId);
        Cache::forgetPrefix('page:');

        return $added;
    }

    /** Сколько секций портала уже стоит на странице (на языке блоков). */
    public static function count(int $pageId, string $lang): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM blocks WHERE page_id = :p AND lang = :l AND type = :t'
        );
        $stmt->execute([':p' => $pageId, ':l' => $lang, ':t' => self::TYPE]);

        return (int) $stmt->fetchColumn();
    }
}
