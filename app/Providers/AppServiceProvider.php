<?php

namespace App\Providers;

use App\Auth\QuestifyApiGuard;
use App\Services\Api\QuestifyApiClient;
use App\Services\AppInfoService;
use App\Services\MissingTranslationReporter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Events\MissingTranslationKey;
use Native\Mobile\Facades\System;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QuestifyApiClient::class);
        $this->app->singleton(AppInfoService::class);
        $this->app->singleton(MissingTranslationReporter::class);
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

        $this->app['events']->listen(MissingTranslationKey::class, function (MissingTranslationKey $event) {
            app(MissingTranslationReporter::class)->report($event->key, $event->locale);
        });

        $this->app->terminating(function () {
            app(MissingTranslationReporter::class)->flush();
        });

        try {
            $isNative = System::isMobile();
        } catch (\Throwable) {
            $isNative = false;
        }

        View::share('isNative', $isNative);
    }
}
