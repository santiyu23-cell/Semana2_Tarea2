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
