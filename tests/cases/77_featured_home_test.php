<?php

declare(strict_types=1);

use App\Core\BlockRenderer;
use App\Core\Database;
use App\Models\PhotoAlbum;

// «Показать на главном»: блоки cards_grid/media_gallery с автоисточником
// собирают карточки из отмеченных записей (проекты / фотоальбомы).

test('media_gallery с источником «albums» рендерит карточки фотоальбомов', function () {
    if (!Database::isConnected()) {
        return;
    }
    $albumId = PhotoAlbum::create('Альбом для главной', '', '/uploads/public/_fh_cover.jpg', true);
    assert_true($albumId !== null, 'альбом создан');
    PhotoAlbum::update($albumId, 'Альбом для главной', '', '/uploads/public/_fh_cover.jpg', true, true);

    $block = ['id' => 0, 'type' => 'media_gallery', 'data' => json_encode(['source' => 'albums', 'limit' => 8])];
    $html = BlockRenderer::render($block)['html'];

    assert_true(str_contains($html, 'Альбом для главной'), 'заголовок альбома отрисован');
    assert_true(str_contains($html, '/albums/'), 'ссылка ведёт на страницу альбома');

    PhotoAlbum::delete($albumId);
});

test('Ручной источник (manual) по-прежнему использует список items', function () {
    if (!Database::isConnected()) {
        return;
    }
    $block = ['id' => 0, 'type' => 'cards_grid', 'data' => json_encode([
        'variant' => 'image',
        'source' => 'manual',
        'items' => [['image' => '/x.jpg', 'title' => 'Ручная карточка', 'url' => '']],
    ])];
    $html = BlockRenderer::render($block)['html'];

    assert_true(str_contains($html, 'Ручная карточка'), 'ручной список отрисован');
});
