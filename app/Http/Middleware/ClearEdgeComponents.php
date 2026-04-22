<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Native\Mobile\Edge\Edge;
use Symfony\Component\HttpFoundation\Response;

class ClearEdgeComponents
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        Edge::reset();
        Edge::clear();

        return $response;
    }
}
