<?php

namespace App;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

abstract class ModuleServiceProvider extends ServiceProvider
{
    public static string $name = '';

    public static string $slug = '';

    public static string $author = '';

    public static string $description = '';

    public static string $version = '';

    public static string $url = '';

    public static function name(): string
    {
        return static::$name;
    }

    public static function description(): string
    {
        return static::$description;
    }

    public static function slug(): string
    {
        $slug = static::$slug;
        if (empty($slug)) {
            $cachedPackages = require app()->getCachedPackagesPath();
            $slug = array_key_first(Arr::where($cachedPackages, static fn (array $package) => in_array(static::class, $package['providers'], true)));
            static::$slug = $slug;
        }

        return $slug;
    }

    public static function author(): string
    {
        $author = static::$author;
        if (empty($author)) {
            $slug = static::slug();
            $author = explode('/', $slug)[0] ?? '';
            static::$author = $author;
        }

        return $author;
    }

    public static function version(): string
    {
        return static::$version;
    }

    public static function url(): string
    {
        return static::$url;
    }

    public static function modulePath(): string
    {
        return dirname((new ReflectionClass(static::class))->getFileName(), 2);
    }

    public static function namespace(): string
    {
        return (new ReflectionClass(static::class))->getNamespaceName();
    }
}
