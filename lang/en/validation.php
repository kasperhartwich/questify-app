<?php

return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute field must be a valid email address.',
    'min' => [
        'string' => 'The :attribute field must be at least :min characters.',
        'numeric' => 'The :attribute field must be at least :min.',
    ],
    'max' => [
        'string' => 'The :attribute field must not be greater than :max characters.',
        'numeric' => 'The :attribute field must not be greater than :max.',
    ],
    'unique' => 'The :attribute has already been taken.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'string' => 'The :attribute field must be a string.',
    'integer' => 'The :attribute field must be an integer.',
    'numeric' => 'The :attribute field must be a number.',
    'boolean' => 'The :attribute field must be true or false.',
    'array' => 'The :attribute field must be an array.',
    'in' => 'The selected :attribute is invalid.',
    'exists' => 'The selected :attribute is invalid.',
    'url' => 'The :attribute field must be a valid URL.',
    'image' => 'The :attribute field must be an image.',
    'between' => [
        'numeric' => 'The :attribute field must be between :min and :max.',
        'string' => 'The :attribute field must be between :min and :max characters.',
    ],
];
