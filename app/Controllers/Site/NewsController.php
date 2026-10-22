<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\ContentLanguageNotice;
use App\Core\AppUrl;
use App\Core\Config;
use App\Core\Fragment;
use App\Core\Locale;
use App\Core\OpenGraphHelper;
use App\Core\View;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsImage;
use App\Models\Setting;

final class NewsController
{
    public function index(): void
    {
        // Прежний адрес рубрики (`/news?category=<slug>`) живёт в закладках и
        // внешних ссылках — он уводит на собственный адрес раздела, иначе одна
        // и та же лента жила бы по двум адресам.
        $legacySlug = trim((string) ($_GET['category'] ?? ''));
        if ($legacySlug !== '') {
            $legacy = NewsCategory::findBySlug($legacySlug, true);
            if ($legacy !== null) {
                \App\Core\Redirect::to(Locale::url(NewsCategory::path($legacy)), 301);
            }
        }

        $this->listing(null);
    }

    /** Лента рубрики: `/rubric/<slug>`. @param array<string, string> $params */
    public function rubric(array $params): void
    {
        $this->section($params, NewsCategory::AXIS_RUBRIC);
    }

    /** Лента страны: `/country/<slug>`. @param array<string, string> $params */
    public function country(array $params): void
    {
        $this->section($params, NewsCategory::AXIS_COUNTRY);
    }

    /** @param array<string, string> $params */
    private function section(array $params, string $axis): void
    {
        $category = NewsCategory::findBySlug((string) ($params['slug'] ?? ''), true, $axis);
        if ($category === null) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $this->listing(NewsCategory::localize($category, Locale::current()));
    }

    /**
     * Лента целиком или одного раздела. Разметка одна: у раздела свой
     * заголовок и свой адрес, а рубрикатор и страны ведут на адреса
     * разделов, а не на параметр общей ленты.
     *
     * @param array<string, mixed>|null $section
     */
    private function listing(?array $section): void
    {
        $lang = Locale::current();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $sectionId = $section !== null ? (int) $section['id'] : 0;

        $total = News::publishedCount($sectionId > 0 ? $sectionId : null, $lang);

        // Композиция страницы одна и та же везде — и на первой, и в разделе:
        // лента идёт ритмом (App\Core\NewsFeedRhythm), размер страницы
        // задаёт он.
        $gridPageSize = \App\Core\NewsFeedRhythm::PAGE_SIZE;
        $pages = max(1, (int) ceil($total / $gridPageSize));
        $page = min($page, $pages);
        $offset = ($page - 1) * $gridPageSize;

        $vars = [
            'items' => News::published($gridPageSize, $offset, $lang, $sectionId > 0 ? $sectionId : null),
            'page' => $page,
            'pages' => $pages,
            'categories' => News::publishedCategories($lang, NewsCategory::AXIS_RUBRIC),
            'countries' => News::publishedCategories($lang, NewsCategory::AXIS_COUNTRY),
            'section' => $section,
            'basePath' => $section !== null ? NewsCategory::path($section) : 'news',
            // Заголовок, лид, SEO и блоки под лентой берутся из страницы,
            // назначенной шапкой раздела в админке (может не быть вовсе).
            // У рубрики и страны шапка своя — название раздела.
            'sectionPage' => $section === null ? \App\Core\SectionPage::render('news', $lang) : null,
        ];

        // AJAX-переход по страницам: тот же список, но без шапки и подвала.
        if (Fragment::wanted()) {
            Fragment::render('site/_news_list', $vars);
            return;
        }

        // Лента использует тот же feature-card interaction, что новости на
        // главной: общие рамка, акцентный край, hover и spotlight. Регистрация
        // news_feature заодно подключает стили .relnews-card, нужные контенту.
        \App\Core\AssetCollector::requireJs('news_feature');
        \App\Core\AssetCollector::requireCss('news-index-pagination', '/assets/css/news-index-pagination.css');

        View::render('site/news_index', $vars);
    }

    /**
     * RSS 2.0 лента последних новостей (стандарт для гос-сайтов и агрегаторов).
     */
    public function feed(): void
    {
        $lang = Locale::current();
        $items = News::published(30, 0, $lang);
        $base = AppUrl::base();
        $siteName = (string) Setting::get('site_name', 'ArtStudio');
        $selfUrl = $base . Locale::url('news/rss.xml', $lang);

        header('Content-Type: application/rss+xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        echo '<channel>' . "\n";
        echo '  <title>' . htmlspecialchars($siteName . ' — ' . t('Новости'), ENT_XML1) . '</title>' . "\n";
        echo '  <link>' . htmlspecialchars($base . Locale::url('news', $lang), ENT_XML1) . '</link>' . "\n";
        echo '  <description>' . htmlspecialchars((string) Setting::get('default_meta_description', t('Новости')), ENT_XML1) . '</description>' . "\n";
        echo '  <language>' . htmlspecialchars($lang, ENT_XML1) . '</language>' . "\n";
        echo '  <atom:link href="' . htmlspecialchars($selfUrl, ENT_XML1) . '" rel="self" type="application/rss+xml"/>' . "\n";
        foreach ($items as $item) {
            $link = $base . Locale::url('news/' . $item['slug'], $lang);
            $pub = $item['published_at'] ? \App\Core\DateFormatter::format((string) $item['published_at'], DATE_RSS) : date(DATE_RSS);
            echo '  <item>' . "\n";
            echo '    <title>' . htmlspecialchars((string) $item['title'], ENT_XML1) . '</title>' . "\n";
            echo '    <link>' . htmlspecialchars($link, ENT_XML1) . '</link>' . "\n";
            echo '    <guid isPermaLink="true">' . htmlspecialchars($link, ENT_XML1) . '</guid>' . "\n";
            echo '    <pubDate>' . htmlspecialchars($pub, ENT_XML1) . '</pubDate>' . "\n";
            if (!empty($item['excerpt'])) {
                echo '    <description>' . htmlspecialchars((string) $item['excerpt'], ENT_XML1) . '</description>' . "\n";
            }
            echo '  </item>' . "\n";
        }
        echo '</channel>' . "\n";
        echo '</rss>' . "\n";
        exit;
    }

    /**
     * Скачивание всех фото новости одним zip-архивом (кнопка «Скачать все фото»).
     * В архив попадают только файлы из каталога публичных загрузок.
     * @param array<string, string> $params
     */
    public function photosZip(array $params): void
    {
        $lang = Locale::current();
        $news = News::findPublishedBySlug($params['slug'] ?? '', $lang);
        if (!$news || !class_exists(\ZipArchive::class)) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $uploadsDir = rtrim((string) Config::get('paths.public_uploads', ''), '/');
        $uploadsUrl = rtrim((string) Config::get('paths.public_uploads_url', '/uploads/public'), '/');
        $paths = [];
        $cover = trim((string) ($news['image'] ?? ''));
        if ($cover !== '') {
            $paths[] = $cover;
        }
        foreach (NewsImage::forNews((int) $news['id']) as $img) {
            $paths[] = (string) $img['path'];
        }

        $files = [];
        foreach (array_unique($paths) as $url) {
            // Разрешаем только файлы из публичных загрузок; realpath отсекает выход из каталога.
            if ($uploadsDir === '' || strncmp($url, $uploadsUrl . '/', strlen($uploadsUrl) + 1) !== 0) {
                continue;
            }
            $file = realpath($uploadsDir . '/' . substr($url, strlen($uploadsUrl) + 1));
            if ($file !== false && strncmp($file, (string) realpath($uploadsDir), strlen((string) realpath($uploadsDir))) === 0 && is_file($file)) {
                $files[] = $file;
            }
        }
        if ($files === []) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ndz');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        foreach ($files as $i => $file) {
            $zip->addFile($file, sprintf('photo-%02d-%s', $i + 1, basename($file)));
        }
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . rawurlencode((string) $news['slug']) . '-photos.zip"');
        header('Content-Length: ' . (string) filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    /** @param array<string, string> $params */
    public function show(array $params): void
    {
        $lang = Locale::current();
        $requestedSlug = trim((string) ($params['slug'] ?? ''));
        $news = News::findPublishedBySlug($requestedSlug, $lang);

        if (!$news) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $canonicalSlug = (string) ($news['slug'] ?? '');
        if (($news['lang'] ?? '') === $lang && $canonicalSlug !== '' && $requestedSlug !== $canonicalSlug) {
            header('Location: ' . Locale::url('news/' . $canonicalSlug, $lang), true, 301);
            exit;
        }

        $available = News::availableLangs((int) $news['id']);
        if (ContentLanguageNotice::renderIfMissing($available, '/news/' . (string) $news['slug'])) {
            return;
        }

        News::incrementViews((int) $news['id']);
        // hreflang и переключатель — только языки с переводом этой новости.
        Locale::setContentLangs($available);
        Locale::setAlternatePaths(\App\Core\TranslationGroupHelper::publishedPaths('news', (int) $news['id'], 'news/'));
        $adjacent = News::adjacent($news, $lang);

        $sidebar = \App\Core\WidgetRenderer::sidebarFor($news['sidebar_layout'] ?? 'right_sidebar', $lang);
        $gallery = NewsImage::forNews((int) $news['id']);

        // Все фото новости публикуем в Open Graph массивом: обложка первой,
        // далее уникальные кадры галереи. Обычные соцсети смогут выбрать
        // превью, а отдельный page-only helper использует те же og:image
        // для нативного File[] share через системное меню устройства.
        $socialImages = [];
        $coverImage = News::getCoverImage($news);
        if ($coverImage !== null && trim($coverImage) !== '') {
            $socialImages[] = trim($coverImage);
        }
        foreach ($gallery as $image) {
            $path = trim((string) ($image['path'] ?? ''));
            if ($path !== '' && !in_array($path, $socialImages, true)) {
                $socialImages[] = $path;
            }
        }
        OpenGraphHelper::setAdditionalImages($socialImages);

        // Стили детальной новости (.newsdetail-*, .relnews-*) вынесены из
        // общего бандла: 42 КБ правил, нужных только здесь, приезжали на
        // каждую страницу сайта, включая главную.
        \App\Core\AssetCollector::requireThemePart('news_detail');
        // Нативное системное «Поделиться» может передать все файлы галереи,
        // в отличие от URL-share эндпоинтов отдельных соцсетей.
        \App\Core\AssetCollector::requireJs('news_share_gallery');
        // Миниатюры галереи должны использовать тот же рабочий responsive-
        // источник, что и большой кадр. Это особенно важно для импортированных
        // фото, у которых WebP-вариант уже есть, а исходник недоступен.
        \App\Core\AssetCollector::requireJs('news_gallery_thumbnails');

        View::render('site/news_show', [
            'news' => $news,
            'gallery' => $gallery,
            'related' => News::related((int) $news['id'], 4, $lang),
            'prevNews' => $adjacent['prev'],
            'nextNews' => $adjacent['next'],
            'sidebar' => $sidebar,
        ]);
    }
}
