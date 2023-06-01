<?php

namespace App;

use function dirname;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

use function in_array;

use ReflectionClass;

abstract class ModuleServiceProvider extends ServiceProvider
{
    protected string $name = '';

    protected string $slug = '';

    protected string $author = '';

    protected string $description = '';

    protected string $version = '';

    protected string $url = '';

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    /**
     * @psalm-suppress InvalidNullableReturnType
     */
    public function slug(): string
    {
        $slug = $this->slug;
        if (empty($slug)) {
            /**
             * @psalm-suppress UnresolvableInclude
             */
            $cached_packages = require app()->getCachedPackagesPath();
            $slug = array_key_first(
                Arr::where(
                    $cached_packages,
                    fn (array $package) => in_array($this::class, $package['providers'], true)
                )
            );
            $this->slug = $slug;
        }

        /**
         * @psalm-suppress NullableReturnStatement
         */
        return $slug;
    }

    public function author(): string
    {
        $author = $this->author;
        if (empty($author)) {
            $slug = $this->slug();
            $author = explode('/', $slug)[0] ?? '';
            $this->author = $author;
        }

        return $author;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function url(): string
    {
        return $this->url;
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
