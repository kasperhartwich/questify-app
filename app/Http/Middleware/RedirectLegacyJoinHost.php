<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The join site moved from join.questifyapp.net to join.getquestify.app.
 * Links and QR codes already shared, printed or saved still carry the old
 * host, so a page view under it is sent permanently to the same address on
 * the current site. Only reads are redirected: a POST would lose its body.
 */
class RedirectLegacyJoinHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isLegacyHost($request->getHost()) || ! $request->isMethodSafe()) {
            return $next($request);
        }

        return redirect()->away(rtrim(config('questify.join.url'), '/').$request->getRequestUri(), 301);
    }

    private function isLegacyHost(string $host): bool
    {
        return in_array(strtolower($host), array_map('strtolower', config('questify.join.legacy_hosts', [])), true);
    }
}
