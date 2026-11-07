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
