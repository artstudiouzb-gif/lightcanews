<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Input;
use App\Core\Portal\PortalAds;
use App\Core\Redirect;
use App\Core\View;

/** Рекламные зоны портала: пять мест, одна форма. */
final class AdController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();
        View::render('admin/ads/index', ['zones' => PortalAds::all()]);
    }

    public function update(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        PortalAds::save(Input::post()->group('zones'));
        // Реклама печатается на каждой странице, а страницы кэшируются.
        Cache::forgetPrefix('page:');

        Flash::success('Рекламные зоны сохранены.');
        Redirect::to('/admin/ads');
    }
}
