<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Entities\Venta;

interface VentaRepositoryInterface
{
    /** @return Venta[] */
    public function all(): array;

    public function find(int $id): ?Venta;

    public function create(Venta $entity): int;

    public function update(Venta $entity): void;

    public function delete(int $id): void;
}
