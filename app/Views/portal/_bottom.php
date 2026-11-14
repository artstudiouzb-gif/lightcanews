<?php
/**
 * Низ страницы нового шаблона: подвал и скрипт портала.
 *
 * Тексты подвала берутся из тех же мест, что и раньше: описание — из колонки
 * «О сайте» конструктора подвала, соцсети — из конструктора шапки, меню
 * редакции — из основного меню, строка копирайта — из подвала. Новый шаблон
 * меняет вид, а не то, где владелец правит содержимое.
 */

use App\Core\Asset;
use App\Core\FooterConfig;
use App\Core\HeaderConfig;
use App\Core\Locale;
use App\Core\Portal\Portal;
use App\Models\MenuItem;
use App\Models\NewsCategory;
use App\Models\Setting;

$footLang = Locale::current();
$footSite = Setting::getLocalized('site_name', $footLang, 'LightCA.News');
$footCfg = FooterConfig::get();
$footAbout = '';
foreach ((array) ($footCfg['columns'] ?? []) as $footCol) {
    if (($footCol['widget'] ?? '') === 'about' && trim((string) ($footCol['text'] ?? '')) !== '') {
        $footAbout = (string) $footCol['text']; // очищено санитайзером при сохранении
        break;
    }
}
$footSocial = (array) (HeaderConfig::get()['social_buttons'] ?? []);
$footMenu = [];
try {
    $footMenu = array_values(array_filter(
        MenuItem::activeForLang($footLang),
        static fn (array $item): bool => empty($item['is_divider']) && empty($item['parent_id'])
    ));
} catch (\Throwable $e) {
    \App\Core\Logger::swallowed('Подвал портала: меню не загрузилось', $e);
}
$footRubrics = NewsCategory::all($footLang, true, NewsCategory::AXIS_RUBRIC);
$footCountries = NewsCategory::all($footLang, true, NewsCategory::AXIS_COUNTRY);
$footBottom = FooterConfig::renderBottom((string) ($footCfg['bottom'] ?? ''), $footSite);
$footBrand = (string) preg_replace('/\.(news)$/i', '<span>.</span>$1', htmlspecialchars($footSite, ENT_QUOTES));
?>
</main>

<footer class="pt-foot">
    <div class="pt-wrap">
        <div class="pt-foot__grid">
            <div>
                <p class="pt-foot__brand"><?= $footBrand ?></p>
                <?php if ($footAbout !== ''): ?><div class="pt-foot__about"><?= $footAbout ?></div><?php endif; ?>
                <?php if ($footSocial !== []): ?>
                <div class="pt-foot__social">
                    <?php foreach ($footSocial as $footBtn): ?>
                        <?php $footUrl = (string) ($footBtn['url'] ?? ''); if ($footUrl === '' || !\App\Core\UrlGuard::isSafeLink($footUrl)) { continue; } ?>
                        <a href="<?= htmlspecialchars($footUrl, ENT_QUOTES) ?>" target="_blank" rel="noopener"><?= htmlspecialchars(mb_convert_case((string) ($footBtn['network'] ?? ''), MB_CASE_TITLE), ENT_QUOTES) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($footRubrics !== []): ?>
            <nav aria-label="<?= htmlspecialchars(t('Рубрики'), ENT_QUOTES) ?>">
                <h2 class="pt-foot__head"><?= htmlspecialchars(t('Рубрики'), ENT_QUOTES) ?></h2>
                <ul>
                    <?php foreach ($footRubrics as $footRubric): ?>
                        <li><a href="<?= htmlspecialchars(Locale::url(NewsCategory::path($footRubric)), ENT_QUOTES) ?>"><?= htmlspecialchars((string) $footRubric['name'], ENT_QUOTES) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php if ($footCountries !== []): ?>
            <nav aria-label="<?= htmlspecialchars(t('Страны'), ENT_QUOTES) ?>">
                <h2 class="pt-foot__head"><?= htmlspecialchars(t('Страны'), ENT_QUOTES) ?></h2>
                <ul>
                    <?php foreach ($footCountries as $footCountry): ?>
                        <li><a href="<?= htmlspecialchars(Locale::url(NewsCategory::path($footCountry)), ENT_QUOTES) ?>"><?= htmlspecialchars((string) $footCountry['name'] . ' (' . Portal::countryCode($footCountry) . ')', ENT_QUOTES) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php if ($footMenu !== []): ?>
            <nav aria-label="<?= htmlspecialchars(t('Редакция'), ENT_QUOTES) ?>">
                <h2 class="pt-foot__head"><?= htmlspecialchars(t('Редакция'), ENT_QUOTES) ?></h2>
                <ul>
                    <?php foreach ($footMenu as $footItem): ?>
                        <li><a href="<?= htmlspecialchars(MenuItem::resolveUrl($footItem, $footLang), ENT_QUOTES) ?>"><?= htmlspecialchars((string) $footItem['title'], ENT_QUOTES) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
        <div class="pt-foot__bottom">
            <span><?= htmlspecialchars($footBottom, ENT_QUOTES) ?></span>
            <span><?= htmlspecialchars(t('При использовании материалов активная ссылка на источник обязательна'), ENT_QUOTES) ?></span>
        </div>
    </div>
</footer>
<script src="<?= htmlspecialchars(Asset::url('/assets/portal/portal.js'), ENT_QUOTES) ?>" defer></script>
<?= \App\Core\AssetCollector::renderScripts() /* скрипты блоков на странице — по одному разу */ ?>
</body>
</html>
