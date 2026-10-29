<?php

namespace App\Contracts;

interface ReadsInstallChannel
{
    /**
     * How this copy of the app was installed, as the phone reports it:
     * `testflight`, `appstore` or `debug`. Null when it cannot be read.
     */
    public function read(): ?string;
}
