<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

class ApiTokenUser implements Authenticatable
{
    public int $id;

    public string $name;

    public string $email;

    public ?string $avatarUrl;

    public string $locale;

    public ?string $createdAt;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->avatarUrl = $data['avatar_url'] ?? null;
        $this->locale = $data['locale'] ?? 'en';
        $this->createdAt = $data['created_at'] ?? null;
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->id;
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // Not supported for API token auth
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatarUrl,
            'locale' => $this->locale,
            'created_at' => $this->createdAt,
        ];
    }
}
