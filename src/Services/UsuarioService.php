<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\NotFoundException;
use App\Entities\Usuario;
use App\Repositories\UsuarioRepositoryInterface;

final class UsuarioService
{
    public function __construct(
        private UsuarioRepositoryInterface $repository
    ) {
    }

    public function list(): array
    {
        return $this->repository->all();
    }

    public function get(int $id): Usuario
    {
        return $this->repository->find($id)
            ?? throw new NotFoundException();
    }

    public function validate(Usuario $e): array
    {
        $errors = [];

        if (trim((string) $e->name) === '') {
            $errors['name'] = 'Name es obligatorio.';
        }
        if (mb_strlen((string) $e->name) > 120) {
            $errors['name'] = 'Name admite maximo 120 caracteres.';
        }
        if (trim((string) $e->login) === '') {
            $errors['login'] = 'Login es obligatorio.';
        }
        if (mb_strlen((string) $e->login) > 120) {
            $errors['login'] = 'Login admite maximo 120 caracteres.';
        }
        if (trim((string) $e->password) === '') {
            $errors['password'] = 'Password es obligatorio.';
        }
        if (mb_strlen((string) $e->password) > 255) {
            $errors['password'] = 'Password admite maximo 255 caracteres.';
        }
        if (trim((string) $e->email) === '') {
            $errors['email'] = 'Email es obligatorio.';
        }
        if (mb_strlen((string) $e->email) > 120) {
            $errors['email'] = 'Email admite maximo 120 caracteres.';
        }
        if ((string) $e->email !== '' && !filter_var($e->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email no es un correo valido.';
        }

        return $errors;
    }

    public function create(Usuario $e): int
    {
        return $this->repository->create($e);
    }

    public function update(Usuario $e): void
    {
        $this->repository->update($e);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
