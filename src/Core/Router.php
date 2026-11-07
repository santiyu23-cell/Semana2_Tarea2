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
