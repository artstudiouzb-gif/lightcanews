<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Search;
use App\Models\News;

// Полнотекстовый поиск по сайту: многословный AND.

test('Site search: все слова запроса должны встретиться (AND)', function () {
    if (!Database::isConnected()) {
        return;
    }
    $marker = 'zztest' . bin2hex(random_bytes(2));
    $id = News::create([
        'title' => 'Новость ' . $marker . ' модернизация',
        'slug' => '_srch_' . $marker,
        'excerpt' => '',
        'content' => '',
        'status' => 'published',
        'published_at' => date('Y-m-d H:i:s', time() - 60),
        'lang' => 'ru',
    ]);

    // Оба слова присутствуют → находим.
    $hit = Search::site($marker . ' модернизация', 40);
    $titles = array_map(static fn ($r) => $r['title'], $hit);
    $found = false;
    foreach ($titles as $t) {
        if (str_contains($t, $marker)) { $found = true; break; }
    }
    assert_true($found, 'новость найдена по двум присутствующим словам');

    // Добавляем слово, которого нет в записи → AND отсекает.
    $miss = Search::site($marker . ' отсутствующееслово', 40);
    $foundMiss = false;
    foreach ($miss as $r) {
        if (str_contains($r['title'], $marker)) { $foundMiss = true; break; }
    }
    assert_true(!$foundMiss, 'при лишнем несовпадающем слове запись не находится (AND)');

    News::forceDelete($id);
});
