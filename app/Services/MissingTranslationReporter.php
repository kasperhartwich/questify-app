<?php

namespace App\Services;

use App\Services\Api\QuestifyApiClient;
use Illuminate\Support\Facades\Log;

class MissingTranslationReporter
{
    /** @var array<string, string> */
    private array $pending = [];

    private bool $flushing = false;

    public function __construct(private QuestifyApiClient $apiClient) {}

    /**
     * Report a missing translation key.
     */
    public function report(string $key, string $locale): void
    {
        $cacheKey = "{$locale}:{$key}";

        if (isset($this->pending[$cacheKey])) {
            return;
        }

        $this->pending[$cacheKey] = $locale;
    }

    /**
     * Flush all pending missing translations to the API.
     */
    public function flush(): void
    {
        if (empty($this->pending) || $this->flushing) {
            return;
        }

        $this->flushing = true;

        $keys = [];
        foreach ($this->pending as $cacheKey => $locale) {
            $key = substr($cacheKey, strlen($locale) + 1);
            $keys[] = [
                'key' => $key,
                'locale' => $locale,
            ];
        }

        try {
            $this->apiClient->post('/translations/missing', [
                'keys' => $keys,
            ]);
        } catch (\Throwable $e) {
            Log::debug('Failed to report missing translations', [
                'error' => $e->getMessage(),
                'count' => count($keys),
            ]);
        }

        $this->pending = [];
        $this->flushing = false;
    }
}
