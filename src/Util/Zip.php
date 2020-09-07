<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

use Symfony\Component\Finder\Finder;
use ZipArchive;

/**
 * Classe dedicata alla gestione dei contenuti ZIP.
 *
 * @since 2.4.2
 */
class Zip
{
    /**
     * Controlla i requisiti per la gestione dei file ZIP.
     *
     * @return bool
     */
    public static function requirements()
    {
        return extension_loaded('zip');
    }

    /**
     * Estrae i contenuti di un file ZIP in una cartella temporanea.
     *
     * @param string $path
     * @param string $destination
     *
     * @return string
     */
    public static function extract($path, $destination = null)
    {
        // Lettura dell'archivio
        $zip = new ZipArchive();
        if (!$zip->open($path)) {
            flash()->error(self::check($path));

            return;
        }

        // Percorso di estrazione
        $extraction_dir = !empty($destination) ? $destination : DOCROOT.'/tmp';
        directory($extraction_dir);

        // Estrazione dell'archivio
        $zip->extractTo($extraction_dir);
        $zip->close();

        return $extraction_dir;
    }

    /**
     * Crea un file zip comprimendo ricorsivamente tutte le sottocartelle a partire da una cartella specificata.
     *
     * @param array|string $source
     * @param string       $destination
     * @param array        $ignores
     */
    public static function create($source, $destination, $ignores = [])
    {
        if (!directory(dirname($destination))) {
            return false;
        }

        $zip = new ZipArchive();

        $result = $zip->open($destination, ZipArchive::CREATE);
        if ($result === true && is_writable(dirname($destination))) {
            $finder = Finder::create()
                ->files()
                ->exclude((array) $ignores['dirs'])
                ->ignoreDotFiles(false)
                ->ignoreVCS(true)
                ->in($source);

            foreach ((array) $ignores['files'] as $value) {
                $finder->notName($value);
            }

            foreach ($finder as $file) {
                $zip->addFile($file, $file->getRelativePathname());
            }
            $zip->close();
        } else {
            flash()->error(tr("Errore durante la creazione dell'archivio!"));
        }

        return $result === true;
    }

    /**
     * Controllo dei file zip e gestione errori.
     *
     * @param string $path
     *
     * @return string|bool
     */
    public static function check($path)
    {
        $errno = zip_open($path);
        zip_close($errno);

        if (!is_resource($errno)) {
            // using constant name as a string to make this function PHP4 compatible
            $errors = [
                ZipArchive::ER_MULTIDISK => tr('archivi multi-disco non supportati'),
                ZipArchive::ER_RENAME => tr('ridenominazione del file temporaneo fallita'),
                ZipArchive::ER_CLOSE => tr('impossibile chiudere il file zip'),
                ZipArchive::ER_SEEK => tr('errore durante la ricerca dei file'),
                ZipArchive::ER_READ => tr('errore di lettura'),
                ZipArchive::ER_WRITE => tr('errore di scrittura'),
                ZipArchive::ER_CRC => tr('errore CRC'),
                ZipArchive::ER_ZIPCLOSED => tr("l'archivio zip è stato chiuso"),
                ZipArchive::ER_NOENT => tr('file non trovato'),
                ZipArchive::ER_EXISTS => tr('il file esiste già'),
                ZipArchive::ER_OPEN => tr('impossibile aprire il file'),
                ZipArchive::ER_TMPOPEN => tr('impossibile creare il file temporaneo'),
                ZipArchive::ER_ZLIB => tr('errore nella libreria Zlib'),
                ZipArchive::ER_MEMORY => tr("fallimento nell'allocare memoria"),
                ZipArchive::ER_CHANGED => tr('voce modificata'),
                ZipArchive::ER_COMPNOTSUPP => tr('metodo di compressione non supportato'),
                ZipArchive::ER_EOF => tr('fine del file non prevista'),
                ZipArchive::ER_INVAL => tr('argomento non valido'),
                ZipArchive::ER_NOZIP => tr('file zip non valido'),
                ZipArchive::ER_INTERNAL => tr('errore interno'),
                ZipArchive::ER_INCONS => tr('archivio zip inconsistente'),
                ZipArchive::ER_REMOVE => tr('impossibile rimuovere la voce'),
                ZipArchive::ER_DELETED => tr('voce eliminata'),
            ];

            if (isset($errors[$errno])) {
                return tr('Errore').': '.$errors[$errno];
            }

            return false;
        } else {
            return true;
        }
    }
}
