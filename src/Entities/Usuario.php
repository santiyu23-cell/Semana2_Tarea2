<?php
declare(strict_types=1);

namespace App\Entities;

/** Una fila de la tabla usuario. */
final class Usuario
{
    public function __construct(
        public ?int $id = null,
        public string $name = '',
        public string $login = '',
        public string $password = '',
        public string $email = '',
        public string $created_at = '',
    ) {
    }

    public static function fromArray(array $d): self
    {
        return new self(
            id: (isset($d['id']) && $d['id'] !== '' ? (int) $d['id'] : null),
            name: (string) ($d['name'] ?? ''),
            login: (string) ($d['login'] ?? ''),
            password: (string) ($d['password'] ?? ''),
            email: (string) ($d['email'] ?? ''),
            created_at: (string) ($d['created_at'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'login' => $this->login,
            'password' => $this->password,
            'email' => $this->email,
            'created_at' => $this->created_at,
        ];
    }
}
