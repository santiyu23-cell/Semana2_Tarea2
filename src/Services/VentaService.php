<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\NotFoundException;
use App\Entities\Venta;
use App\Repositories\VentaRepositoryInterface;

final class VentaService
{
    public function __construct(
        private VentaRepositoryInterface $repository
    ) {
    }

    public function list(): array
    {
        return $this->repository->all();
    }

    public function get(int $id): Venta
    {
        return $this->repository->find($id)
            ?? throw new NotFoundException();
    }

    public function validate(Venta $e): array
    {
        $errors = [];



        return $errors;
    }

    public function create(Venta $e): int
    {
        return $this->repository->create($e);
    }

    public function update(Venta $e): void
    {
        $this->repository->update($e);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
