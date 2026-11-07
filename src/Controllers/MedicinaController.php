<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Entities\Medicina;
use App\Repositories\MedicinaRepository;
use App\Services\MedicinaService;
use PDOException;

final class MedicinaController extends Controller
{
    private MedicinaService $service;

    public function __construct(?MedicinaService $service = null)
    {
        parent::__construct();

        $this->service = $service
            ?? new MedicinaService(
                new MedicinaRepository(Database::connection())
            );
    }

    public function index(): void
    {
        $this->render(
            'medicina/index',
            ['items' => $this->service->list()],
            'Medicina'
        );
    }

    public function create(): void
    {
        $this->form(new Medicina(), [], 'medicina/store', 'Nuevo Medicina');
    }

    public function store(): void
    {
        $this->requirePost();

        $entity = Medicina::fromArray($this->request->allPost());
        $errors = $this->service->validate($entity);

        if ($errors) {
            $this->form($entity, $errors, 'medicina/store', 'Nuevo Medicina');
            return;
        }

        $this->service->create($entity);

        $this->redirect('medicina', 'Registro creado correctamente.');
    }

    public function edit(?int $id = null): void
    {
        $entity = $this->service->get((int) $id);

        $this->form(
            $entity,
            [],
            'medicina/update/' . $entity->id,
            'Editar Medicina'
        );
    }

    public function update(?int $id = null): void
    {
        $this->requirePost();

        $this->service->get((int) $id);

        $entity = Medicina::fromArray($this->request->allPost());
        $entity->id = (int) $id;

        $errors = $this->service->validate($entity);

        if ($errors) {
            $this->form(
                $entity,
                $errors,
                'medicina/update/' . $id,
                'Editar Medicina'
            );
            return;
        }

        $this->service->update($entity);

        $this->redirect('medicina', 'Registro actualizado correctamente.');
    }

    public function delete(?int $id = null): void
    {
        $this->requirePost();

        $this->service->get((int) $id);

        try {
            $this->service->delete((int) $id);
        } catch (PDOException) {
            $this->redirect(
                'medicina',
                'No se pudo eliminar: el registro esta en uso.',
                'error'
            );
        }

        $this->redirect('medicina', 'Registro eliminado.');
    }

    private function form(
        Medicina $entity,
        array $errors,
        string $action,
        string $title
    ): void {
        $this->render(
            'medicina/form',
            compact('entity', 'errors', 'action'),
            $title
        );
    }
}
