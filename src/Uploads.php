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

use Models\Upload;

/**
 * Classe per la gestione degli upload del progetto.
 *
 * @since 2.4.1
 */
class Uploads
{
    /**
     * Restituisce l'elenco degli allegati registrati per un determinato modulo/plugin e record.
     *
     * @param array $data
     *
     * @return array
     */
    public static function get($data)
    {
        $database = database();

        $uploads = $database->select('zz_files', '*', [
            'id_module' => !empty($data['id_module']) && empty($data['id_plugin']) ? $data['id_module'] : null,
            'id_plugin' => !empty($data['id_plugin']) ? $data['id_plugin'] : null,
            'id_record' => $data['id_record'],
        ]);

        return $uploads;
    }

    /**
     * Restituisce il nome della cartella per l'upload degli allegati per un determinato modulo/plugin.
     *
     * @param string|int $id_module
     * @param string|int $id_plugin
     *
     * @return string
     */
    public static function getDirectory($id_module, $id_plugin = null)
    {
        if (empty($id_plugin)) {
            $structure = Modules::get($id_module);
        } else {
            $structure = Plugins::get($id_plugin);
        }

        return $structure->upload_directory;
    }

    /**
     * Effettua l'upload di un file nella cartella indicata.
     *
     * @param string|array $source
     * @param array        $data
     * @param array        $options
     *
     * @return Upload
     */
    public static function upload($source, $data, $options = [])
    {
        return Upload::build($source, $data);
    }

    /**
     * Elimina l'allegato indicato.
     *
     * @param string $filename
     * @param array  $data
     *
     * @return string Nome del file
     */
    public static function delete($filename, $data)
    {
        if (!empty($filename)) {
            $database = database();

            $name = $database->selectOne('zz_files', ['name'], [
                'filename' => $filename,
                'id_module' => !empty($data['id_module']) && empty($data['id_plugin']) ? $data['id_module'] : null,
                'id_plugin' => !empty($data['id_plugin']) ? $data['id_plugin'] : null,
                'id_record' => $data['id_record'],
            ])['name'];

            $fileinfo = self::fileInfo($filename);
            $directory = base_dir().'/'.self::getDirectory($data['id_module'], $data['id_plugin']);

            $files = [
                $directory.'/'.$fileinfo['basename'],
                $directory.'/'.$fileinfo['filename'].'_thumb600.'.$fileinfo['extension'],
                $directory.'/'.$fileinfo['filename'].'_thumb100.'.$fileinfo['extension'],
                $directory.'/'.$fileinfo['filename'].'_thumb250.'.$fileinfo['extension'],
            ];

            if (delete($files)) {
                $database->delete('zz_files', [
                    'filename' => $fileinfo['basename'],
                    'id_module' => !empty($data['id_module']) && empty($data['id_plugin']) ? $data['id_module'] : null,
                    'id_plugin' => !empty($data['id_plugin']) ? $data['id_plugin'] : null,
                    'id_record' => $data['id_record'],
                ]);

                return $name;
            }
        }

        return null;
    }

    /**
     * Rimuove tutti gli allegati di un determinato modulo/plugin e record.
     *
     * @param array $data
     */
    public static function deleteLinked($data)
    {
        $uploads = self::get($data);

        foreach ($uploads as $upload) {
            self::delete($upload['filename'], $data);
        }
    }

    /**
     * Restituisce le informazioni relative al file indicato.
     *
     * @param string $filepath
     *
     * @return array
     */
    public static function fileInfo($filepath)
    {
        $infos = pathinfo($filepath);
        $infos['extension'] = strtolower($infos['extension']);

        return $infos;
    }
}
