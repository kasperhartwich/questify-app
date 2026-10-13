<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Join Site
    |--------------------------------------------------------------------------
    |
    | The public site players open from a shared link or a scanned QR code. A
    | phone serves its screens from php://127.0.0.1, so a relative address
    | would hand other players a link only the host's own app can open. The
    | packaged app always uses the production site: the packager ships the
    | developer's local .env.
    |
    | The site used to live at join.questifyapp.net. Requests that still
    | arrive under a legacy host are sent to the current one, so links and
    | printed QR codes shared before the move keep working.
    |
    */

    'join' => [
        'url' => env('NATIVEPHP_RUNNING') ? 'https://join.getquestify.app' : env('QUESTIFY_JOIN_URL', 'https://join.getquestify.app'),
        'legacy_hosts' => array_filter(array_map('trim', explode(',', (string) env('QUESTIFY_LEGACY_JOIN_HOSTS', 'join.questifyapp.net')))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Universal Links
    |--------------------------------------------------------------------------
    |
    | A join link on the join site opens the installed app instead of the
    | browser. The site lists the app (team ID and bundle ID) and the paths it
    | takes in /.well-known/apple-app-site-association; the app claims the
    | host with its associated-domains entitlement (nativephp.deeplink_host).
    | The shell opens the link's path as the same screen inside the app.
    |
    */

    'universal_links' => [
        'app_ids' => ['2KF539NBBF.com.focusweb.questify'],
        'paths' => ['/join', '/join/*'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scoring Defaults
    |--------------------------------------------------------------------------
    */

    'scoring' => [
        'speed_bonus_window_seconds' => 30,
        'speed_bonus_max_points' => 50,
        'completion_bonus_max_points' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'max_per_page' => 100,
    ],

];
