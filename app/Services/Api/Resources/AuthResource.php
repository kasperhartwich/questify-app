<?php

namespace App\Services\Api\Resources;

use App\Services\Api\QuestifyApiClient;

class AuthResource
{
    public function __construct(private QuestifyApiClient $client) {}

    /**
     * @return array{data: array{user: array, token: string}, message: string}
     */
    public function login(string $email, string $password): array
    {
        return $this->client->post('/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    /**
     * @return array{data: array{user: array, token: string}, message: string}
     */
    public function register(string $name, string $email, string $password, string $passwordConfirmation): array
    {
        return $this->client->post('/auth/register', [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ]);
    }

    public function logout(): void
    {
        $this->client->post('/auth/logout');
    }

    /**
     * @return array{data: array}
     */
    public function me(): array
    {
        return $this->client->get('/auth/me');
    }

    public function forgotPassword(string $email): array
    {
        return $this->client->post('/auth/forgot-password', ['email' => $email]);
    }

    public function resetPassword(string $token, string $email, string $password, string $passwordConfirmation): array
    {
        return $this->client->post('/auth/reset-password', [
            'token' => $token,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ]);
    }

    public function unlinkSocial(string $provider): array
    {
        return $this->client->delete("/auth/social/{$provider}");
    }
}
