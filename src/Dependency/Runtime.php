<?php

namespace Fastly\PhpRuntime\Dependency;

use Exception;
use Fastly\PhpRuntime\GitHub\Api;
use Fastly\PhpRuntime\Util;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class Runtime
{
    /**
     * Ensures a version of the Fastly PHP runtime is found on the system.
     * If the runtime is not available, downloads it.
     *
     * @param string $version
     * @return void
     */
    public static function ensureRuntimeVersion(string $version): void
    {
        $runtimeFilePath = self::getRuntimeVersionPath($version);
        if (@file_exists($runtimeFilePath)) {
            // Specific runtime version already downloaded.
            return;
        }

        Api::fetchReleaseAsset('runtime.wasm', $runtimeFilePath, $version);

        if (!@file_exists($runtimeFilePath)) {
            throw new RuntimeException('Error downloading runtime release.');
        }
    }

    public static function getRuntimeVersionPath(string $version): string
    {
        $runtimeDirectory = self::getRuntimeDirectory();
        $filename = "runtime-$version.wasm";

        return $runtimeDirectory . '/' . $filename;
    }

    public static function exists(string $path): bool
    {
        return str_ends_with($path, '.wasm') && @file_exists($path);
    }

    public static function getLatestRuntimeVersion(): string
    {
        $client = Api::getHttpApiClient();

        try {
            $response = $client->get('releases/latest');

            $responseBody = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return $responseBody['tag_name'];
        } catch (Exception| GuzzleException $e) {
            throw new RuntimeException(
                'Failed to fetch latest runtime version information: ' . $e->getMessage()
            );
        }
    }

    /**
     * @throws RuntimeException
     */
    public static function getRuntimeDirectory(): string
    {
        $runtimeDirectory = self::runtimeDirFromEnv();
        if ($runtimeDirectory) {
            return $runtimeDirectory;
        }

        if (self::isWindows()) {
            $appData = Util::getEnv('APPDATA');
            if (!$appData) {
                throw new RuntimeException('APPDATA or FASTLY_PHP_RUNTIME_HOME must be set');
            }

            return rtrim(str_replace('\\', '/', $appData), '/');
        }

        $home = self::getHomeDir();

        $dirs = [];
        if (self::useXdg()) {
            $xdgConfig = Util::getEnv('XDG_CONFIG_HOME') ?? $home . '/.config';
            $dirs[] = $xdgConfig . '/fastly-php-runtime';
        }

        $dirs[] = $home . '/.fastly-php-runtime';

        foreach ($dirs as $dir) {
            if (@is_dir($dir)) {
                return $dir;
            }
        }

        $defaultDir = $dirs[0];
        if (!mkdir($defaultDir, 0755) && !is_dir($defaultDir)) {
            throw new \RuntimeException(sprintf('Could not create directory "%s"', $defaultDir));
        }

        return $defaultDir;
    }

    private static function getHomeDir(): string
    {
        $home = Util::getEnv('HOME');
        if (!$home) {
            throw new RuntimeException('APPDATA or FASTLY_PHP_RUNTIME_HOME must be set');
        }

        return rtrim(str_replace('\\', '/', $home), '/');
    }

    private static function useXdg(): bool
    {
        foreach (array_keys($_SERVER) as $key) {
            if (str_starts_with($key, 'XDG_')) {
                return true;
            }
        }

        if (@is_dir('/etc/xdg')) {
            return true;
        }

        return false;
    }

    private static function isWindows(): bool
    {
        return \defined('PHP_WINDOWS_VERSION_BUILD');
    }

    private static function runtimeDirFromEnv(): ?string
    {
        return Util::getEnv('FASTLY_PHP_RUNTIME_HOME');
    }
}