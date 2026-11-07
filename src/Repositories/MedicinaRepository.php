<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Entities\Medicina;
use PDO;

final class MedicinaRepository implements MedicinaRepositoryInterface
{
    private const WRITABLE = ['name', 'description', 'price', 'stock', 'active'];

    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $rows = $this->pdo
            ->query('SELECT * FROM "medicina" ORDER BY "id" DESC')
            ->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            [Medicina::class, 'fromArray'],
            $rows
        );
    }

    public function find(int $id): ?Medicina
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM "medicina" WHERE "id" = :id'
        );

        $st->execute(['id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ? Medicina::fromArray($row) : null;
    }

    public function create(Medicina $entity): int
    {
        $sql = '
            INSERT INTO "medicina" ("name", "description", "price", "stock", "active")
            VALUES (:name, :description, :price, :stock, :active)
            RETURNING "id"
        ';

        $st = $this->pdo->prepare($sql);
        $st->execute($this->data($entity));

        return (int) $st->fetchColumn();
    }

    public function update(Medicina $entity): void
    {
        $sql = '
            UPDATE "medicina"
            SET "name" = :name, "description" = :description, "price" = :price, "stock" = :stock, "active" = :active
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
            'DELETE FROM "medicina" WHERE "id" = :id'
        );

        $st->execute(['id' => $id]);
    }

    private function data(Medicina $entity): array
    {
        return array_intersect_key(
            $entity->toArray(),
            array_flip(self::WRITABLE)
        );
    }
}
