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

namespace Modules\FileAdapters;

use League\Flysystem\Filesystem;

class OSMFilesystem extends Filesystem
{
    /** @var array Elenco delle tipologie di file pericolose */
    protected static $not_allowed_types = [
        'php' => 'application/php',
        'php5' => 'application/php',
        'phtml' => 'application/php',
        'html' => 'text/html',
        'htm' => 'text/html',
    ];

    public function upload($directory, $filename, $contents)
    {
        // Verifico se l'esensione non è consentita
        $extension = pathinfo((string) $filename, PATHINFO_EXTENSION);
        $extension = strtolower($extension);

        // Controllo sulle estensioni permesse
        $allowed = self::isSupportedType($extension);
        if (!$allowed) {
            flash()->error(tr('Estensione non supportata!'));

            return false;
        }

        if (!$this->directoryExists($directory)) {
            try {
                $this->createDirectory($directory);
            } catch (\Exception) {
                flash()->error(tr('Impossibile creare la cartella controllare i permessi!'));

                return false;
            }
        }

        do {
            $filename = random_string().'.'.$extension;
        } while ($this->fileExists($directory.'/'.$filename));

        $this->write($directory.'/'.$filename, $contents);

        return ['filename' => $filename, 'extension' => $extension];
    }

    protected static function isSupportedType($extension)
    {
        return !in_array(strtolower((string) $extension), array_keys(self::$not_allowed_types));
    }
}
