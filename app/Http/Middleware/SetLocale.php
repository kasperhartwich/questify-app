<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['en', 'da'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        App::setLocale($locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $sessionLocale = session('locale');
        if ($sessionLocale && in_array($sessionLocale, self::SUPPORTED_LOCALES, true)) {
            return $sessionLocale;
        }

        $userLocale = Auth::user()?->locale;
        if ($userLocale && in_array($userLocale, self::SUPPORTED_LOCALES, true)) {
            return $userLocale;
        }

        $preferred = $request->getPreferredLanguage(self::SUPPORTED_LOCALES);

        if ($preferred !== null) {
            return $preferred;
        }

        return config('app.locale', 'en');
    }
}
