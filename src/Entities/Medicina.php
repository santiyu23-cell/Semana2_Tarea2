<?php
declare(strict_types=1);

namespace App\Entities;

/** Una fila de la tabla medicina. */
final class Medicina
{
    public function __construct(
        public ?int $id = null,
        public string $name = '',
        public ?string $description = null,
        public float $price = 0.0,
        public int $stock = 0,
        public bool $active = false,
        public string $created_at = '',
    ) {
    }

    public static function fromArray(array $d): self
    {
        return new self(
            id: (isset($d['id']) && $d['id'] !== '' ? (int) $d['id'] : null),
            name: (string) ($d['name'] ?? ''),
            description: (isset($d['description']) && $d['description'] !== '' ? (string) $d['description'] : null),
            price: (float) ($d['price'] ?? 0.0),
            stock: (int) ($d['stock'] ?? 0),
            active: in_array(strtolower((string) ($d['active'] ?? '')), ['1', 't', 'true', 'yes', 'on'], true),
            created_at: (string) ($d['created_at'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'active' => (int) $this->active,
            'created_at' => $this->created_at,
        ];
    }
}
