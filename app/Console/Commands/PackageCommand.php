<?php

namespace App\Console\Commands;

use Native\Mobile\Commands\PackageCommand as BasePackageCommand;

class PackageCommand extends BasePackageCommand
{
    protected function openOutputDirectory(string $directory): void
    {
        $this->components->twoColumnDetail('Output directory', $directory);
    }
}
