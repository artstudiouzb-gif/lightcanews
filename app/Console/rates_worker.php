<?php

declare(strict_types=1);

/*
 * Курсы валют для плашки в шапке (элемент «Курсы валют»).
 *   php app/Console/rates_worker.php
 *
 * Cron (ЦБ обновляет курсы раз в день; чаще — без пользы):
 *   15 * * * * php /path/to/app/Console/rates_worker.php >> /path/to/storage/logs/rates_worker.log 2>&1
 *
 * Публичная страница в сеть не ходит, поэтому курсы забирает этот воркер, а
 * шапка показывает сохранённую копию (App\Core\CurrencyRates).
 */

require __DIR__ . '/../Core/Cli.php';
\App\Core\Cli::assertCli();

require __DIR__ . '/../Core/bootstrap.php';

use App\Core\Cache;
use App\Core\CurrencyRates;
use App\Core\ProcessLock;

$lock = ProcessLock::acquire('rates_worker');
if ($lock === null) {
    fwrite(STDERR, 'rates_worker уже выполняется — пропуск запуска.' . PHP_EOL);
    exit(0);
}

try {
    $result = CurrencyRates::refresh();
    if (!$result['ok']) {
        fwrite(STDERR, 'Курсы не обновлены: ' . $result['error'] . PHP_EOL);
        exit(1);
    }
    // Шапка входит в кэш страниц: без сброса новые курсы появились бы только
    // после следующей правки контента.
    Cache::forgetPrefix('page:');
    fwrite(STDOUT, 'Курсы валют обновлены.' . PHP_EOL);
} finally {
    ProcessLock::release($lock);
}

exit(0);
