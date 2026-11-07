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
