<?php

namespace App\Exceptions\Api;

class ApiValidationException extends ApiException
{
    /** @var array<string, array<int, string>> */
    public readonly array $errors;

    /** @param array<string, array<int, string>> $errors */
    public function __construct(string $message = 'Validation failed.', array $errors = [], array $body = [])
    {
        $this->errors = $errors;
        parent::__construct(422, $message, $body);
    }
}
