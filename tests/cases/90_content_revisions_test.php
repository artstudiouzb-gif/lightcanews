<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\ConcurrencyException;
use App\Models\ContentRevision;
use App\Models\Page;

test('content revision UI exposes history links and local draft safeguards', function (): void {
    $root = dirname(__DIR__, 2);
    $pageForm = (string) file_get_contents($root . '/app/Views/admin/pages/form.php');
    $newsForm = (string) file_get_contents($root . '/app/Views/admin/news/form.php');
    $adminJs = (string) file_get_contents($root . '/public/assets/js/admin.js');

    assert_contains('/admin/revisions/page/', $pageForm);
    assert_contains('/admin/revisions/news/', $newsForm);
    assert_contains('expected_updated_at', $pageForm);
    assert_contains('expected_lock_version', $pageForm);
    assert_contains('data-content-draft', $newsForm);
    assert_contains('artstudio:draft:', $adminJs);
    assert_contains('draft_saved', $adminJs);
    assert_contains('beforeunload', $adminJs);
});

test('content save: stale lock_version rolls back parent and children', function (): void {
    if (!Database::isConnected()) {
        return;
    }
    $pdo = Database::pdo();
    $base = [
        'title' => 'CAS original', 'slug' => 'cas-' . bin2hex(random_bytes(4)),
        'meta_title' => null, 'meta_description' => null, 'lead' => null,
        'status' => 'draft', 'layout_type' => 'no_sidebar', 'custom_css' => '', 'custom_js' => '',
    ];
    $id = Page::create($base);
    $version = (int) Page::findById($id)['lock_version'];
    $data = array_merge($base, ['title' => 'CAS first']);
    try {
        Page::update($id, $data, $version);
        $failed = false;
        try {
            Database::transaction(static function (\PDO $_pdo) use ($id, $data, $version): void {
                Page::update($id, array_merge($data, ['title' => 'CAS stale']), $version);
            });
        } catch (ConcurrencyException) {
            $failed = true;
        }
        assert_true($failed, 'устаревшее сохранение отклонено');
        assert_same('CAS first', Page::findById($id)['title']);
    } finally {
        Page::forceDelete($id);
    }
});
