<?php

namespace Fastly\PhpRuntime\Dependency;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class Runtime
{
    private const GH_RUNTIME_REPO = 'bbutkovic/fastly-php-runtime';

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

        try {
            $assetsResponse = self::getHttpApiClient()->request('GET', "releases/tags/$version");
            $assetsBody = json_decode($assetsResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $assets = $assetsBody['assets'];

            $assetId = '';
            foreach ($assets as $asset) {
                if ($asset['name'] === 'runtime.wasm') {
                    $assetId = $asset['id'];
                    break;
                }
            }

            if (!$assetId) {
                throw new RuntimeException('Could not find asset for download');
            }

            self::getHttpApiClient('octet-stream')->request('GET', "releases/assets/$assetId", [
                'sink' => $runtimeFilePath
            ]);
        } catch (Exception | GuzzleException $e) {
            throw new RuntimeException('Error with downloading latest runtime release: ' . $e->getMessage());
        }

        if (!@file_exists($runtimeFilePath)) {
            throw new RuntimeException('Error downloading latest runtime release.');
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
        $client = self::getHttpApiClient();

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
            $appData = self::getEnv('APPDATA');
            if (!$appData) {
                throw new RuntimeException('APPDATA or FASTLY_PHP_RUNTIME_HOME must be set');
            }

            return rtrim(str_replace('\\', '/', $appData), '/');
        }

        $home = self::getHomeDir();

        $dirs = [];
        if (self::useXdg()) {
            $xdgConfig = self::getEnv('XDG_CONFIG_HOME') ?? $home . '/.config';
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
        $home = self::getEnv('HOME');
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
        return self::getEnv('FASTLY_PHP_RUNTIME_HOME');
    }

    private static function getEnv(string $variable): ?string
    {
        if (array_key_exists($variable, $_SERVER)) {
            return (string) $_SERVER[$variable];
        }
        if (array_key_exists($variable, $_ENV)) {
            return (string) $_ENV[$variable];
        }

        $env = getenv($variable);
        return $env ? (string) $env : null;
    }

    private static function getHttpApiClient(string $responseType = 'json'): Client
    {
        switch ($responseType) {
            case 'json':
                $responseType = 'application/vnd.github+json';
                break;
            case 'raw':
                $responseType = 'application/vnd.github.v3.raw';
                break;
            case 'octet-stream':
                $responseType = 'application/octet-stream';
                break;
        }

        $headers = [
            'Accept' => $responseType,
        ];

        $authToken = self::getEnv('FASTLY_PHP_RUNTIME_GH_TOKEN');
        if ($authToken) {
            $headers['Authorization'] = "Bearer $authToken";
        }

        $repo = self::GH_RUNTIME_REPO;
        return new Client([
            'base_uri' => "https://api.github.com/repos/$repo/",
            'headers' => $headers
        ]);
    }
}