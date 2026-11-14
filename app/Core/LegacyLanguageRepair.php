<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\News;

/**
 * Языки новостей, перенесённых со старой CMS до того, как импорт научился
 * определять язык записи WPML по тексту.
 *
 * Тогда почти всё приехало узбекским, а переводы не сложились в пары: каждая
 * запись стала отдельной новостью. Исправление читает тот же файл экспорта,
 * определяет язык каждой записи и собирает пары переводов тем же планом, что у
 * импорта, а дальше правит только `lang` и `translation_group_id` найденных
 * новостей: текст, адреса и правки редактора не трогаются. Новость узнаётся по
 * адресу — импорт давал ей slug записи.
 *
 * Пара связывается связанной записью (механизм Б): у каждой версии свой адрес,
 * и переписывать её в поля перевода значило бы ломать уже работающие ссылки.
 */
final class LegacyLanguageRepair
{
    /**
     * @param array{groups:array<int,array<int,array<string,mixed>>>,unresolved:array<int,array<string,mixed>>} $plan
     * @param array<string,string> $langs язык старого сайта → язык портала, первый основной
     * @return array{found:int, lang_changed:int, linked:int, conflicts:int, missing:int, by_lang:array<string,int>}
     */
    public static function run(array $plan, array $langs, bool $dryRun = false): array
    {
        $out = ['found' => 0, 'lang_changed' => 0, 'linked' => 0, 'conflicts' => 0, 'missing' => 0, 'by_lang' => []];
        $primaryWp = $langs !== [] ? (string) array_key_first($langs) : 'uz';
        $groups = LegacyWxrImporter::importGroups($plan, $langs);

        $slugs = [];
        foreach ($groups as $group) {
            foreach ($group as $post) {
                $slugs[] = self::postSlug($post);
            }
        }
        $news = self::newsBySlug(array_values(array_unique(array_filter($slugs))));
        $translated = self::translatedLangs(array_column($news, 'id'));

        $pdo = Database::pdo();
        $update = $pdo->prepare('UPDATE news SET lang = :lang, translation_group_id = :g WHERE id = :id');
        $seen = [];
        foreach ($groups as $group) {
            $members = [];
            foreach ($group as $post) {
                $lang = $langs[(string) ($post['lang'] ?? '')] ?? '';
                $row = $news[self::postSlug($post)] ?? null;
                if ($lang === '' || $row === null || isset($seen[$row['id']])) {
                    if ($row === null) {
                        $out['missing']++;
                    }
                    continue;
                }
                $seen[$row['id']] = true;
                $members[] = ['row' => $row, 'lang' => $lang, 'primary' => (string) $post['lang'] === $primaryWp];
            }
            $base = null;
            $baseLang = '';
            foreach ($members as $m) {
                if ($base === null && $m['primary']) {
                    $base = $m['row'];
                    $baseLang = $m['lang'];
                }
            }
            if ($base === null && $members !== []) {
                $base = $members[0]['row'];
                $baseLang = $members[0]['lang'];
            }

            $usedLangs = [$baseLang => true];
            foreach ($members as $m) {
                $row = $m['row'];
                $out['found']++;
                $out['by_lang'][$m['lang']] = ($out['by_lang'][$m['lang']] ?? 0) + 1;
                $group = $row['group'];
                $isBase = $base !== null && $row['id'] === $base['id'];
                // Вторая версия получает группу основной, если у той нет
                // перевода полями на тот же язык (иначе язык вышел бы дважды)
                // и если версия ещё не принадлежит чужой группе.
                // Одиночная новость — группа самой себя (create() ставит
                // translation_group_id = id), поэтому «своя» группа считается
                // свободной, а ключ группы основной берётся так же, как в
                // News::groupKey().
                $baseKey = $base !== null ? ($base['group'] > 0 ? $base['group'] : $base['id']) : 0;
                $standalone = $group === 0 || $group === $row['id'];
                if (!$isBase && $base !== null && !isset($usedLangs[$m['lang']])
                    && $m['lang'] !== $baseLang
                    && !in_array($m['lang'], $translated[$base['id']] ?? [], true)
                    && ($standalone || $group === $baseKey)) {
                    $group = $baseKey;
                }
                if (!$isBase) {
                    $usedLangs[$m['lang']] = true;
                }
                if ($group === $row['group'] && $m['lang'] === $row['lang']) {
                    continue;
                }
                if (!$dryRun) {
                    try {
                        $update->execute([':lang' => $m['lang'], ':g' => $group > 0 ? $group : null, ':id' => $row['id']]);
                    } catch (\PDOException) {
                        // Адрес уже занят на этом языке другой новостью.
                        $out['conflicts']++;
                        continue;
                    }
                }
                if ($m['lang'] !== $row['lang']) {
                    $out['lang_changed']++;
                }
                if ($group !== $row['group'] && $group === $baseKey) {
                    $out['linked']++;
                }
            }
        }

        if (!$dryRun && ($out['lang_changed'] > 0 || $out['linked'] > 0)) {
            Cache::forgetPrefix('page:');
        }

        return $out;
    }

    /** @param array<string,mixed> $post */
    private static function postSlug(array $post): string
    {
        $slug = (string) ($post['slug'] ?? '');

        return $slug !== '' ? $slug : Slug::make((string) ($post['title'] ?? ''));
    }

    /**
     * @param list<string> $slugs
     * @return array<string, array{id:int, lang:string, group:int}>
     */
    private static function newsBySlug(array $slugs): array
    {
        $out = [];
        foreach (array_chunk($slugs, 500) as $chunk) {
            $in = implode(',', array_fill(0, count($chunk), '?'));
            $stmt = Database::pdo()->prepare(
                "SELECT id, slug, lang, translation_group_id FROM news
                 WHERE deleted_at IS NULL AND slug IN ({$in}) ORDER BY id ASC"
            );
            $stmt->execute($chunk);
            foreach (Database::rows($stmt) as $row) {
                $out[(string) $row['slug']] ??= [
                    'id' => (int) $row['id'],
                    'lang' => (string) $row['lang'],
                    'group' => (int) ($row['translation_group_id'] ?? 0),
                ];
            }
        }

        return $out;
    }

    /**
     * Языки переводов полями (механизм А) у новостей.
     *
     * @param list<int> $ids
     * @return array<int, list<string>>
     */
    private static function translatedLangs(array $ids): array
    {
        $out = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            $in = implode(',', array_fill(0, count($chunk), '?'));
            $stmt = Database::pdo()->prepare("SELECT news_id, lang FROM news_translations WHERE news_id IN ({$in})");
            $stmt->execute($chunk);
            foreach (Database::rows($stmt) as $row) {
                $out[(int) $row['news_id']][] = (string) $row['lang'];
            }
        }

        return $out;
    }
}
