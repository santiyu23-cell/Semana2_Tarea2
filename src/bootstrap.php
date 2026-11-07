<?php
declare(strict_types=1);

// Autoload simple: App\Core\Router -> src/Core/Router.php
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $file = __DIR__ . '/'
        . str_replace('\\', '/', substr($class, strlen($prefix)))
        . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/Core/helpers.php';

App\Core\Env::load(dirname(__DIR__) . '/.env');

// Nunca mostrar errores internos directamente en el navegador.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
