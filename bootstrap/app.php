<?php

use App\Console\Commands\PackageCommand;
use App\Http\Middleware\FetchAppInfo;
use App\Http\Middleware\LogActivity;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    );

if (! env('NATIVEPHP_RUNNING')) {
    $app->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']],
    );
}

return $app
    ->withCommands([PackageCommand::class])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->api(append: [LogActivity::class]);
        $middleware->web(append: [FetchAppInfo::class, SetLocale::class]);
        $middleware->redirectGuestsTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A 404 in a mobile app is a dead link we shipped, not routine noise.
        // Laravel ignores every HttpException, so report it here instead and
        // return null to fall through to the normal error page.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            Log::warning('Route not found', ['url' => $request->fullUrl()]);

            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }

            return null;
        });
    })->create();
