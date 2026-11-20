<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\SettingsValidator;
use App\Models\Setting;

/**
 * PWA-манифест (задача 116), собираемый из настроек. Отдаётся по
 * /manifest.webmanifest.
 */
final class ManifestController
{
    public function webmanifest(): void
    {
        header('Content-Type: application/manifest+json; charset=UTF-8');

        $name = Setting::get('site_name', 'LightCA.News');
        $short = SettingsValidator::shortName(Setting::get('pwa_short_name', $name));
        $themeColor = SettingsValidator::hexColor(Setting::get('theme_color', '#1a1a1a'), '#1a1a1a');
        $favicon = Setting::get('favicon_url', '');

        $manifest = [
            'name' => $name,
            'short_name' => $short !== '' ? $short : mb_substr($name, 0, 12),
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => $themeColor,
        ];
        if ($favicon !== '') {
            $type = str_ends_with(strtolower($favicon), '.svg') ? 'image/svg+xml' : 'image/png';
            $manifest['icons'] = [[
                'src' => $favicon,
                'sizes' => 'any',
                'type' => $type,
            ]];
        }

        echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Правила упреждающей загрузки (`SpeculationRules::PATH`). Набор общий
     * для всех страниц и меняется только с настройкой, поэтому кэшируется.
     * Приём выключен — 404: заголовок на него тогда и не указывает.
     */
    public function speculationRules(): void
    {
        $json = \App\Core\SpeculationRules::json();
        if ($json === null) {
            http_response_code(404);
            header('Cache-Control: no-store');
            return;
        }
        header('Content-Type: application/speculationrules+json');
        header('Cache-Control: public, max-age=3600');
        echo $json;
    }
}
