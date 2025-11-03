<?php
declare(strict_types=1);

use App\Core\Database\Connection;
use App\Core\Database\Migrator;

session_start();
require_once __DIR__ . '/../app/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    if (str_starts_with($class, $prefix)) {
        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

$step = (int) ($_GET['step'] ?? 1);
$errors = [];

function checkRequirements(): array
{
    $requirements = [
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'openssl' => extension_loaded('openssl'),
        'mbstring' => extension_loaded('mbstring'),
    ];

    $writableDirs = ['cache', 'logs', 'storage', 'uploads'];
    foreach ($writableDirs as $dir) {
        $path = realpath(__DIR__ . '/../' . $dir) ?: __DIR__ . '/../' . $dir;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $requirements[$dir . '_writable'] = is_writable($path);
    }

    return $requirements;
}

function requirementsPassed(array $requirements): bool
{
    return !in_array(false, $requirements, true);
}

function renderHeader(string $title): void
{
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">';
    echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title></head><body class="bg-light">';
    echo '<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-8">';
}

function renderFooter(): void
{
    echo '</div></div></div></body></html>';
}

function writeEnvFile(array $data): void
{
    $path = __DIR__ . '/../.env.php';
    $export = var_export($data, true);
    file_put_contents($path, "<?php\nreturn {$export};\n");
}

if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['installer']['database'] = [
        'host' => $_POST['db_host'] ?? '127.0.0.1',
        'port' => (int) ($_POST['db_port'] ?? 3306),
        'database' => $_POST['db_name'] ?? '',
        'username' => $_POST['db_user'] ?? '',
        'password' => $_POST['db_pass'] ?? '',
    ];

    if ($_SESSION['installer']['database']['database'] === '') {
        $errors[] = 'Database name is required.';
    }

    if (!$errors) {
        header('Location: index.php?step=3');
        exit;
    }
}

if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminEmail = $_POST['admin_email'] ?? '';
    $adminPassword = $_POST['admin_password'] ?? '';

    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid admin email is required.';
    }

    if (strlen($adminPassword) < 8) {
        $errors[] = 'Admin password must be at least 8 characters.';
    }

    if (!$errors) {
        $db = $_SESSION['installer']['database'] ?? [];
        try {
            $pdo = Connection::make([
                'host' => $db['host'],
                'port' => (int) $db['port'],
                'database' => $db['database'],
                'username' => $db['username'],
                'password' => $db['password'],
                'charset' => 'utf8mb4',
            ]);

            $migrator = new Migrator($pdo);
            $migrator->run();

            $statement = $pdo->prepare('INSERT INTO users (name, email, password, roles, created_at, updated_at) VALUES (:name, :email, :password, :roles, NOW(), NOW()) ON DUPLICATE KEY UPDATE password = VALUES(password), roles = VALUES(roles), updated_at = NOW()');
            $statement->execute([
                'name' => 'Administrator',
                'email' => $adminEmail,
                'password' => password_hash($adminPassword, PASSWORD_BCRYPT),
                'roles' => 'admin',
            ]);

            $_SESSION['installer']['admin'] = ['email' => $adminEmail];
            header('Location: index.php?step=4');
            exit;
        } catch (\Throwable $exception) {
            $errors[] = 'Installation failed: ' . $exception->getMessage();
        }
    }
}

if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = $_SESSION['installer']['database'] ?? [];
    $env = require __DIR__ . '/../env.sample.php';
    $env['APP_ENV'] = 'production';
    $env['APP_DEBUG'] = false;
    $env['DB_HOST'] = $db['host'];
    $env['DB_PORT'] = (string) $db['port'];
    $env['DB_DATABASE'] = $db['database'];
    $env['DB_USERNAME'] = $db['username'];
    $env['DB_PASSWORD'] = $db['password'];
    $env['AES_ENCRYPTION_KEY'] = 'base64:' . base64_encode(random_bytes(32));

    writeEnvFile($env);
    session_destroy();

    renderHeader('Installation Complete');
    echo '<div class="alert alert-success"><h4 class="alert-heading">Success!</h4><p>The application has been installed. Remove the installer directory before going live.</p><a class="btn btn-primary" href="../index.php">Go to application</a></div>';
    renderFooter();
    exit;
}

switch ($step) {
    case 1:
        $requirements = checkRequirements();
        renderHeader('Installation - Requirements');
        echo '<h1 class="mb-4">Server Requirements</h1><ul class="list-group">';
        foreach ($requirements as $name => $status) {
            $badge = $status ? 'bg-success' : 'bg-danger';
            echo '<li class="list-group-item d-flex justify-content-between align-items-center">' . htmlspecialchars($name) . '<span class="badge ' . $badge . '">' . ($status ? 'OK' : 'Missing') . '</span></li>';
        }
        echo '</ul>';
        if (requirementsPassed($requirements)) {
            echo '<a class="btn btn-primary mt-4" href="?step=2">Continue</a>';
        } else {
            echo '<div class="alert alert-warning mt-4">Please resolve the missing requirements before continuing.</div>';
        }
        renderFooter();
        break;

    case 2:
        $db = $_SESSION['installer']['database'] ?? ['host' => '127.0.0.1', 'port' => 3306, 'database' => '', 'username' => '', 'password' => ''];
        renderHeader('Installation - Database');
        echo '<h1 class="mb-4">Database Configuration</h1>';
        foreach ($errors as $error) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        echo '<form method="POST" action="?step=2">';
        echo '<div class="row g-3">';
        echo '<div class="col-md-6"><label class="form-label">Host</label><input type="text" name="db_host" class="form-control" value="' . htmlspecialchars($db['host']) . '" required></div>';
        echo '<div class="col-md-6"><label class="form-label">Port</label><input type="number" name="db_port" class="form-control" value="' . (int) $db['port'] . '" required></div>';
        echo '<div class="col-md-6"><label class="form-label">Database</label><input type="text" name="db_name" class="form-control" value="' . htmlspecialchars($db['database']) . '" required></div>';
        echo '<div class="col-md-6"><label class="form-label">Username</label><input type="text" name="db_user" class="form-control" value="' . htmlspecialchars($db['username']) . '"></div>';
        echo '<div class="col-md-6"><label class="form-label">Password</label><input type="password" name="db_pass" class="form-control" value="' . htmlspecialchars($db['password']) . '"></div>';
        echo '</div><button type="submit" class="btn btn-primary mt-4">Save & Continue</button></form>';
        renderFooter();
        break;

    case 3:
        renderHeader('Installation - Administrator');
        foreach ($errors as $error) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        echo '<h1 class="mb-4">Create Administrator</h1>';
        echo '<form method="POST" action="?step=3">';
        echo '<div class="mb-3"><label class="form-label">Admin Email</label><input type="email" name="admin_email" class="form-control" required></div>';
        echo '<div class="mb-3"><label class="form-label">Admin Password</label><input type="password" name="admin_password" class="form-control" required minlength="8"></div>';
        echo '<button type="submit" class="btn btn-primary">Install</button></form>';
        renderFooter();
        break;

    case 4:
        renderHeader('Installation - Finalize');
        echo '<h1 class="mb-4">Finalize Installation</h1>';
        echo '<p>Click finalize to write the configuration file.</p>';
        echo '<form method="POST" action="?step=4"><button type="submit" class="btn btn-success">Finalize Installation</button></form>';
        renderFooter();
        break;

    default:
        header('Location: index.php');
        exit;
}
