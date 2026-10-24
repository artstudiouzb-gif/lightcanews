<?php

declare(strict_types=1);

use App\Core\BlockRenderer;
use App\Core\Database;
use App\Models\News;
use App\Models\NewsCategory;

/** @return string разметка блока «Новостная витрина» с данными */
function news_front_html(array $data): string
{
    return (string) BlockRenderer::render([
        'id' => 901,
        'type' => 'news_front',
        'data' => json_encode($data, JSON_THROW_ON_ERROR),
        'custom_css' => '',
    ])['html'];
}

test('Новостная витрина: главное, страны, выбор редакции и пустой раздел (БД)', function (): void {
    ensure_test_db();

    $pdo = Database::pdo();
    $suffix = bin2hex(random_bytes(4));
    $rubric = (int) NewsCategory::create('Рубрика ' . $suffix, 'nf-rubric-' . $suffix);
    $country = (int) NewsCategory::create('Страна ' . $suffix, 'nf-country-' . $suffix, axis: NewsCategory::AXIS_COUNTRY);

    $insert = $pdo->prepare(
        "INSERT INTO news (title, slug, category_id, excerpt, content, status, published_at, lang, created_at)
         VALUES (:title, :slug, :cat, 'Анонс', '<p>x</p>', 'published', NOW() - INTERVAL :age MINUTE, 'ru', NOW())"
    );
    $ids = [];
    for ($n = 0; $n < 6; $n++) {
        $insert->execute([':title' => "Материал {$n} {$suffix}", ':slug' => "nf-{$n}-{$suffix}", ':cat' => $rubric, ':age' => $n + 1]);
        $ids[] = (int) $pdo->lastInsertId();
    }
    NewsCategory::setCountries($ids[0], [$country]);
    News::setEditorPick($ids[1], true);

    // Главное: крупная новость с меткой обеих осей, ряд и «Последние».
    $main = news_front_html(['layout' => 'main', 'category' => $rubric, 'limit' => 1, 'latest_limit' => 1]);
    assert_contains('nf-lead', $main, 'главное открывается крупной новостью');
    assert_contains('Материал 0 ' . $suffix, $main);
    assert_contains('nf-kicker__country">Страна ' . $suffix, $main, 'метка называет страну');
    assert_contains('nf-latest', $main, 'колонка «Последние новости» сбоку');
    assert_not_contains('nf--main-wide', $main);
    $wide = news_front_html(['layout' => 'main', 'category' => $rubric, 'latest_limit' => 0]);
    assert_contains('nf--main-wide', $wide, 'без «Последних» список занимает всю ширину');

    // Страны: вкладки скрыты до скрипта, панель страны со своим адресом.
    $countries = news_front_html(['layout' => 'countries']);
    assert_contains('data-nf-tabs', $countries);
    assert_contains('role="tablist"', $countries);
    assert_contains('/country/nf-country-' . $suffix, $countries, 'панель ведёт в ленту страны');

    // Выбор редакции: только отмеченные вручную.
    $picks = news_front_html(['layout' => 'picks']);
    assert_contains('Материал 1 ' . $suffix, $picks);
    assert_not_contains('Материал 2 ' . $suffix, $picks, 'неотмеченная новость в подборку не попадает');

    // Пустой раздел говорит об этом, а не исчезает.
    News::setEditorPick($ids[1], false);
    $empty = news_front_html(['layout' => 'picks']);
    assert_contains('nf-empty', $empty, 'пустая подборка показывает сообщение');
});
