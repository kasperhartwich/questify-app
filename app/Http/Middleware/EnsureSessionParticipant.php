<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a player into the lobby and gameplay screens with either an account or
 * the participant id a guest join stored. The API accepts guests on these
 * endpoints, but the screens sat behind "auth", so a signed-out player joined
 * successfully and was then bounced to the welcome screen.
 */
class EnsureSessionParticipant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() || session('questify_participant_id')) {
            return $next($request);
        }

        return redirect('/');
    }
}
