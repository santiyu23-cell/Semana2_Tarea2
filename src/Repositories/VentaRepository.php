<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Entities\Venta;
use PDO;

final class VentaRepository implements VentaRepositoryInterface
{
    private const WRITABLE = ['user_id', 'total'];

    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $rows = $this->pdo
            ->query('SELECT * FROM "venta" ORDER BY "id" DESC')
            ->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            [Venta::class, 'fromArray'],
            $rows
        );
    }

    public function find(int $id): ?Venta
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM "venta" WHERE "id" = :id'
        );

        $st->execute(['id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ? Venta::fromArray($row) : null;
    }

    public function create(Venta $entity): int
    {
        $sql = '
            INSERT INTO "venta" ("user_id", "total")
            VALUES (:user_id, :total)
            RETURNING "id"
        ';

        $st = $this->pdo->prepare($sql);
        $st->execute($this->data($entity));

        return (int) $st->fetchColumn();
    }

    public function update(Venta $entity): void
    {
        $sql = '
            UPDATE "venta"
            SET "user_id" = :user_id, "total" = :total
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
            'DELETE FROM "venta" WHERE "id" = :id'
        );

        $st->execute(['id' => $id]);
    }

    private function data(Venta $entity): array
    {
        return array_intersect_key(
            $entity->toArray(),
            array_flip(self::WRITABLE)
        );
    }
}
