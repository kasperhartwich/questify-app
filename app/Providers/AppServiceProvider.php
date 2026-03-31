<?php

namespace App\Providers;

use App\Auth\QuestifyApiGuard;
use App\Services\Api\QuestifyApiClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QuestifyApiClient::class);
    }

    public function boot(): void
    {
        URL::forceHttps();

        Auth::extend('questify-api', function ($app, $name, array $config) {
            return new QuestifyApiGuard(
                $app->make(QuestifyApiClient::class),
                $app->make('session.store'),
            );
        });
    }
}
