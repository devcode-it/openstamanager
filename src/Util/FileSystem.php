<?php

namespace Util;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @since 2.4.6
 */
class FileSystem
{
    /**
     * Controlla l'esistenza e i permessi di scrittura sul percorso indicato.
     *
     * @param string $path
     *
     * @return bool
     */
    public static function directory($path)
    {
        if (is_dir($path) && is_writable($path)) {
            return true;
        } elseif (!is_dir($path)) {
            // Filesystem Symfony
            $fs = new \Symfony\Component\Filesystem\Filesystem();

            // Tentativo di creazione
            try {
                $fs->mkdir($path);

                return true;
            } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
            }
        }

        return false;
    }

    /**
     * Individua la dimensione di una cartella indicata.
     *
     * @param string $path
     *
     * @return int
     */
    public static function folderSize($path)
    {
        $total = 0;
        $path = realpath($path);

        if ($path !== false && $path != '' && file_exists($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                $total += $object->getSize();
            }
        }

        return $total;
    }

    /**
     * Individua la dimensione del file indicato.
     *
     * @param string $path
     *
     * @return int
     */
    public static function fileSize($path)
    {
        $path = realpath($path);

        return filesize($path);
    }

    /**
     * Individua la dimensione del file o cartella indicati, formattati in modo standard.
     *
     * @param string $path
     *
     * @return string
     */
    public static function size($path)
    {
        $path = realpath($path);

        $result = is_file($path) ? self::fileSize($path) : self::folderSize($path);

        return self::formatBytes($result);
    }

    /**
     * Formatta i byte nell'unità di misura relativa.
     *
     * @param int $bytes
     * @param int $precision
     *
     * @return string
     */
    public static function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
