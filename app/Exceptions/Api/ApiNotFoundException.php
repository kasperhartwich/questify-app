<?php

namespace App\Exceptions\Api;

class ApiNotFoundException extends ApiException
{
    public function __construct(string $message = 'Not found.', array $body = [])
    {
        parent::__construct(404, $message, $body);
    }
}
