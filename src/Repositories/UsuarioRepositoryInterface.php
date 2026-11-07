<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Entities\Usuario;

interface UsuarioRepositoryInterface
{
    /** @return Usuario[] */
    public function all(): array;

    public function find(int $id): ?Usuario;

    public function create(Usuario $entity): int;

    public function update(Usuario $entity): void;

    public function delete(int $id): void;
}
