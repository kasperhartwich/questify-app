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
    | Android App Links
    |--------------------------------------------------------------------------
    |
    | The Android counterpart of universal links. The join site lists the app
    | (package name) and the SHA-256 fingerprints of the certificates it is
    | signed with in /.well-known/assetlinks.json. NativePHP gives the app a
    | verified https intent filter for nativephp.deeplink_host, which claims
    | every path on that host, not only the join screens. With Play App
    | Signing, list the app signing key from the Play Console, plus the upload
    | key for builds installed outside Play. Without a fingerprint the file is
    | not served and the links stay in the browser.
    |
    */

    'app_links' => [
        'package_name' => 'com.focusweb.questify',
        'sha256_cert_fingerprints' => array_values(array_filter(array_map(
            fn (string $fingerprint): string => strtoupper(trim($fingerprint)),
            explode(',', (string) env('QUESTIFY_ANDROID_CERT_SHA256', '')),
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Participant Tokens
    |--------------------------------------------------------------------------
    |
    | Join hands a token to builds from participant_token_min_build on (the
    | X-App-Build header); they send it back as X-Participant-Token on gameplay
    | calls. A participant who has one always needs it, and a wrong token is
    | always refused. Older builds get none: while require_participant_token is
    | off they still play on the participant id alone and the session's player
    | list stays public; on, such requests are refused (QU-40). The
    | live API is questify-admin's; this copy follows it.
    |
    */

    'require_participant_token' => (bool) env('REQUIRE_PARTICIPANT_TOKEN', false),

    'participant_token_min_build' => (int) env('PARTICIPANT_TOKEN_MIN_BUILD', 47),

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
