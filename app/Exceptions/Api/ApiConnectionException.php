<?php

namespace App\Exceptions\Api;

/**
 * The request never got an answer: no signal, or the retries ran out. Raised
 * as an ApiException so every screen's existing handler shows a retryable
 * message instead of a failed Livewire request.
 */
class ApiConnectionException extends ApiException
{
    public function __construct(string $message = '', array $body = [])
    {
        parent::__construct(0, $message !== '' ? $message : __('general.no_connection'), $body);
    }
}
