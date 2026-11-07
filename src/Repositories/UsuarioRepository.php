<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Entities\Usuario;
use PDO;

final class UsuarioRepository implements UsuarioRepositoryInterface
{
    private const WRITABLE = ['name', 'login', 'password', 'email'];

    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $rows = $this->pdo
            ->query('SELECT * FROM "usuario" ORDER BY "id" DESC')
            ->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            [Usuario::class, 'fromArray'],
            $rows
        );
    }

    public function find(int $id): ?Usuario
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM "usuario" WHERE "id" = :id'
        );

        $st->execute(['id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ? Usuario::fromArray($row) : null;
    }

    public function create(Usuario $entity): int
    {
        $sql = '
            INSERT INTO "usuario" ("name", "login", "password", "email")
            VALUES (:name, :login, :password, :email)
            RETURNING "id"
        ';

        $st = $this->pdo->prepare($sql);
        $st->execute($this->data($entity));

        return (int) $st->fetchColumn();
    }

    public function update(Usuario $entity): void
    {
        $sql = '
            UPDATE "usuario"
            SET "name" = :name, "login" = :login, "password" = :password, "email" = :email
            WHERE "id" = :__pk
        ';

        $st = $this->pdo->prepare($sql);

        $st->execute(
            $this->data($entity) + [
                '__pk' => $entity->id
            ]
        );
    }

    public function delete(int $id): void
    {
        $st = $this->pdo->prepare(
            'DELETE FROM "usuario" WHERE "id" = :id'
        );

        $st->execute(['id' => $id]);
    }

    private function data(Usuario $entity): array
    {
        return array_intersect_key(
            $entity->toArray(),
            array_flip(self::WRITABLE)
        );
    }
}
