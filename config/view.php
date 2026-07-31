<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views. Of course
    | the usual Laravel view path has already been registered for you.
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    | NativePHP's BundleExclusions strips `storage/framework` from the packaged
    | app, so the directory does not exist when `package:discover` boots during
    | `native:package`. `realpath()` returns `false` for a missing directory,
    | which makes the Blade compiler throw "Please provide a valid cache path".
    | Fall back to the plain path so it is always a valid string — Laravel then
    | creates the directory on first compile at runtime.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views')) ?: storage_path('framework/views')
    ),

];
