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
