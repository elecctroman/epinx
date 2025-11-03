<?php
declare(strict_types=1);

use App\Core\Env;
use App\Services\QueueWorker;
use App\Services\SupplierService;

if (php_sapi_name() === 'cli') {
    parse_str($argv[1] ?? '', $cliQuery);
    foreach ($cliQuery as $key => $value) {
        $_GET[$key] = $value;
    }
}

$container = require __DIR__ . '/bootstrap.php';

$key = (string) ($_GET['key'] ?? '');
$expectedKey = (string) Env::get('CRON_KEY', '');

if ($expectedKey === '' || !hash_equals($expectedKey, $key)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid key']);
    exit;
}

$task = (string) ($_GET['task'] ?? 'all');

header('Content-Type: application/json; charset=utf-8');

$results = [
    'queue' => null,
    'sync' => null,
    'maintenance' => null,
];

if ($task === 'all' || $task === 'queue') {
    /** @var QueueWorker $worker */
    $worker = $container->get(QueueWorker::class);
    /** @var SupplierService $suppliers */
    $suppliers = $container->get(SupplierService::class);
    $results['queue'] = [
        'queued_followups' => $suppliers->queuePendingFollowUps(),
        'processed' => $worker->run(50),
    ];
}

if ($task === 'all' || $task === 'sync') {
    /** @var SupplierService $suppliers */
    $suppliers = $container->get(SupplierService::class);
    $results['sync'] = $suppliers->syncCatalog();
}

if ($task === 'all' || $task === 'maintenance') {
    $results['maintenance'] = runMaintenance();
}

echo json_encode(['success' => true, 'results' => array_filter($results)]);

function runMaintenance(): array
{
    $logPath = (string) Env::get('LOG_PATH', __DIR__ . '/logs/app.log');
    $rotated = rotateLog($logPath);
    $backup = createBackupZip(__DIR__ . '/storage', __DIR__ . '/backups');

    return [
        'log_rotated' => $rotated,
        'backup_created' => $backup,
    ];
}

function rotateLog(string $path, int $maxSize = 5242880): ?string
{
    if (!file_exists($path)) {
        return null;
    }

    if (filesize($path) < $maxSize) {
        return null;
    }

    $directory = dirname($path);
    if (!is_dir($directory)) {
        return null;
    }

    $timestamp = date('Ymd_His');
    $target = $directory . '/app-' . $timestamp . '.log';
    if (@rename($path, $target)) {
        touch($path);
        return $target;
    }

    return null;
}

function createBackupZip(string $source, string $destinationDir): ?string
{
    if (!is_dir($source)) {
        return null;
    }

    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0775, true);
    }

    $fileName = $destinationDir . '/backup-' . date('Ymd_His') . '.zip';
    $zip = new \ZipArchive();
    if ($zip->open($fileName, \ZipArchive::CREATE) !== true) {
        return null;
    }

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        $filePath = (string) $file;
        $localName = substr($filePath, strlen($source) + 1);
        if (is_dir($filePath)) {
            $zip->addEmptyDir($localName);
        } else {
            $zip->addFile($filePath, $localName);
        }
    }

    $zip->close();

    return $fileName;
}
