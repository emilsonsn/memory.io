<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class VersionedCache
{
    /**
     * @param  array<string, mixed>  $params
     */
    public static function remember(string $namespace, array $params, int $ttlSeconds, Closure $callback, string|int|null $scope = null): mixed
    {
        $version = self::version($namespace, $scope);
        $dataKey = self::dataKey($namespace, $scope, $params, $version);

        return Cache::remember($dataKey, now()->addSeconds($ttlSeconds), $callback);
    }

    public static function bump(string $namespace, string|int|null $scope = null): void
    {
        $versionKey = self::versionKey($namespace, $scope);

        Cache::add($versionKey, 1, now()->addYear());
        Cache::increment($versionKey);
    }

    private static function version(string $namespace, string|int|null $scope = null): int
    {
        return (int) Cache::get(self::versionKey($namespace, $scope), 1);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private static function dataKey(string $namespace, string|int|null $scope, array $params, int $version): string
    {
        $scopePart = self::normalizeScope($scope);
        $fingerprint = md5(json_encode($params));

        return "cache:{$namespace}:{$scopePart}:v{$version}:{$fingerprint}";
    }

    private static function versionKey(string $namespace, string|int|null $scope = null): string
    {
        $scopePart = self::normalizeScope($scope);

        return "cache:{$namespace}:{$scopePart}:version";
    }

    private static function normalizeScope(string|int|null $scope): string
    {
        return $scope === null ? 'global' : (string) $scope;
    }
}
