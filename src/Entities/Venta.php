<?php
declare(strict_types=1);

namespace App\Entities;

/** Una fila de la tabla venta. */
final class Venta
{
    public function __construct(
        public ?int $id = null,
        public int $user_id = 0,
        public float $total = 0.0,
        public string $created_at = '',
    ) {
    }

    public static function fromArray(array $d): self
    {
        return new self(
            id: (isset($d['id']) && $d['id'] !== '' ? (int) $d['id'] : null),
            user_id: (int) ($d['user_id'] ?? 0),
            total: (float) ($d['total'] ?? 0.0),
            created_at: (string) ($d['created_at'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'total' => $this->total,
            'created_at' => $this->created_at,
        ];
    }
}
