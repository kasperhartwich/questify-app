<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    /**
     * Map route names to activity type keys.
     *
     * @var array<string, string>
     */
    private const ROUTE_TYPE_MAP = [
        'auth.register' => 'user_registered',
        'auth.login' => 'user_logged_in',
        'auth.login.phone' => 'user_logged_in',
        'auth.logout' => 'user_logged_out',
        'auth.forgot-password' => 'password_reset_requested',
        'auth.reset-password' => 'password_reset',
        'auth.social.unlink' => 'social_unlinked',
        'auth.submit-phone' => 'phone_submitted',
        'auth.verify-phone' => 'phone_verified',
        'user.profile.update' => 'profile_updated',
        'user.destroy' => 'account_deleted',
        'quests.store' => 'quest_created',
        'quests.update' => 'quest_updated',
        'quests.destroy' => 'quest_archived',
        'quests.publish' => 'quest_published',
        'quests.rate' => 'quest_rated',
        'quests.flag' => 'quest_flagged',
        'quests.favourite.toggle' => 'quest_favourited',
        'sessions.store' => 'session_created',
        'sessions.join' => 'session_joined',
        'sessions.start' => 'session_started',
        'sessions.end' => 'session_ended',
        'gameplay.arrived' => 'checkpoint_arrived',
        'gameplay.answer' => 'answer_submitted',
    ];

    /**
     * Routes where dedicated controller logging already handles the activity.
     * The middleware should NOT log these to avoid duplicates.
     *
     * @var list<string>
     */
    private const SKIP_ROUTES = [
        'quests.store',
        'quests.publish',
        'quests.rate',
        'quests.favourite.toggle',
        'sessions.store',
        'gameplay.answer',
    ];

    public function __construct(private ActivityLogService $activityLogService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log successful mutations from authenticated users
        if (! $request->user() || $response->getStatusCode() >= 400) {
            return $response;
        }

        $routeName = $request->route()?->getName();
        if (! $routeName || ! isset(self::ROUTE_TYPE_MAP[$routeName])) {
            return $response;
        }

        if (in_array($routeName, self::SKIP_ROUTES, true)) {
            return $response;
        }

        try {
            $this->activityLogService->log(
                $request->user(),
                self::ROUTE_TYPE_MAP[$routeName],
            );
        } catch (\Throwable) {
            // Don't break the request if logging fails
        }

        return $response;
    }
}
