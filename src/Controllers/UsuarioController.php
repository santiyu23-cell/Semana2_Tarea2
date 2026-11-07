<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Entities\Usuario;
use App\Repositories\UsuarioRepository;
use App\Services\UsuarioService;
use PDOException;

final class UsuarioController extends Controller
{
    private UsuarioService $service;

    public function __construct(?UsuarioService $service = null)
    {
        parent::__construct();

        $this->service = $service
            ?? new UsuarioService(
                new UsuarioRepository(Database::connection())
            );
    }

    public function index(): void
    {
        $this->render(
            'usuario/index',
            ['items' => $this->service->list()],
            'Usuario'
        );
    }

    public function create(): void
    {
        $this->form(new Usuario(), [], 'usuario/store', 'Nuevo Usuario');
    }

    public function store(): void
    {
        $this->requirePost();

        $entity = Usuario::fromArray($this->request->allPost());
        $errors = $this->service->validate($entity);

        if ($errors) {
            $this->form($entity, $errors, 'usuario/store', 'Nuevo Usuario');
            return;
        }

        $this->service->create($entity);

        $this->redirect('usuario', 'Registro creado correctamente.');
    }

    public function edit(?int $id = null): void
    {
        $entity = $this->service->get((int) $id);

        $this->form(
            $entity,
            [],
            'usuario/update/' . $entity->id,
            'Editar Usuario'
        );
    }

    public function update(?int $id = null): void
    {
        $this->requirePost();

        $this->service->get((int) $id);

        $entity = Usuario::fromArray($this->request->allPost());
        $entity->id = (int) $id;

        $errors = $this->service->validate($entity);

        if ($errors) {
            $this->form(
                $entity,
                $errors,
                'usuario/update/' . $id,
                'Editar Usuario'
            );
            return;
        }

        $this->service->update($entity);

        $this->redirect('usuario', 'Registro actualizado correctamente.');
    }

    public function delete(?int $id = null): void
    {
        $this->requirePost();

        $this->service->get((int) $id);

        try {
            $this->service->delete((int) $id);
        } catch (PDOException) {
            $this->redirect(
                'usuario',
                'No se pudo eliminar: el registro esta en uso.',
                'error'
            );
        }

        $this->redirect('usuario', 'Registro eliminado.');
    }

    private function form(
        Usuario $entity,
        array $errors,
        string $action,
        string $title
    ): void {
        $this->render(
            'usuario/form',
            compact('entity', 'errors', 'action'),
            $title
        );
    }
}
