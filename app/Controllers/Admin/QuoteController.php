<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Input;
use App\Core\Portal\MarketQuotes;
use App\Core\Redirect;
use App\Core\View;
use App\Models\Language;

/** Котировки сырья: хлопок, пряжа. Цифры вводит редакция. */
final class QuoteController
{
    public function index(): void
    {
        Auth::requireLogin();
        View::render('admin/quotes/index', [
            'quotes' => MarketQuotes::all(),
            'languages' => Language::active(),
        ]);
    }

    public function update(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $post = Input::post();
        MarketQuotes::save($post->group('items'), $post->str('source', '', 120));
        // Полоса курсов стоит на главной, а страницы кэшируются.
        Cache::forgetPrefix('page:');

        Flash::success('Котировки сохранены.');
        Redirect::to('/admin/quotes');
    }
}
