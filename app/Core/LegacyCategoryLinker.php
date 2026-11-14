<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\News;
use App\Models\NewsCategory;

/**
 * Рубрики старой CMS → разделы портала.
 *
 * Импорт поначалу рубрик не переносил вовсе, и сотни новостей приехали без
 * раздела. Связка читает тот же файл экспорта ещё раз: у каждой записи там
 * названы её рубрики, а адрес новости, в которую запись превратилась,
 * выводится тем же правилом, что у импорта (`LegacyWxrImporter::groupSlug`) —
 * поэтому новость находится по адресу, без отдельной таблицы соответствий.
 *
 * Соответствие «рубрика старого сайта → раздел» выбирает редактор, форма
 * предлагает очевидное: совпал slug или название (на любом языке раздела).
 * Раздел оси «рубрика» у новости один — берётся первый подходящий, разделы оси
 * «страна» складываются. Уже заполненное по умолчанию не трогается: правку,
 * сделанную руками после импорта, повторный проход затирать не должен.
 */
final class LegacyCategoryLinker
{
    /**
     * Рубрики файла с числом опубликованных записей, самые частые сверху.
     *
     * @param array{posts:array<int,array<string,mixed>>} $data
     * @return array<string, array{name:string, count:int}>
     */
    public static function sourceCategories(array $data): array
    {
        $out = [];
        foreach ($data['posts'] as $post) {
            if ((string) ($post['status'] ?? '') !== 'publish') {
                continue;
            }
            foreach ((array) ($post['categories'] ?? []) as $slug => $name) {
                $slug = (string) $slug;
                $out[$slug] ??= ['name' => (string) $name, 'count' => 0];
                $out[$slug]['count']++;
            }
        }
        uasort($out, static fn (array $a, array $b): int => $b['count'] <=> $a['count'] ?: strcmp($a['name'], $b['name']));

        return $out;
    }

    /**
     * Очевидные соответствия: slug или название совпали без учёта регистра.
     *
     * @param array<string, array{name:string, count:int}> $source
     * @return array<string, int> slug рубрики старого сайта → id раздела
     */
    public static function autoMatch(array $source): array
    {
        $bySlug = [];
        $byName = [];
        foreach (self::sections() as $section) {
            $id = (int) $section['id'];
            $bySlug[mb_strtolower((string) $section['slug'])] ??= $id;
            foreach ($section['names'] as $name) {
                $byName[self::key($name)] ??= $id;
            }
        }
        $map = [];
        foreach ($source as $slug => $info) {
            $id = $bySlug[mb_strtolower(rawurldecode((string) $slug))] ?? $byName[self::key($info['name'])] ?? 0;
            if ($id > 0) {
                $map[(string) $slug] = $id;
            }
        }

        return $map;
    }

    /**
     * Разделы портала с названиями на всех языках — для подбора и для формы.
     *
     * @return list<array{id:int, slug:string, name:string, axis:string, names:list<string>}>
     */
    public static function sections(): array
    {
        $rows = Database::rows(Database::pdo()->query(
            'SELECT id, slug, name, axis FROM news_categories ORDER BY axis ASC, sort_order ASC, name ASC'
        ));
        $names = [];
        foreach (Database::rows(Database::pdo()->query('SELECT category_id, name FROM news_category_translations')) as $t) {
            $names[(int) $t['category_id']][] = (string) $t['name'];
        }

        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'slug' => (string) $r['slug'],
            'name' => (string) $r['name'],
            'axis' => NewsCategory::axis((string) $r['axis']),
            'names' => array_values(array_filter(array_merge([(string) $r['name']], $names[(int) $r['id']] ?? []))),
        ], $rows);
    }

    /**
     * Раздаёт разделы перенесённым новостям.
     *
     * @param array{groups:array<int,array<int,array<string,mixed>>>,unresolved:array<int,array<string,mixed>>} $plan
     * @param array<string,string> $langs
     * @param array<string, int> $map slug рубрики старого сайта → id раздела (0 — не переносить)
     * @return array{news:int, linked:int, rubrics:int, countries:int, kept:int, missing:int, unmapped:int}
     */
    public static function link(array $plan, array $langs, array $map, bool $overwrite = false, bool $dryRun = false): array
    {
        $out = ['news' => 0, 'linked' => 0, 'rubrics' => 0, 'countries' => 0, 'kept' => 0, 'missing' => 0, 'unmapped' => 0];
        $primaryWp = $langs !== [] ? (string) array_key_first($langs) : 'uz';
        $axes = [];
        foreach (self::sections() as $section) {
            $axes[$section['id']] = $section['axis'];
        }

        // Группа → адрес новости и набор разделов.
        $wanted = [];
        foreach (LegacyWxrImporter::importGroups($plan, $langs) as $group) {
            $slug = LegacyWxrImporter::groupSlug($group, $primaryWp);
            if ($slug === '') {
                continue;
            }
            $ids = [];
            foreach (LegacyWxrImporter::groupCategories($group) as $cat) {
                $id = (int) ($map[$cat] ?? 0);
                if ($id > 0 && isset($axes[$id])) {
                    $ids[$id] = true;
                }
            }
            $wanted[$slug] = array_merge($wanted[$slug] ?? [], array_keys($ids));
        }

        $news = self::newsBySlug(array_keys($wanted));
        $countriesOf = NewsCategory::countryIdsFor(array_column($news, 'key'));
        $pdo = Database::pdo();
        $setRubric = $pdo->prepare('UPDATE news SET category_id = :c WHERE ' . News::groupKey('news') . ' = :k AND deleted_at IS NULL');

        foreach ($wanted as $slug => $ids) {
            $row = $news[$slug] ?? null;
            if ($row === null) {
                $out['missing']++;
                continue;
            }
            $out['news']++;
            if ($ids === []) {
                $out['unmapped']++;
                continue;
            }
            $rubric = 0;
            $countries = [];
            foreach ($ids as $id) {
                if ($axes[$id] === NewsCategory::AXIS_COUNTRY) {
                    $countries[] = $id;
                } elseif ($rubric === 0) {
                    $rubric = $id;
                }
            }
            $key = $row['key'];
            $changed = false;
            if ($rubric > 0 && ($overwrite || $row['category_id'] === 0) && $row['category_id'] !== $rubric) {
                if (!$dryRun) {
                    $setRubric->execute([':c' => $rubric, ':k' => $key]);
                }
                $out['rubrics']++;
                $changed = true;
            }
            $current = $countriesOf[$key] ?? [];
            if ($countries !== [] && ($overwrite || $current === [])) {
                $next = $overwrite ? $countries : array_merge($current, $countries);
                sort($next);
                $sortedCurrent = $current;
                sort($sortedCurrent);
                if (array_values(array_unique($next)) !== $sortedCurrent) {
                    if (!$dryRun) {
                        NewsCategory::setCountries($key, $next);
                    }
                    $out['countries']++;
                    $changed = true;
                }
            }
            $changed ? $out['linked']++ : $out['kept']++;
        }

        if (!$dryRun && $out['linked'] > 0) {
            NewsCategory::forgetCache();
            Cache::forgetPrefix('page:');
        }

        return $out;
    }

    /**
     * Новости по адресу пакетами: число запросов не зависит от размера архива.
     *
     * @param list<string> $slugs
     * @return array<string, array{key:int, category_id:int}>
     */
    private static function newsBySlug(array $slugs): array
    {
        $out = [];
        foreach (array_chunk($slugs, 500) as $chunk) {
            $in = implode(',', array_fill(0, count($chunk), '?'));
            $stmt = Database::pdo()->prepare(
                'SELECT slug, ' . News::groupKey('news') . " AS gk, category_id FROM news
                 WHERE deleted_at IS NULL AND slug IN ({$in}) ORDER BY id ASC"
            );
            $stmt->execute($chunk);
            foreach (Database::rows($stmt) as $row) {
                $out[(string) $row['slug']] ??= ['key' => (int) $row['gk'], 'category_id' => (int) ($row['category_id'] ?? 0)];
            }
        }

        return $out;
    }

    private static function key(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name) ?? $name));
    }
}
