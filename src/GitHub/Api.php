<?php

namespace Fastly\PhpRuntime\GitHub;

use Exception;
use Fastly\PhpRuntime\Util;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class Api
{
    /**
     * TODO: Implement some basic GitHub API endpoints
     */

    private const GH_RUNTIME_REPO = 'bbutkovic/fastly-php-runtime';

    public static function getHttpApiClient(string $responseType = 'json'): Client
    {
        switch ($responseType) {
            case 'json':
            default:
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

        $authToken = Util::getEnv('FASTLY_PHP_RUNTIME_GH_TOKEN');
        if ($authToken) {
            $headers['Authorization'] = "Bearer $authToken";
        }

        $repo = self::GH_RUNTIME_REPO;
        return new Client([
            'base_uri' => "https://api.github.com/repos/$repo/",
            'headers' => $headers
        ]);
    }

    /**
     * @param string $asset
     * @param string $output
     * @param string $version
     * @throws RuntimeException
     * @return void
     */
    public static function fetchReleaseAsset(string $asset, string $output, string $version = 'latest'): void
    {
        if (empty($output)) {
            throw new RuntimeException('$output file must be provided.');
        }

        if ($version === 'latest') {
            $version = self::getLatestRuntimeVersion();
        }

        try {
            $assetsResponse = self::getHttpApiClient()->request('GET', "releases/tags/$version");
            $assetsBody = json_decode($assetsResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $assets = $assetsBody['assets'];

            $releaseAssetId = '';
            foreach ($assets as $releaseAsset) {
                if ($releaseAsset['name'] === $asset) {
                    $releaseAssetId = $releaseAsset['id'];
                    break;
                }
            }

            if (!$releaseAssetId) {
                throw new RuntimeException('Could not find asset for download');
            }

            self::getHttpApiClient('octet-stream')->request(
                'GET',
                "releases/assets/$releaseAssetId", [
                    'sink' => $output
                ]
            );
        } catch (Exception | GuzzleException $e) {
            throw new RuntimeException("Error with downloading release asset $asset: " . $e->getMessage());
        }
    }

    /**
     * @throws RuntimeException
     * @return string
     */
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
}