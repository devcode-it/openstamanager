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

namespace Models;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Intervention\Image\ImageManager;
use Modules\CategorieFiles\Categoria;
use Modules\FileAdapters\FileAdapter;
use Modules\FileAdapters\OSMFilesystem;

class Upload extends Model
{
    use SimpleModelTrait;

    /**
     * Elenco delle estensioni file per mime type.
     * Fonte: https://www.iana.org/assignments/media-types/media-types.xhtml.
     *
     * @var array
     */
    protected static $extension_association = [
        'image/gif' => 'gif',
        'image/bmp' => 'bmp',
        'image/jpg' => 'jpg',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/heic' => 'heic',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'image/tiff' => 'tiff',
    ];

    protected $table = 'zz_files';

    protected $file_info;

    /**
     * @return mixed|string
     */
    public function getCategoryAttribute()
    {
        $categoria = database()->fetchOne('SELECT `name` FROM `zz_files_categories` WHERE `id` = '.prepare($this->attributes['id_category']));

        return $categoria['name'] ?? 'Generale';
    }

    public function setCategoryAttribute($value)
    {
        if ($value == 'Generale' || $value == '') {
            $value = 0;
        }

        $this->attributes['id_category'] = $value;
    }

    /**
     * Crea un nuovo upload.
     *
     * @param string|array $source
     * @param array        $data
     * @param null         $name
     * @param null         $category
     *
     * @return self
     */
    public static function build($source = null, $data = null, $name = null, $category = null)
    {
        // Verifica permessi prima del caricamento
        if (!self::checkUploadPermissions($data)) {
            return false;
        }

        $model = new static();

        // Informazioni di base
        if (empty($name)) {
            $name = $data['name'] ?? ($source['name'] ?? $source);
        }

        $category = $data['category'] ?? $category;
        $id_category = null;
        if ($category) {
            $id_category = Categoria::where('name', '=', $category)->first()->id;
        }

        // Se presente category ma non id_category la creo
        if ($category && !$id_category) {
            $categoria = Categoria::build();
            $categoria->name = $category;
            $categoria->save();
            $id_category = $categoria->id;
        }

        $original_name = $source['name'] ?? $name;
        $id_category = $data['id_category'] ?? $id_category;

        // Nome e categoria dell'allegato
        $model->name = !empty($name) ? $name : $original_name;
        $model->id_category = $id_category;

        // Nome di origine dell'allegato
        $original_name = !empty($data['original_name']) ? $data['original_name'] : $original_name; // Campo "original_name" variato in modo dinamico
        $model->original_name = $original_name; // Fix per "original" di Eloquent

        // Informazioni sulle relazioni
        $model->id_module = !empty($data['id_module']) && empty($data['id_plugin']) ? $data['id_module'] : null;
        $model->id_plugin = !empty($data['id_plugin']) ? $data['id_plugin'] : null;
        $model->id_record = !empty($data['id_record']) ? $data['id_record'] : null;
        $model->key = !empty($data['key']) ? $data['key'] : null;

        // Caricamento file con interfaccia di upload

        // Verifico qual'è il metodo predefinito al momento
        if (!empty($data['id_adapter'])) {
            $adapter_config = FileAdapter::find($data['id_adapter']);
        } else {
            $adapter_config = FileAdapter::getDefaultConnector();
        }
        $class = $adapter_config->class;

        // Costruisco l'oggetto che mi permetterà di effettuare il caricamento
        $adapter = new $class($adapter_config->options);
        $filesystem = new OSMFilesystem($adapter);

        // Su source arriva direttamente il contenuto o il formato file
        if (is_array($source)) {
            // Caricamento con l'interfaccia di upload
            try {
                $file = $filesystem->upload($model->attachments_directory, $original_name, file_get_contents($source['tmp_name']));
            } catch (\Exception) {
                flash()->error(tr('Impossibile creare il file!'));

                return false;
            }

            // Aggiornamento dimensione fisica e responsabile del caricamento
            $model->size = $source['size'];
        } else {
            // Caricamento con l'interfaccia di upload
            try {
                $file = $filesystem->upload($model->attachments_directory, $original_name, file_exists($source) ? file_get_contents($source) : $source);
                $model->size = $file['size'];
            } catch (\Exception) {
                flash()->error(tr('Impossibile creare il file!'));

                return false;
            }
        }

        $model->id_adapter = $adapter_config->id;
        $model->filename = $file['filename'];

        $model->user()->associate(auth_osm()->getUser());

        // Rimozione estensione dal nome visibile
        $extension = $file['extension'];
        if (string_ends_with($model->name, $extension)) {
            $length = strlen((string) $extension) + 1;
            $model->name = substr((string) $model->name, 0, -$length);
        }

        $model->save();

        return $model;
    }

    /**
     * @return string
     */
    public function getDirectoryAttribute()
    {
        $parent = $this->plugin ?: $this->module;

        return strtolower($parent ? $parent->directory : '');
    }

    /**
     * @return string
     */
    public function getAttachmentsDirectoryAttribute()
    {
        $parent = $this->plugin ?: $this->module;

        return strtolower($parent ? $parent->attachments_directory : '');
    }

    /**
     * @return string
     */
    public function getOriginalNameAttribute()
    {
        return $this->attributes['original'];
    }

    public function setOriginalNameAttribute($value)
    {
        $this->attributes['original'] = $value;
    }

    /**
     * @return bool
     */
    public function isImage()
    {
        $list = ['jpg', 'png', 'gif', 'jpeg', 'bmp'];

        return in_array($this->extension, $list);
    }

    /**
     * @return string
     */
    public static function getExtensionFromMimeType($file_content)
    {
        $finfo = finfo_open();
        $mime_type = finfo_buffer($finfo, $file_content, FILEINFO_MIME_TYPE);
        finfo_close($finfo);

        return self::$extension_association[$mime_type];
    }

    /**
     * @return bool
     */
    public function isFatturaElettronica()
    {
        return $this->extension == 'xml' && strtolower($this->category) == 'fattura elettronica';
    }

    /**
     * @return bool
     */
    public function isPDF()
    {
        return $this->extension == 'pdf';
    }

    /**
     * @return bool
     */
    public function hasPreview()
    {
        return $this->isImage() || $this->isFatturaElettronica() || $this->isPDF();
    }

    public function delete()
    {
        // Verifica permessi prima dell'eliminazione
        $data = [
            'id_module' => $this->id_module,
            'id_plugin' => $this->id_plugin,
            'id_record' => $this->id_record,
            'key' => $this->key,
        ];

        if (!self::checkDeletePermissions($data)) {
            return false;
        }

        $adapter_config = FileAdapter::find($this->id_adapter);
        $class = $adapter_config->class;

        // Costruisco l'oggetto che mi permetterà di effettuare il caricamento
        $adapter = new $class($adapter_config->options);
        $filesystem = new OSMFilesystem($adapter);

        $filesystem->delete($this->attachments_directory.'/'.$this->filename);

        // Elimino i thumbnails associati se il file è un'immagine
        if ($this->isImage()) {
            $this->deleteThumbnails($filesystem);
        }

        return parent::delete();
    }

    public function save(array $options = [], $skip_resize = false)
    {
        if ($this->isImage() && !$skip_resize) {
            // self::generateThumbnails($this);

            if (setting('Ridimensiona automaticamente le immagini caricate')) {
                self::ridimensionaImmagini($this);
            }
        }

        return parent::save($options);
    }

    public function copia($data)
    {
        $data['original_name'] = $this->original_name;
        $data['key'] = $this->key;

        $adapter_config = FileAdapter::find($this->id_adapter);
        $class = $adapter_config->class;

        // Costruisco l'oggetto che mi permetterà di effettuare il caricamento
        $adapter = new $class($adapter_config->options);
        $filesystem = new OSMFilesystem($adapter);

        $source = $this->get_contents();

        $file = $filesystem->read($this->attachments_directory.'/'.$this->filename);

        $result = self::build($file, $data, $this->name, $this->category);

        return $result;
    }

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function plugin()
    {
        return $this->belongsTo(Plugin::class, 'id_plugin');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function get_contents()
    {
        $adapter_config = FileAdapter::find($this->id_adapter);
        $class = $adapter_config->class;

        // Costruisco l'oggetto che mi permetterà di effettuare il caricamento
        $adapter = new $class($adapter_config->options);
        $filesystem = new OSMFilesystem($adapter);

        return $filesystem->read($this->attachments_directory.'/'.$this->filename);
    }

    /**
     * @return string
     */
    public function getLocalFilepathAttribute()
    {
        $parent = $this->plugin ?: $this->module;

        return $parent->upload_directory.'/'.$this->filename;
    }

    /**
     * @return string
     */
    public function getLocalFileurlAttribute()
    {
        return str_replace('\\', '/', $this->local_filepath);
    }

    /**
     * @return array
     */
    public function getInfoAttribute()
    {
        if (!isset($this->file_info)) {
            $filepath = $this->local_filepath;
            $infos = self::getInfo($filepath);

            $this->file_info = $infos;
        }

        return $this->file_info;
    }

    /**
     * @return string|null
     */
    public function getExtensionAttribute()
    {
        $extension = pathinfo($this->filename, PATHINFO_EXTENSION);
        $extension = strtolower($extension);

        return $extension;
    }

    /**
     * Restituisce i contenuti del file locale.
     *
     * @return false|string
     */
    public function getContent()
    {
        return file_get_contents(base_dir().'/files/'.$this->attachments_directory.'/'.$this->filename);
    }

    public static function getInfo($file)
    {
        return pathinfo((string) $file);
    }

    public static function ridimensionaImmagini($upload)
    {
        $info = $upload->info;
        $directory = $upload->attachments_directory;

        $filepath = base_dir().'/'.$info['dirname'].'/'.$info['filename'].'.'.$info['extension'];

        if (!in_array(mime_content_type($filepath), ['image/png', 'image/gif', 'image/jpeg'])) {
            return;
        }

        $manager = ImageManager::gd(autoOrientation: true);
        $img = $manager->read($filepath);
        $img->scale(setting('Larghezza per ridimensionamento immagini'), null);

        $img->save(slashes($filepath));

        clearstatcache();
        $upload->size = filesize(slashes($filepath));
        $upload->save([], true);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_category');
    }

    /**
     * Elimina i thumbnails associati al file immagine.
     *
     * @param OSMFilesystem $filesystem
     *
     * @return void
     */
    protected function deleteThumbnails($filesystem)
    {
        $info = $this->info;
        $directory = $this->attachments_directory;

        // Dimensioni dei thumbnails
        $thumbnail_sizes = ['600', '250', '100'];

        foreach ($thumbnail_sizes as $size) {
            $thumbnail_filename = $info['filename'].'_thumb'.$size.'.'.$info['extension'];
            try {
                $filesystem->delete($directory.'/'.$thumbnail_filename);
            } catch (\Exception) {
                // Se il thumbnail non esiste, continuo senza errori
            }
        }
    }

    /**
     * Verifica i permessi per il caricamento degli allegati.
     *
     * @param array $data Dati del file da caricare
     * @return bool True se l'utente ha i permessi, false altrimenti
     */
    protected static function checkUploadPermissions($data)
    {
        // Se non ci sono dati del modulo, permetti il caricamento (per compatibilità)
        if (empty($data['id_module']) && empty($data['id_plugin'])) {
            return true;
        }

        $id_module = $data['id_module'] ?? null;
        if (!empty($data['id_plugin'])) {
            $plugin = \Models\Plugin::find($data['id_plugin']);
            $id_module = $plugin ? $plugin->idmodule_to : null;
        }

        if (!$id_module) {
            return true; // Se non riusciamo a determinare il modulo, permetti per compatibilità
        }

        // Verifica permessi in base al tipo di accesso
        if (\Permissions::isTokenAccess()) {
            // Per accesso tramite token, verifica i permessi del token
            $token_info = $_SESSION['token_access'];
            $token_permission = $token_info['permessi'] ?? 'r';

            // Caricamento allegati: permessi 'ra', 'rwa' o 'rw'
            $has_permission = in_array($token_permission, ['ra', 'rwa', 'rw']);

            if (!$has_permission) {
                flash()->error(tr('Non hai permessi di caricamento per il modulo _MODULE_', [
                    '_MODULE_' => '"'.\Models\Module::find($id_module)->getTranslation('title').'"',
                ]));
            }

            return $has_permission;
        } else {
            // Per accesso normale, usa i permessi standard del modulo
            $has_permission = (\Modules::getPermission($id_module) == 'rw');

            if (!$has_permission) {
                flash()->error(tr('Non hai permessi di scrittura per il modulo _MODULE_', [
                    '_MODULE_' => '"'.\Models\Module::find($id_module)->getTranslation('title').'"',
                ]));
            }

            return $has_permission;
        }
    }

    /**
     * Verifica i permessi per l'eliminazione degli allegati.
     *
     * @param array $data Dati del file da eliminare
     * @return bool True se l'utente ha i permessi, false altrimenti
     */
    protected static function checkDeletePermissions($data)
    {
        // Se non ci sono dati del modulo, permetti l'eliminazione (per compatibilità)
        if (empty($data['id_module']) && empty($data['id_plugin'])) {
            return true;
        }

        $id_module = $data['id_module'] ?? null;
        if (!empty($data['id_plugin'])) {
            $plugin = \Models\Plugin::find($data['id_plugin']);
            $id_module = $plugin ? $plugin->idmodule_to : null;
        }

        if (!$id_module) {
            return true; // Se non riusciamo a determinare il modulo, permetti per compatibilità
        }

        // Verifica permessi in base al tipo di accesso
        if (\Permissions::isTokenAccess()) {
            // Per accesso tramite token, verifica i permessi del token
            $token_info = $_SESSION['token_access'];
            $token_permission = $token_info['permessi'] ?? 'r';

            // Rimozione allegati: solo permessi 'rwa' o 'rw'
            $has_permission = in_array($token_permission, ['rwa', 'rw']);

            if (!$has_permission) {
                flash()->error(tr('Non hai permessi di eliminazione per il modulo _MODULE_', [
                    '_MODULE_' => '"'.\Models\Module::find($id_module)->getTranslation('title').'"',
                ]));
            }

            return $has_permission;
        } else {
            // Per accesso normale, usa i permessi standard del modulo
            $has_permission = (\Modules::getPermission($id_module) == 'rw');

            if (!$has_permission) {
                flash()->error(tr('Non hai permessi di scrittura per il modulo _MODULE_', [
                    '_MODULE_' => '"'.\Models\Module::find($id_module)->getTranslation('title').'"',
                ]));
            }

            return $has_permission;
        }
    }

    /**
     * Genera le thumbnails per le immagini.
     */
    protected static function generateThumbnails($upload)
    {
        $info = $upload->info;
        $directory = $upload->attachments_directory;

        $filepath = $upload->filepath;

        if (!in_array(mime_content_type($filepath), ['image/x-png', 'image/gif', 'image/jpeg'])) {
            return;
        }

        $img = getImageManager()->read($filepath);

        $img->resize(600, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save(slashes($directory.'/'.$info['filename'].'_thumb600.'.$info['extension']));

        $img->scale(250, null);
        $img->save(slashes($directory.'/'.$info['filename'].'_thumb250.'.$info['extension']));

        $img->scale(100, null);
        $img->save(slashes($directory.'/'.$info['filename'].'_thumb100.'.$info['extension']));
    }
}
