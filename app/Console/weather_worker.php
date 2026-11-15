<?php

declare(strict_types=1);

/*
 * Погода для шапки портала (Open-Meteo, без ключа).
 *   php app/Console/weather_worker.php
 *
 * Cron необязателен: без него копия обновляется после ответа посетителю.
 *   7 * * * * php /path/to/app/Console/weather_worker.php >> /path/to/storage/logs/weather_worker.log 2>&1
 */

require __DIR__ . '/../Core/Cli.php';
\App\Core\Cli::assertCli();

require __DIR__ . '/../Core/bootstrap.php';

use App\Core\ProcessLock;
use App\Core\Weather;

$lock = ProcessLock::acquire('weather_worker');
if ($lock === null) {
    fwrite(STDERR, 'weather_worker уже выполняется — пропуск запуска.' . PHP_EOL);
    exit(0);
}

try {
    $result = Weather::refresh();
    if (!$result['ok']) {
        fwrite(STDERR, 'Погода не обновлена: ' . $result['error'] . PHP_EOL);
        exit(1);
    }
    fwrite(STDOUT, 'Погода обновлена.' . PHP_EOL);
} finally {
    ProcessLock::release($lock);
}

exit(0);
