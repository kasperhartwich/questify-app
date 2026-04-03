<?php

namespace App\Services\Api;

use App\Exceptions\Api\ApiAuthenticationException;
use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiNotFoundException;
use App\Exceptions\Api\ApiServerException;
use App\Exceptions\Api\ApiValidationException;
use App\Services\Api\Resources\AuthResource;
use App\Services\Api\Resources\CategoryApiResource;
use App\Services\Api\Resources\GameplayApiResource;
use App\Services\Api\Resources\QuestApiResource;
use App\Services\Api\Resources\SessionApiResource;
use App\Services\Api\Resources\UserApiResource;
use App\Services\TokenStorage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Facades\System;

class QuestifyApiClient
{
    private string $baseUrl;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = System::isMobile()
            ? 'https://questifyapp.net'
            : rtrim(config('services.questify.url'), '/');
        $this->timeout = config('services.questify.timeout', 15);
    }

    public function request(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl.'/api/v1')
            ->timeout($this->timeout)
            ->retry(3, 200, fn (\Exception $e) => $e instanceof ConnectionException, throw: false)
            ->acceptJson()
            ->withHeaders([
                'Accept-Language' => app()->getLocale(),
                'User-Agent' => 'Questify/'.config('nativephp.version', '1.0.0'),
            ]);

        $token = TokenStorage::get();
        if ($token) {
            $request = $request->withToken($token);
        }

        return $request;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    public function handleResponse(Response $response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $body = $response->json() ?? [];
        $message = $body['message'] ?? 'API request failed';

        throw match (true) {
            $response->status() === 401 => new ApiAuthenticationException($message, $body),
            $response->status() === 404 => new ApiNotFoundException($message, $body),
            $response->status() === 422 => new ApiValidationException($message, $body['errors'] ?? [], $body),
            $response->status() >= 500 => new ApiServerException($response->status(), $message, $body),
            default => new ApiException($response->status(), $message, $body),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $url, array $query = []): array
    {
        return $this->handleResponse($this->request()->get($url, $query));
    }

    /**
     * @return array<string, mixed>
     */
    public function post(string $url, array $data = []): array
    {
        return $this->handleResponse($this->request()->post($url, $data));
    }

    /**
     * @return array<string, mixed>
     */
    public function put(string $url, array $data = []): array
    {
        return $this->handleResponse($this->request()->put($url, $data));
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $url): array
    {
        return $this->handleResponse($this->request()->delete($url));
    }

    /**
     * Send a multipart POST request (for file uploads).
     *
     * @param  array<string, mixed>  $fields
     * @param  array<string, array{path: string, name: string}>  $files
     * @return array<string, mixed>
     */
    public function postMultipart(string $url, array $fields = [], array $files = []): array
    {
        $request = $this->request()->asMultipart();

        foreach ($files as $fieldName => $file) {
            $request = $request->attach($fieldName, file_get_contents($file['path']), $file['name']);
        }

        return $this->handleResponse($request->post($url, $fields));
    }

    public function auth(): AuthResource
    {
        return new AuthResource($this);
    }

    public function quests(): QuestApiResource
    {
        return new QuestApiResource($this);
    }

    public function categories(): CategoryApiResource
    {
        return new CategoryApiResource($this);
    }

    public function sessions(): SessionApiResource
    {
        return new SessionApiResource($this);
    }

    public function gameplay(): GameplayApiResource
    {
        return new GameplayApiResource($this);
    }

    public function user(): UserApiResource
    {
        return new UserApiResource($this);
    }
}
