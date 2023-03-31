<?php

namespace Fastly\PhpRuntime\Cli;

use Exception;
use Fastly\PhpRuntime\Cli\Commands\StubsDownloadCommand;
use Symfony\Component\Console\Application;
use Fastly\PhpRuntime\Cli\Commands\BundleCommand;

class Tool {
    /**
     * @throws Exception
     */
    public static function run(): void
    {
        $app = new Application('Fastly PHP Runtime Tool');
        $app->add(new BundleCommand());
        $app->add(new StubsDownloadCommand());
        $app->run();
    }
}