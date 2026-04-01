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
    public function register(string $name, string $email, string $password, string $passwordConfirmation, ?string $phoneNumber = null): array
    {
        return $this->client->post('/auth/register', array_filter([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
            'phone_number' => $phoneNumber,
        ]));
    }

    /**
     * @return array{message: string, requires_otp?: bool, login_token?: string}
     */
    public function verifyOtp(string $code, string $loginToken): array
    {
        return $this->client->post('/auth/verify-otp', [
            'code' => $code,
            'login_token' => $loginToken,
        ]);
    }

    /**
     * @return array{message: string, requires_phone_verification: bool}
     */
    public function submitPhone(string $phoneNumber): array
    {
        return $this->client->post('/auth/submit-phone', [
            'phone_number' => $phoneNumber,
        ]);
    }

    /**
     * @return array{data: array{user: array, token: string}, message: string}
     */
    public function verifyPhone(string $code): array
    {
        return $this->client->post('/auth/verify-phone', [
            'code' => $code,
        ]);
    }

    /**
     * @return array{message: string}
     */
    public function resendVerification(): array
    {
        return $this->client->post('/auth/resend-verification');
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

    /**
     * @return array{requires_otp: bool, login_token: string}
     */
    public function loginPhone(string $phoneNumber): array
    {
        return $this->client->post('/auth/login/phone', [
            'phone_number' => $phoneNumber,
        ]);
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
