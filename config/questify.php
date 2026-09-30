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
        'legacy_hosts' => array_filter(explode(',', (string) env('QUESTIFY_LEGACY_JOIN_HOSTS', 'join.questifyapp.net'))),
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
