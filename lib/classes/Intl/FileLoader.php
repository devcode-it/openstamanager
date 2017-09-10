<?php

namespace Intl;

/**
 * Classe dedicata al caricamento delle risorse per le traduzioni.
 *
 * @since 2.3
 */
class FileLoader extends \Symfony\Component\Translation\Loader\FileLoader
{
    protected static $loaders = [];
    protected $include_filename;

    public function __construct($include_filename = false)
    {
        $this->include_filename = $include_filename;
    }

    protected function loadResource($resource)
    {
        $result = [];

        $extension = strtolower(pathinfo($resource, PATHINFO_EXTENSION));
        $loader = $this->getLoader($extension);
        if (!empty($extension) && $extension != 'po' && !empty($loader)) {
            $result = $loader->loadResource($resource);

            if (!empty($this->include_filename)) {
                $result = array_combine(
                    array_map(function ($k) use ($resource, $extension) {
                        return basename($resource, '.'.$extension).'.'.$k;
                    }, array_keys($result)),
                    $result
                );
            }
        }

        return $result;
    }

    protected function getLoader($name)
    {
        if (empty(self::$loaders[$name])) {
            $class = '\Symfony\Component\Translation\Loader\\'.ucfirst($name).'FileLoader';
            if (class_exists($class)) {
                self::$loaders[$name] = new $class();
            }
        }

        return !empty(self::$loaders[$name]) ? self::$loaders[$name] : null;
    }
}
