<?php
/**
 * Низ страницы нового шаблона: подвал и скрипт портала.
 *
 * Колонки подвала — из конструктора подвала (`FooterConfig`): их число,
 * заголовки и виджеты (знак с описанием, меню, рубрики, страны, контакты,
 * соцсети, подписка, текст), по два виджета в колонке. Вид — свой, по
 * прототипу: конструктор задаёт содержимое, а не оформление, поэтому его
 * макеты, фон и узоры здесь не действуют. Соцсети — кнопки конструктора
 * шапки, как и везде на сайте.
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
$footSocial = (array) (HeaderConfig::get()['social_buttons'] ?? []);
$footBottom = FooterConfig::renderBottom((string) ($footCfg['bottom'] ?? ''), $footSite);
$footBrand = (string) preg_replace('/\.(news)$/i', '<span>.</span>$1', htmlspecialchars($footSite, ENT_QUOTES));
$footE = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES);

/** Ссылки списком: пустой список не печатается. */
$footList = static function (array $links) use ($footE): string {
    if ($links === []) {
        return '';
    }
    $h = '<ul>';
    foreach ($links as [$label, $url]) {
        $h .= '<li><a href="' . $footE($url) . '">' . $label . '</a></li>';
    }

    return $h . '</ul>';
};

/** Один виджет колонки в разметке портала; пустой виджет — пустая строка. */
$footWidget = static function (string $widget, string $text) use ($footLang, $footBrand, $footSocial, $footE, $footList): string {
    switch ($widget) {
        case 'about':
            // Текст уже очищен санитайзером при сохранении.
            return '<p class="pt-foot__brand">' . $footBrand . '</p>'
                . (trim($text) !== '' ? '<div class="pt-foot__about">' . $text . '</div>' : '');
        case 'menu':
            $links = [];
            try {
                foreach (MenuItem::activeForLang($footLang) as $item) {
                    if (empty($item['is_divider']) && empty($item['parent_id'])) {
                        $links[] = [$footE((string) $item['title']), MenuItem::resolveUrl($item, $footLang)];
                    }
                }
            } catch (\Throwable $e) {
                \App\Core\Logger::swallowed('Подвал портала: меню не загрузилось', $e);
            }

            return $footList($links);
        case 'rubrics':
        case 'countries':
            // Разделы — из категорий: новая рубрика или страна появляется в
            // подвале сама. У стран метка — по настройке «Дизайна».
            $countries = $widget === 'countries';
            $links = [];
            foreach (NewsCategory::all($footLang, true, $countries ? NewsCategory::AXIS_COUNTRY : NewsCategory::AXIS_RUBRIC) as $section) {
                $label = $footE((string) $section['name']);
                if ($countries && Portal::countryMarkMode() === 'flags') {
                    $label = Portal::countryMark($section) . ' ' . $label;
                } elseif ($countries && Portal::countryMarkMode() === 'codes') {
                    $label .= ' (' . $footE(Portal::countryCode($section)) . ')';
                }
                $links[] = [$label, Locale::url(NewsCategory::path($section), $footLang)];
            }

            return $footList($links);
        case 'contacts':
            $links = [];
            $address = trim((string) Setting::get('contact_address', ''));
            $phone = trim((string) Setting::get('contact_phone', ''));
            $email = trim((string) Setting::get('contact_email', ''));
            $h = '';
            if ($address !== '') {
                $h .= '<li>' . $footE($address) . '</li>';
            }
            if ($phone !== '') {
                $h .= '<li><a href="tel:' . $footE((string) preg_replace('/[^0-9+]/', '', $phone)) . '">' . $footE($phone) . '</a></li>';
            }
            if ($email !== '') {
                $h .= '<li><a href="mailto:' . $footE($email) . '">' . $footE($email) . '</a></li>';
            }

            return $h !== '' ? '<ul>' . $h . '</ul>' : '';
        case 'social':
            $h = '';
            foreach ($footSocial as $btn) {
                $url = (string) ($btn['url'] ?? '');
                if ($url === '' || !\App\Core\UrlGuard::isSafeLink($url)) {
                    continue;
                }
                $network = mb_convert_case((string) ($btn['network'] ?? ''), MB_CASE_TITLE);
                $h .= '<a href="' . $footE($url) . '" target="_blank" rel="noopener" aria-label="' . $footE(t('Мы в соцсети') . ': ' . $network) . '">' . $footE($network) . '</a>';
            }

            return $h !== '' ? '<div class="pt-foot__social">' . $h . '</div>' : '';
        case 'subscribe':
            return '<form class="pt-foot__subscribe" method="post" action="' . $footE(Locale::url('subscribe', $footLang)) . '">'
                . \App\Core\Csrf::field() . \App\Core\Csrf::honeypotField()
                . '<input type="hidden" name="source" value="footer">'
                . '<input type="email" name="email" placeholder="you@company.uz" aria-label="E-mail" autocomplete="email" required>'
                . '<button type="submit">' . $footE(t('Подписаться')) . '</button>'
                . '</form>';
        case 'text':
            return trim($text) !== '' ? '<div class="pt-foot__about">' . $text . '</div>' : '';
        default:
            return '';
    }
};

$footColumns = [];
// «Минимальный» — только нижняя строка с копирайтом.
foreach ((($footCfg['style'] ?? 'columns') === 'minimal' ? [] : (array) ($footCfg['columns'] ?? [])) as $footCol) {
    $footParts = [];
    foreach ([[(string) $footCol['widget'], (string) $footCol['text']], [(string) ($footCol['widget2'] ?? ''), (string) ($footCol['text2'] ?? '')]] as [$footW, $footT]) {
        if ($footW !== '') {
            $footHtml = $footWidget($footW, $footT);
            if ($footHtml !== '') {
                $footParts[] = $footHtml;
            }
        }
    }
    // Пустая колонка не печатается: рамка с одним заголовком читается как поломка.
    if ($footParts !== []) {
        $footColumns[] = ['heading' => trim((string) $footCol['heading']), 'html' => implode('', $footParts), 'nav' => in_array($footCol['widget'], ['menu', 'rubrics', 'countries'], true)];
    }
}
$footLinks = '';
foreach ((array) ($footCfg['bottom_links'] ?? []) as $footLink) {
    $footLinks .= '<a href="' . $footE(MenuItem::resolveUrl(['url_type' => 'custom', 'url_value' => (string) $footLink['url']], $footLang)) . '">' . $footE(t((string) $footLink['label'])) . '</a>';
}
?>
</main>

<footer class="pt-foot">
    <div class="pt-wrap">
        <?php if ($footColumns !== []): ?>
        <div class="pt-foot__grid pt-foot__grid--c<?= min(4, count($footColumns)) ?>">
            <?php foreach ($footColumns as $footCol): ?>
                <?php $footTag = $footCol['nav'] ? 'nav' : 'div'; ?>
                <<?= $footTag ?><?= $footCol['nav'] && $footCol['heading'] !== '' ? ' aria-label="' . $footE(t($footCol['heading'])) . '"' : '' ?>>
                    <?php if ($footCol['heading'] !== ''): ?><h2 class="pt-foot__head"><?= $footE(t($footCol['heading'])) ?></h2><?php endif; ?>
                    <?= $footCol['html'] ?>
                </<?= $footTag ?>>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="pt-foot__bottom">
            <span><?= $footE($footBottom) ?></span>
            <?php if ($footLinks !== ''): ?><nav class="pt-foot__links" aria-label="<?= $footE(t('Дополнительные ссылки')) ?>"><?= $footLinks ?></nav><?php endif; ?>
            <span><?= $footE(t('При использовании материалов активная ссылка на источник обязательна')) ?></span>
        </div>
    </div>
</footer>
<script src="<?= htmlspecialchars(Asset::url('/assets/portal/portal.js'), ENT_QUOTES) ?>" defer></script>
<?= \App\Core\AssetCollector::renderScripts() /* скрипты блоков на странице — по одному разу */ ?>
</body>
</html>
