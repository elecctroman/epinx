<?php
declare(strict_types=1);

session_start();

use App\Core\Config;
use App\Core\Router;

$container = require __DIR__ . '/bootstrap.php';

$router = new Router($container);
$router->loadRoutes(Config::get('routes', []));

$response = $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
$response->send();
