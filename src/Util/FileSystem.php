<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Util;

/**
 * @since 2.4.6
 */
class FileSystem
{
    protected static $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

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
            } catch (\Symfony\Component\Filesystem\Exception\IOException) {
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
    public static function folderSize($path, $exclusions = [])
    {
        $total = 0;
        $path = realpath($path);

        if ($path !== false && $path != '' && file_exists($path)) {
            $finder = new \Symfony\Component\Finder\Finder();
            $finder->files()->in($path);

            // Apply directory exclusions
            if (!empty($exclusions)) {
                $finder->exclude($exclusions);
            }

            foreach ($finder as $file) {
                try {
                    // Verifica se il file è accessibile prima di ottenere la sua dimensione
                    if (is_readable($file->getRealPath())) {
                        $total += $file->getSize();
                    }
                } catch (\Exception) {
                    // Ignora i file che non possono essere letti (es. link simbolici rotti)
                    continue;
                }
            }
        }

        return $total;
    }

    /**
     * Restituisce il numero di file contenuti nella cartella indicata.
     *
     * @param string $path
     *
     * @return int
     */
    public static function fileCount($path, $exclusions = [])
    {
        $total = 0;
        $path = realpath($path);

        if ($path !== false && $path != '' && file_exists($path)) {
            $finder = new \Symfony\Component\Finder\Finder();
            $finder->files()->in($path);

            // Apply directory exclusions
            if (!empty($exclusions)) {
                $finder->exclude($exclusions);
            }

            foreach ($finder as $file) {
                try {
                    // Verifica se il file è accessibile
                    if (is_readable($file->getRealPath())) {
                        ++$total;
                    }
                } catch (\Exception) {
                    // Ignora i file che non possono essere letti (es. link simbolici rotti)
                    continue;
                }
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
        $units = self::$units;

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= 1024 ** $pow;
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Converte l'unità di misura relativa in byte.
     *
     * @param string $bytes
     *
     * @return string
     */
    public static function convertBytes($bytes)
    {
        $units = self::$units;

        $result = 0;
        $count = count($units);
        for ($i = $count - 1; $i >= 0; --$i) {
            $pos = strpos($bytes, (string) $units[$i]);
            if ($pos !== false) {
                $value = substr($bytes, 0, $pos);

                $result = floatval($value) * 1024 ** $i;

                break;
            }
        }

        return $result;
    }
}
