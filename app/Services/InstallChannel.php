<?php

namespace App\Services;

use App\Contracts\ReadsInstallChannel;

/**
 * Whether this copy of the app came from TestFlight or the App Store. The
 * same binary goes to both, so no build flag can tell them apart. Anything
 * that cannot be read counts as the App Store, which is the right guess for
 * everyone who is not a tester and means the app never asks about betas.
 */
class InstallChannel
{
    public const TESTFLIGHT = 'testflight';

    public const APP_STORE = 'appstore';

    public const DEBUG = 'debug';

    private ?string $memo = null;

    public function __construct(private ReadsInstallChannel $reader) {}

    /**
     * The channel the API is told about: `testflight` or `appstore`. Asked once,
     * since how the app was installed cannot change while it runs.
     */
    public function current(): string
    {
        return $this->memo ??= $this->reader->read() === self::TESTFLIGHT ? self::TESTFLIGHT : self::APP_STORE;
    }

    public function isBeta(): bool
    {
        return $this->current() === self::TESTFLIGHT;
    }
}
