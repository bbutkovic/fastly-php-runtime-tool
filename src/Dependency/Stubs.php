<?php

namespace Fastly\PhpRuntime\Dependency;

use Fastly\PhpRuntime\GitHub\Api;
use RuntimeException;

class Stubs
{
    /**
     * @param string $version
     * @param string $output
     * @throws RuntimeException
     * @return void
     */
    public static function downloadStubs(
        string $version = 'latest',
        string $output = 'fastly-php-runtime.stubs.php'
    ): void {
        Api::fetchReleaseAsset('fastly-php-runtime.stubs.php', $output, $version);

        if (!@file_exists($output)) {
            throw new RuntimeException('Error downloading runtime release.');
        }
    }
}