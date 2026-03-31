<?php

namespace App\Http\Middleware;

use App\Services\AppInfoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FetchAppInfo
{
    public function __construct(private AppInfoService $appInfo) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->appInfo->get();

        return $next($request);
    }
}
