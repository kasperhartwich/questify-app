<?php

namespace App\Exceptions\Api;

use RuntimeException;

class ApiException extends RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        string $message = 'API request failed',
        public readonly array $body = [],
    ) {
        parent::__construct($message, $statusCode);
    }
}
