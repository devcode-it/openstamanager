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

/**
 * Classe per la gestione degli upload del progetto.
 *
 * @since 2.4.1
 */
class Uploads
{
    /** @var array Elenco delle tipologie di file permesse */
    protected static $allowed_types = [
        // Image formats
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',
        'bmp' => 'image/bmp',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'ico' => 'image/x-icon',
        // Video formats
        'asx' => 'video/asf',
        'asf' => 'video/asf',
        'wax' => 'video/asf',
        'wmv' => 'video/asf',
        'wmx' => 'video/asf',
        'avi' => 'video/avi',
        'divx' => 'video/divx',
        'flv' => 'video/x-flv',
        'mov' => 'video/quicktime',
        'qt' => 'video/quicktime',
        'mpg' => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        'mpe' => 'video/mpeg',
        'mp4' => 'video/mp4',
        'm4v' => 'video/mp4',
        'ogv' => 'video/ogg',
        'mkv' => 'video/x-matroska',
        // Text formats
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'tsv' => 'text/tab-separated-values',
        'ics' => 'text/calendar',
        'rtx' => 'text/richtext',
        'css' => 'text/css',
        'htm' => 'text/html',
        'html' => 'text/html',
        // Audio formats
        'mp3' => 'audio/mpeg',
        'm4a' => 'audio/mpeg',
        'm4b' => 'audio/mpeg',
        'mp' => 'audio/mpeg',
        'ra' => 'audio/x-realaudio',
        'ram' => 'audio/x-realaudio',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'oga' => 'audio/ogg',
        'mid' => 'audio/midi',
        'midi' => 'audio/midi',
        'wma' => 'audio/wma',
        'mka' => 'audio/x-matroska',
        // Misc application formats
        'rtf' => 'application/rtf',
        'js' => 'application/javascript',
        'pdf' => 'application/pdf',
        'swf' => 'application/x-shockwave-flash',
        'class' => 'application/java',
        'tar' => 'application/x-tar',
        'zip' => 'application/zip',
        'gz' => 'application/x-gzip',
        'gzip' => 'application/x-gzip',
        'rar' => 'application/rar',
        '7z' => 'application/x-7z-compressed',
        // MS Office formats
        'doc' => 'application/msword',
        'pot' => 'application/vnd.ms-powerpoint',
        'pps' => 'application/vnd.ms-powerpoint',
        'ppt' => 'application/vnd.ms-powerpoint',
        'wri' => 'application/vnd.ms-write',
        'xla' => 'application/vnd.ms-excel',
        'xls' => 'application/vnd.ms-excel',
        'xlt' => 'application/vnd.ms-excel',
        'xlw' => 'application/vnd.ms-excel',
        'mdb' => 'application/vnd.ms-access',
        'mpp' => 'application/vnd.ms-project',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
        'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
        'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
        'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
        'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
        'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
        'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
        'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
        'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
        'sldm' => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
        'onetoc' => 'application/onenote',
        'onetoc2' => 'application/onenote',
        'onetmp' => 'application/onenote',
        'onepkg' => 'application/onenote',
        // OpenOffice formats
        'odt' => 'application/vnd.oasis.opendocument.text',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odg' => 'application/vnd.oasis.opendocument.graphics',
        'odc' => 'application/vnd.oasis.opendocument.chart',
        'odb' => 'application/vnd.oasis.opendocument.database',
        'odf' => 'application/vnd.oasis.opendocument.formula',
        // WordPerfect formats
        'wp' => 'application/wordperfect',
        'wpd' => 'application/wordperfect',
    ];

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
     * Individua il nome fisico per il file indicato.
     *
     * @param string $source
     * @param array  $data
     *
     * @return string
     */
    public static function getName($source, $data)
    {
        $extension = strtolower(self::fileInfo($source)['extension']);
        $ok = self::isSupportedType($extension);

        $directory = DOCROOT.'/'.self::getDirectory($data['id_module'], $data['id_plugin']);

        do {
            $filename = random_string().'.'.$extension;
        } while (file_exists($directory.'/'.$filename));

        return $filename;
    }

    /**
     * Effettua l'upload di un file nella cartella indicata.
     *
     * @param array $source
     * @param array $data
     * @param array $options
     *
     * @return string
     */
    public static function upload($source, $data, $options = [])
    {
        $original = isset($source['name']) ? $source['name'] : basename($source);

        $filename = self::getName($original, $data);
        $directory = DOCROOT.'/'.self::getDirectory($data['id_module'], $data['id_plugin']);

        // Creazione file fisico
        if (
            !directory($directory) ||
            (is_uploaded_file($source['tmp_name']) && !move_uploaded_file($source['tmp_name'], $directory.'/'.$filename)) ||
            (is_string($source) && !copy($source, $directory.'/'.$filename))
        ) {
            return null;
        }

        // Registrazione del file
        $data['filename'] = $filename;
        $data['original'] = $original;
        $data['size'] = $source['size'];
        self::register($data);

        // Operazioni finali
        self::processOptions($data, $options);

        return $filename;
    }

    /**
     * Registra nel database il file caricato con i dati richiesti.
     *
     * @param array $data
     */
    public static function register($data)
    {
        $database = database();

        $database->insert('zz_files', [
            'name' => !empty($data['name']) ? $data['name'] : $data['original'],
            'filename' => !empty($data['filename']) ? $data['filename'] : $data['original'],
            'original' => $data['original'],
            'category' => !empty($data['category']) ? $data['category'] : null,
            'id_module' => !empty($data['id_module']) && empty($data['id_plugin']) ? $data['id_module'] : null,
            'id_plugin' => !empty($data['id_plugin']) ? $data['id_plugin'] : null,
            'id_record' => $data['id_record'],
            'size' => $data['size'],
            'created_by' => auth()->getUser()->id,
        ]);
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
            $directory = DOCROOT.'/'.self::getDirectory($data['id_module'], $data['id_plugin']);

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

    /**
     * Genera un ID fittizio per l'aggiunta di allegati a livello temporaneo.
     *
     * @return int
     */
    public static function getFakeID()
    {
        return -rand(1, 9999);
    }

    /**
     * Sposta gli allegati fittizi a un record reale.
     *
     * @param int $fake_id
     * @param int $id_record
     */
    public static function updateFake($fake_id, $id_record)
    {
        database()->update('zz_files', [
            'id_record' => $id_record,
        ], [
            'id_record' => $fake_id,
        ]);
    }

    /**
     * Copia gli allegati di un record in un altro record.
     *
     * @param array $from
     * @param array $to
     *
     * @return bool
     */
    public static function copy($from, $to)
    {
        $attachments = self::get($from);

        $directory = DOCROOT.'/'.self::getDirectory($to['id_module'], $to['id_plugin']);
        $directory_from = DOCROOT.'/'.self::getDirectory($from['id_module'], $from['id_plugin']);

        foreach ($attachments as $attachment) {
            $data = array_merge($attachment, $to);

            // Individuazione del nuovo nome fisico
            $data['filename'] = self::getName($directory_from.'/'.$attachment['filename'], $data);

            // Copia fisica
            if (!copy($directory_from.'/'.$attachment['filename'], $directory.'/'.$data['filename'])) {
                return false;
            }

            // Registrazione del file
            self::register($data);

            // Operazioni finali
            $options = [];
            self::processOptions($data, $options);
        }

        return true;
    }

    protected static function processOptions($data, $options)
    {
        $directory = DOCROOT.'/'.self::getDirectory($data['id_module'], $data['id_plugin']);

        if (!empty($options['thumbnails'])) {
            self::thumbnails($directory.'/'.$data['filename'], $directory);
        }
    }

    /**
     * Controlla se l'estensione è supportata dal sistema di upload.
     *
     * @param string $extension
     *
     * @return bool
     */
    protected static function isSupportedType($extension)
    {
        return in_array(strtolower($extension), array_keys(self::$allowed_types));
    }

    /**
     * Genera le thumbnails per le immagini.
     *
     * @param string $filepath
     * @param string $directory
     */
    protected static function thumbnails($filepath, $directory = null)
    {
        $fileinfo = self::fileInfo($filepath);
        $directory = empty($directory) ? dirname($filepath) : $directory;

        if (!in_array(mime_content_type($filepath), ['image/x-png', 'image/gif', 'image/jpeg'])) {
            return;
        }

        $driver = extension_loaded('gd') ? 'gd' : 'imagick';
        Intervention\Image\ImageManagerStatic::configure(['driver' => $driver]);

        $img = Intervention\Image\ImageManagerStatic::make($filepath);

        $img->resize(600, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save(slashes($directory.'/'.$fileinfo['filename'].'_thumb600.'.$fileinfo['extension']));

        $img->resize(250, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save(slashes($directory.'/'.$fileinfo['filename'].'_thumb250.'.$fileinfo['extension']));

        $img->resize(100, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save(slashes($directory.'/'.$fileinfo['filename'].'_thumb100.'.$fileinfo['extension']));
    }
}
