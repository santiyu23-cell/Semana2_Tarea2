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
