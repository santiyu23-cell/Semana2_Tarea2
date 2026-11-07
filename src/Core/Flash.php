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
