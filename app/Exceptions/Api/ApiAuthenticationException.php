<?php

namespace App\Exceptions\Api;

class ApiAuthenticationException extends ApiException
{
    public function __construct(string $message = 'Unauthenticated.', array $body = [])
    {
        parent::__construct(401, $message, $body);
    }
}
