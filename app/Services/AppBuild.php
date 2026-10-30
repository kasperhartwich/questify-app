<?php

namespace App\Services;

use App\Contracts\ReadsAppBuild;

/**
 * The build number this copy of the app really has. The phone knows it; the
 * config only holds what the environment said when the app was bundled, which
 * is one less than the IPA's number. The config is the fallback.
 */
class AppBuild
{
    private ?int $memo = null;

    public function __construct(private ReadsAppBuild $reader) {}

    public function number(): int
    {
        return $this->memo ??= $this->reader->read() ?? (int) config('nativephp.version_code', 1);
    }
}
