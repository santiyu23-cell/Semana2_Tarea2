<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Entities\Medicina;

interface MedicinaRepositoryInterface
{
    /** @return Medicina[] */
    public function all(): array;

    public function find(int $id): ?Medicina;

    public function create(Medicina $entity): int;

    public function update(Medicina $entity): void;

    public function delete(int $id): void;
}
