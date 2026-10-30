<?php

namespace App\Contracts;

interface ReadsAppBuild
{
    /**
     * The build number of the installed binary (CFBundleVersion), as the
     * phone reports it. Null when it cannot be read.
     */
    public function read(): ?int;
}
