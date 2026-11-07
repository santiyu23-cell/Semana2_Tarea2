<?php
declare(strict_types=1);

/**
 * PASO 1 - CREAR PROYECTO
 *
 * Uso:
 *   php 1_crear_proyecto.php
 *
 * Requiere:
 *   PHP 8.1+
 *   PostgreSQL
 *   Extensiones PHP: PDO y pdo_pgsql
 *
 * Nunca sobrescribe archivos existentes.
 * Por seguridad, este generador solo se ejecuta por consola.
 */

interface FileSource
{
    /** @return array<string,string> ruta relativa => contenido */
    public function files(): array;
}

final class FileWriter
{
    public function __construct(private string $root) {}

    public function write(string $relativePath, string $content): string
    {
        $full = $this->root . '/' . $relativePath;

        if (!is_dir(dirname($full))) {
            if (!mkdir(dirname($full), 0775, true) && !is_dir(dirname($full))) {
                throw new RuntimeException("No se pudo crear el directorio: $relativePath");
            }
        }

        if (file_exists($full)) {
            return "= ya existe : $relativePath";
        }

        if (file_put_contents($full, $content) === false) {
            throw new RuntimeException("No se pudo escribir el archivo: $relativePath");
        }

        return "+ creado     : $relativePath";
    }
}

final class ProjectBuilder
{
    public function __construct(
        private FileSource $source,
        private FileWriter $writer
    ) {}

    /** @return string[] */
    public function build(): array
    {
        $log = [];

        foreach ($this->source->files() as $path => $content) {
            $log[] = $this->writer->write($path, $content);
        }

        return $log;
    }
}

final class BaseProjectFiles implements FileSource
{
    public function files(): array
    {
        return [
            '.htaccess'                                 => $this->rootHtaccess(),
            '.gitignore'                                => $this->gitignore(),
            '.env'                                      => $this->env(),
            '.env.example'                              => $this->envExample(),
            'README.md'                                 => $this->readme(),
            'bin/migrate.php'                           => $this->migrate(),
            'database/migrations/.gitkeep'              => '',
            'database/migrations/001_create_tables.sql' => $this->sql(),
            'public/.htaccess'                          => $this->publicHtaccess(),
            'public/index.php'                          => $this->index(),
            'src/bootstrap.php'                         => $this->bootstrap(),
            'src/Core/helpers.php'                      => $this->helpers(),
            'src/Core/Env.php'                          => $this->coreEnv(),
            'src/Core/Database.php'                     => $this->coreDatabase(),
            'src/Core/Request.php'                      => $this->coreRequest(),
            'src/Core/Router.php'                       => $this->coreRouter(),
            'src/Core/View.php'                         => $this->coreView(),
            'src/Core/Flash.php'                        => $this->coreFlash(),
            'src/Core/Controller.php'                   => $this->coreController(),
            'src/Core/NotFoundException.php'            => $this->coreNotFound(),
            'src/Controllers/HomeController.php'        => $this->homeController(),
            'src/Entities/.gitkeep'                     => '',
            'src/Repositories/.gitkeep'                 => '',
            'src/Services/.gitkeep'                     => '',
            'src/Views/layout.php'                      => $this->viewLayout(),
            'src/Views/home.php'                        => $this->viewHome(),
            'src/Views/errors/404.php'                  => $this->view404(),
            'src/Views/errors/500.php'                  => $this->view500(),
        ];
    }

    private function rootHtaccess(): string
    {
        return <<<'EOT'
RewriteEngine On

# Bloquear archivos ocultos y configuraciones
RewriteRule (^|/)\.env($|/) - [F,L]
RewriteRule (^|/)\.env\. - [F,L]
RewriteRule ^\.git - [F,L]
RewriteRule ^\.env\.example$ - [F,L]

# Bloquear archivos internos
RewriteRule ^(src|database|bin)(/|$) - [F,L]

# Los generadores solo deben ejecutarse por consola
RewriteRule ^(1_crear_proyecto|2_generar_crud)\.php$ - [F,L]

# Evitar acceso directo a archivos de configuración
<FilesMatch "^(\.env|\.env\.example|\.gitignore|README\.md)$">
    Require all denied
</FilesMatch>

# Todo lo demás se sirve desde /public
RewriteRule ^$ public/ [L]
RewriteRule ^(?!public/)(.*)$ public/$1 [L]

EOT;
    }

    private function gitignore(): string
    {
        return <<<'EOT'
.env
.env.*
!.env.example
/vendor/
/.idea/
/.vscode/
.DS_Store
Thumbs.db
EOT;
    }

    private function publicHtaccess(): string
    {
        return <<<'EOT'
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]

EOT;
    }

    private function env(): string
    {
        return <<<'EOT'
APP_NAME="CRUD PHP"
APP_DEBUG=false

DB_HOST=127.0.0.1
DB_PORT=5433
DB_NAME=crudphp
DB_USER=postgres
DB_PASS=

EOT;
    }

    private function envExample(): string
    {
        return <<<'EOT'
APP_NAME="CRUD PHP"
APP_DEBUG=false

DB_HOST=127.0.0.1
DB_PORT=5433
DB_NAME=crudphp
DB_USER=postgres
DB_PASS=

EOT;
    }

    private function readme(): string
    {
        return <<<'EOT'
# CRUD PHP

Proyecto base en PHP 8.1+ con PDO, PostgreSQL y arquitectura simple basada en SOLID.

## Requisitos

- PHP 8.1 o superior.
- PostgreSQL.
- Extensiones PHP `pdo` y `pdo_pgsql`.
- Apache con `mod_rewrite` habilitado.

## Instalación

1. Configura los datos de conexión en `.env`.
2. Ejecuta las migraciones:

   `php bin/migrate.php`

3. Genera un CRUD para una tabla:

   `php 2_generar_crud.php products`

4. Abre el proyecto en el navegador:

   `http://localhost/nombre_carpeta/`

## Capas

- `Controllers`: reciben las peticiones HTTP.
- `Services`: validaciones y reglas de negocio.
- `Repositories`: acceso a datos mediante PDO.
- `Entities`: objetos que representan los registros.
- `Views`: HTML.
- `Core`: utilidades generales.

## Seguridad

- No subir `.env` a Git.
- No publicar archivos internos.
- Mantener `APP_DEBUG=false` en producción.
- Los generadores se ejecutan únicamente por consola.
- En producción, configurar `public/` como directorio público del servidor.

## Rutas

Por convención:

`/recurso/accion/id`

Ejemplo:

`/products/edit/3`

EOT;
    }

    private function sql(): string
    {
        return <<<'EOT'
CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    price NUMERIC(10,2) NOT NULL DEFAULT 0,
    stock INTEGER NOT NULL DEFAULT 0,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

EOT;
    }

    private function migrate(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

/**
 * Ejecuta las migraciones pendientes.
 *
 * Uso:
 *   php bin/migrate.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso no permitido.');
}

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Env;

try {
    $host = Env::get('DB_HOST', '127.0.0.1');
    $port = Env::get('DB_PORT', '5432');
    $name = Env::get('DB_NAME', 'crudphp');
    $user = Env::get('DB_USER', 'postgres');
    $pass = Env::get('DB_PASS', '');

    // Validar el nombre para utilizarlo como identificador SQL.
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
        throw new RuntimeException('El nombre de la base de datos no es válido.');
    }

    // Conexión a la base administrativa de PostgreSQL.
    $adminDsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=postgres',
        $host,
        $port
    );

    $admin = new PDO($adminDsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Comprobar si la base de datos ya existe.
    $check = $admin->prepare(
        'SELECT 1 FROM pg_database WHERE datname = :name'
    );

    $check->execute(['name' => $name]);

    if (!$check->fetchColumn()) {
        $admin->exec('CREATE DATABASE "' . $name . '"');
        echo "Base de datos creada: $name" . PHP_EOL;
    } else {
        echo "Base de datos existente: $name" . PHP_EOL;
    }

    // Conexión a la base del proyecto.
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $host,
        $port,
        $name
    );

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Tabla para controlar las migraciones ejecutadas.
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS schema_migrations (
    filename VARCHAR(255) PRIMARY KEY,
    applied_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
)
SQL);

    $directory = dirname(__DIR__) . '/database/migrations';
    $files = glob($directory . '/*.sql') ?: [];

    sort($files, SORT_STRING);

    foreach ($files as $file) {
        $filename = basename($file);

        $check = $pdo->prepare(
            'SELECT 1 FROM schema_migrations WHERE filename = :filename'
        );

        $check->execute(['filename' => $filename]);

        if ($check->fetchColumn()) {
            echo "SKIP: $filename" . PHP_EOL;
            continue;
        }

        $sql = file_get_contents($file);

        if ($sql === false || trim($sql) === '') {
            echo "SKIP: $filename (vacío)" . PHP_EOL;
            continue;
        }

        $pdo->beginTransaction();

        try {
            $pdo->exec($sql);

            $insert = $pdo->prepare(
                'INSERT INTO schema_migrations (filename) VALUES (:filename)'
            );

            $insert->execute(['filename' => $filename]);

            $pdo->commit();

            echo "OK: $filename" . PHP_EOL;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    echo PHP_EOL . "Migraciones finalizadas." . PHP_EOL;

} catch (Throwable $e) {
    // No mostrar credenciales ni detalles internos de conexión.
    error_log((string) $e);
    fwrite(STDERR, 'Error al ejecutar las migraciones. Revisa la configuración y los registros.' . PHP_EOL);
    exit(1);
}

EOT;
    }

    private function index(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\NotFoundException;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;

session_start();

try {
    (new Router())->dispatch(new Request());

} catch (NotFoundException) {
    http_response_code(404);

    echo View::render('errors/404', [
        'title' => 'No encontrado'
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    // El detalle técnico queda en el registro del servidor.
    error_log((string) $e);

    echo View::render('errors/500', [
        'title' => 'Error',
        'message' => 'Ocurrió un error inesperado.'
    ]);
}

EOT;
    }

    private function bootstrap(): string
    {
        return <<<'EOT'
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

EOT;
    }

    private function helpers(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

use App\Core\Request;

/** Escapa texto para HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Construye una URL de la app. */
function url(string $path = ''): string
{
    return Request::baseUrl() . '/' . ltrim($path, '/');
}

EOT;
    }

    private function coreEnv(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Lee las variables del archivo .env.
 *
 * No imprime ni expone los valores.
 */
final class Env
{
    private static array $vars = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            $key = trim($key);
            $value = trim($value);

            if ($key === '') {
                continue;
            }

            self::$vars[$key] = trim($value, "\"'");
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$vars[$key] ?? $default;
    }
}

EOT;
    }

    private function coreDatabase(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Administra una única conexión PDO a PostgreSQL.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $host = Env::get('DB_HOST', '127.0.0.1');
            $port = Env::get('DB_PORT', '5432');
            $name = Env::get('DB_NAME', 'crudphp');
            $user = Env::get('DB_USER', 'postgres');
            $pass = Env::get('DB_PASS', '');

            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $host,
                $port,
                $name
            );

            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$pdo;
    }
}

EOT;
    }

    private function coreRequest(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

namespace App\Core;

/** Datos de la petición HTTP. */
final class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function allPost(): array
    {
        return $_POST;
    }

    /** /products/edit/3 -> ['products', 'edit', '3'] */
    public function segments(): array
    {
        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $base = self::baseUrl();

        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        return array_values(
            array_filter(explode('/', $path), fn(string $s) => $s !== '')
        );
    }

    /** Carpeta donde vive la app dentro del servidor. */
    public static function baseUrl(): string
    {
        $script = rtrim(
            str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')),
            '/'
        );

        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        if ($script === '' || $path === $script || str_starts_with($path, $script . '/')) {
            return $script;
        }

        return rtrim(str_replace('\\', '/', dirname($script)), '/');
    }
}

EOT;
    }

    private function coreRouter(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

namespace App\Core;

use ReflectionMethod;

/**
 * Enrutador por convención:
 * /recurso/accion/id -> RecursoController::accion($id)
 */
final class Router
{
    public function dispatch(Request $request): void
    {
        [$resource, $action, $id] = $request->segments() + ['home', 'index', null];

        if (
            !preg_match('/^[a-z][a-z0-9_]*$/', (string) $resource)
            || !preg_match('/^[a-z][a-zA-Z0-9]*$/', (string) $action)
        ) {
            throw new NotFoundException();
        }

        $class = 'App\\Controllers\\'
            . str_replace(' ', '', ucwords(str_replace('_', ' ', $resource)))
            . 'Controller';

        if (
            !class_exists($class)
            || !is_subclass_of($class, Controller::class)
            || !method_exists($class, $action)
        ) {
            throw new NotFoundException();
        }

        $method = new ReflectionMethod($class, $action);

        if (
            !$method->isPublic()
            || $method->isStatic()
            || $method->getDeclaringClass()->getName() === Controller::class
        ) {
            throw new NotFoundException();
        }

        $controller = new $class();
        $controller->$action($id !== null ? (int) $id : null);
    }
}

EOT;
    }

    private function coreView(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Renderiza una vista dentro del layout. */
final class View
{
    public static function render(string $view, array $data = []): string
    {
        $content = self::capture($view, $data);

        return self::capture('layout', $data + [
            'content' => $content
        ]);
    }

    private static function capture(string $view, array $data): string
    {
        $file = dirname(__DIR__) . '/Views/' . $view . '.php';

        if (!is_file($file)) {
            throw new RuntimeException("Vista no encontrada: $view");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;

        return (string) ob_get_clean();
    }
}

EOT;
    }

    private function coreFlash(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

namespace App\Core;

/** Mensajes de una sola vez. */
final class Flash
{
    public static function set(string $message, string $type = 'ok'): void
    {
        $_SESSION['flash'] = [
            'm' => $message,
            't' => $type
        ];
    }

    public static function pull(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        return $flash;
    }
}

EOT;
    }

    private function coreController(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

namespace App\Core;

/** Base de todos los controladores. */
abstract class Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    protected function render(
        string $view,
        array $data = [],
        string $title = ''
    ): void {
        echo View::render($view, $data + [
            'title' => $title
        ]);
    }

    protected function redirect(
        string $path,
        ?string $message = null,
        string $type = 'ok'
    ): never {
        if ($message !== null) {
            Flash::set($message, $type);
        }

        header('Location: ' . url($path));
        exit;
    }

    protected function requirePost(): void
    {
        if ($this->request->method() !== 'POST') {
            throw new NotFoundException();
        }
    }
}

EOT;
    }

    private function coreNotFound(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class NotFoundException extends RuntimeException
{
}

EOT;
    }

    private function homeController(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class HomeController extends Controller
{
    public function index(): void
    {
        $this->render('home', [], 'Inicio');
    }
}

EOT;
    }

    private function viewLayout(): string
    {
        return <<<'EOT'
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($title ?? 'App') ?> - <?= e(\App\Core\Env::get('APP_NAME', 'App')) ?></title>

    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, Arial, sans-serif; background: #f4f5f7; color: #222; }
        nav { background: #1f2937; padding: 12px 24px; }
        nav a { color: #fff; margin-right: 18px; text-decoration: none; }
        nav a:hover { text-decoration: underline; }
        main { max-width: 960px; margin: 24px auto; background: #fff; padding: 24px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; }
        .btn, button.btn { background: #2563eb; color: #fff; border: 0; padding: 8px 14px; border-radius: 6px; text-decoration: none; cursor: pointer; font-size: 14px; }
        .actions { display: flex; gap: 12px; align-items: center; }
        .actions form { margin: 0; }
        button.link { background: none; border: 0; color: #2563eb; cursor: pointer; padding: 0; font-size: 14px; }
        button.danger { color: #dc2626; }
        .field { margin-bottom: 14px; display: flex; flex-direction: column; gap: 4px; }
        .field input[type=checkbox] { width: 18px; height: 18px; }
        input, select, textarea { padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        .error { color: #dc2626; }
        .buttons { display: flex; gap: 16px; align-items: center; margin-top: 20px; }
        .flash { padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; }
        .flash.ok { background: #dcfce7; }
        .flash.error { background: #fee2e2; }
    </style>
</head>
<body>
<nav>
    <a href="<?= url('') ?>">Inicio</a>
    <!-- MENU -->
</nav>

<main>
    <?php if ($flash = \App\Core\Flash::pull()): ?>
        <div class="flash <?= e($flash['t']) ?>">
            <?= e($flash['m']) ?>
        </div>
    <?php endif; ?>

    <?= $content ?>
</main>
</body>
</html>

EOT;
    }

    private function viewHome(): string
    {
        return <<<'EOT'
<h1>Bienvenido</h1>

<p>El proyecto base está funcionando.</p>

<p>Para generar un CRUD de una tabla, ejecuta desde la consola:</p>

<pre>php 2_generar_crud.php nombre_de_la_tabla</pre>

EOT;
    }

    private function view404(): string
    {
        return <<<'EOT'
<h1>404</h1>
<p>La página que buscas no existe.</p>
<p><a href="<?= url('') ?>">Volver al inicio</a></p>

EOT;
    }

    private function view500(): string
    {
        return <<<'EOT'
<h1>Error</h1>
<p><?= e($message ?? 'Error inesperado.') ?></p>
<p><a href="<?= url('') ?>">Volver al inicio</a></p>

EOT;
    }
}

// ---------------------------------------------------------------------------
// EJECUCIÓN DEL GENERADOR
// ---------------------------------------------------------------------------

// Este archivo solo se puede ejecutar desde la consola.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este generador solo puede ejecutarse por consola.');
}

$root = __DIR__;

$builder = new ProjectBuilder(
    new BaseProjectFiles(),
    new FileWriter($root)
);

echo "Creando proyecto en: $root" . PHP_EOL . PHP_EOL;

try {
    echo implode(PHP_EOL, $builder->build()) . PHP_EOL . PHP_EOL;

    echo "Proyecto generado." . PHP_EOL . PHP_EOL;

    echo "Siguientes pasos:" . PHP_EOL;
    echo "  1) Revisa la configuración de .env" . PHP_EOL;
    echo "  2) Ejecuta: php bin/migrate.php" . PHP_EOL;
    echo "  3) Ejecuta: php 2_generar_crud.php products" . PHP_EOL;

} catch (Throwable $e) {
    error_log((string) $e);

    fwrite(
        STDERR,
        'No se pudo generar el proyecto. Revisa los permisos y los registros.' . PHP_EOL
    );

    exit(1);
}