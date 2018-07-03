<?php

/**
 * Classe per la gestione degli upload del progetto.
 *
 * @since 2.4.1
 */
class Uploads
{
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
        'm4b' => 'audio/mpeg',
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
     * Effettua l'upload di un file nella cartella indicata.
     *
     * @param string $src
     * @param string $original
     * @param string $upload_dir
     * @param array  $data
     *
     * @return int|bool
     */
    public static function upload($src, $original, $upload_dir, $data = [])
    {
        $extension = pathinfo($original)['extension'];

        $ok = self::isSupportedType($extension);

        do {
            $filename = random_string().'.'.$extension;
        } while (file_exists($upload_dir.'/'.$filename));

        // Creazione file fisico
        if (!directory($upload_dir) || !move_uploaded_file($src, $upload_dir.'/'.$filename)) {
            return false;
        }

        $database = Database::getConnection();

        // Registrazione del file
        $database->insert('zz_files', [
            'nome' => !empty($data['name']) ? $data['name'] : $original,
            'filename' => $filename,
            'original' => $original,
            'category' => !empty($data['category']) ? $data['category'] : null,
            'id_module' => !empty($data['id_module']) ? $data['id_module'] : null,
            'id_record' => !empty($data['id_record']) ? $data['id_record'] : null,
            'id_plugin' => !empty($data['id_plugin']) ? $data['id_plugin'] : null,
        ]);

        return $database->lastInsertedID();
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
        return in_array($extension, array_keys(self::$allowed_types));
    }
}
