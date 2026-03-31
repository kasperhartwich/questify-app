<?php

namespace App\Exceptions\Api;

class ApiServerException extends ApiException
{
    public function __construct(int $statusCode = 500, string $message = 'Server error.', array $body = [])
    {
        parent::__construct($statusCode, $message, $body);
    }
}
