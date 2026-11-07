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
