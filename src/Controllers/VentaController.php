<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Entities\Venta;
use App\Repositories\VentaRepository;
use App\Services\VentaService;
use PDOException;

final class VentaController extends Controller
{
    private VentaService $service;

    public function __construct(?VentaService $service = null)
    {
        parent::__construct();

        $this->service = $service
            ?? new VentaService(
                new VentaRepository(Database::connection())
            );
    }

    public function index(): void
    {
        $this->render(
            'venta/index',
            ['items' => $this->service->list()],
            'Venta'
        );
    }

    public function create(): void
    {
        $this->form(new Venta(), [], 'venta/store', 'Nuevo Venta');
    }

    public function store(): void
    {
        $this->requirePost();

        $entity = Venta::fromArray($this->request->allPost());
        $errors = $this->service->validate($entity);

        if ($errors) {
            $this->form($entity, $errors, 'venta/store', 'Nuevo Venta');
            return;
        }

        $this->service->create($entity);

        $this->redirect('venta', 'Registro creado correctamente.');
    }

    public function edit(?int $id = null): void
    {
        $entity = $this->service->get((int) $id);

        $this->form(
            $entity,
            [],
            'venta/update/' . $entity->id,
            'Editar Venta'
        );
    }

    public function update(?int $id = null): void
    {
        $this->requirePost();

        $this->service->get((int) $id);

        $entity = Venta::fromArray($this->request->allPost());
        $entity->id = (int) $id;

        $errors = $this->service->validate($entity);

        if ($errors) {
            $this->form(
                $entity,
                $errors,
                'venta/update/' . $id,
                'Editar Venta'
            );
            return;
        }

        $this->service->update($entity);

        $this->redirect('venta', 'Registro actualizado correctamente.');
    }

    public function delete(?int $id = null): void
    {
        $this->requirePost();

        $this->service->get((int) $id);

        try {
            $this->service->delete((int) $id);
        } catch (PDOException) {
            $this->redirect(
                'venta',
                'No se pudo eliminar: el registro esta en uso.',
                'error'
            );
        }

        $this->redirect('venta', 'Registro eliminado.');
    }

    private function form(
        Venta $entity,
        array $errors,
        string $action,
        string $title
    ): void {
        $this->render(
            'venta/form',
            compact('entity', 'errors', 'action'),
            $title
        );
    }
}
