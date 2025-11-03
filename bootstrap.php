<?php
declare(strict_types=1);

require_once __DIR__ . '/app/helpers.php';

if (!class_exists('App\\Core\\Container')) {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        $baseDir = __DIR__ . '/app/';

        if (str_starts_with($class, $prefix)) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    });
}

use App\Core\Config;
use App\Core\Container;
use App\Core\Database\Connection;
use App\Core\Env;
use App\Core\Support\FileStorage;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\CartService;
use App\Services\CatalogService;
use App\Services\DeliveryService;
use App\Services\Mailer;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\QueueService;
use App\Services\ReCaptchaService;
use App\Services\SupplierService;
use App\Services\WebhookService;
use App\Services\QueueWorker;

Env::load(__DIR__ . '/.env.php');
Config::load(__DIR__ . '/config');

$container = new Container();
$container->set(Connection::class, static function () {
    return Connection::make([
        'host' => Env::get('DB_HOST'),
        'port' => (int) Env::get('DB_PORT', 3306),
        'database' => Env::get('DB_DATABASE'),
        'username' => Env::get('DB_USERNAME'),
        'password' => Env::get('DB_PASSWORD'),
        'charset' => 'utf8mb4',
    ]);
});

$container->set(AuthService::class, static function () use ($container) {
    return new AuthService($container->get(Connection::class));
});

$container->set(FileStorage::class, static function () {
    return new FileStorage(__DIR__ . '/storage');
});

$container->set(Mailer::class, static function () {
    return new Mailer();
});

$container->set(CatalogService::class, static function () use ($container) {
    return new CatalogService($container->get(Connection::class));
});

$container->set(CartService::class, static function () {
    return new CartService();
});

$container->set(QueueService::class, static function () use ($container) {
    return new QueueService($container->get(Connection::class));
});

$container->set(SupplierService::class, static function () use ($container) {
    return new SupplierService(
        $container->get(Connection::class),
        $container->get(QueueService::class)
    );
});

$container->set(AuditService::class, static function () use ($container) {
    return new AuditService($container->get(Connection::class));
});

$container->set(OrderService::class, static function () use ($container) {
    return new OrderService($container->get(Connection::class));
});

$container->set(DeliveryService::class, static function () use ($container) {
    return new DeliveryService(
        $container->get(Connection::class),
        $container->get(FileStorage::class),
        $container->get(Mailer::class),
        $container->get(SupplierService::class),
        $container->get(AuditService::class)
    );
});

$container->set(PaymentService::class, static function () {
    return new PaymentService();
});

$container->set(ReCaptchaService::class, static function () {
    return new ReCaptchaService();
});

$container->set(WebhookService::class, static function () use ($container) {
    return new WebhookService(
        $container->get(Connection::class),
        $container->get(OrderService::class),
        $container->get(DeliveryService::class),
        $container->get(QueueService::class)
    );
});

$container->set(QueueWorker::class, static function () use ($container) {
    return new QueueWorker(
        $container->get(QueueService::class),
        $container->get(SupplierService::class),
        $container->get(WebhookService::class),
        $container->get(DeliveryService::class)
    );
});

return $container;
