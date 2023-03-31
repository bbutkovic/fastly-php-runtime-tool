<?php

namespace Fastly\PhpRuntime;

class Util
{
    public static function getEnv(string $variable): ?string
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
}