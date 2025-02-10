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
use Intervention\Image\ImageManagerStatic;
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
        return $this->attributes['category'] ?: 'Generale';
    }

    public function setCategoryAttribute($value)
    {
        if ($value == 'Generale' || $value == '') {
            $value = null;
        }

        $this->attributes['category'] = $value;
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
        $model = new static();

        // Informazioni di base
        if (empty($name)) {
            $name = $data['name'] ?? ($source['name'] ?? $source);
        }

        $original_name = $source['name'] ?? $name;
        $category = $data['category'] ?? $category;

        // Nome e categoria dell'allegato
        $model->name = !empty($name) ? $name : $original_name;
        $model->category = $category;

        // Nome di origine dell'allegato
        $original_name = !empty($data['original_name']) ? $data['original_name'] : $original_name; // Campo "original_name" variato in modo dinamico
        $model->original_name = $original_name; // Fix per "original" di Eloquent

        // Informazioni sulle relazioni
        $model->id_module = !empty($data['id_module']) && empty($data['id_plugin']) ? $data['id_module'] : null;
        $model->id_plugin = !empty($data['id_plugin']) ? $data['id_plugin'] : null;
        $model->id_record = !empty($data['id_record']) ? $data['id_record'] : null;

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

        $model->user()->associate(auth()->getUser());

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
        $adapter_config = FileAdapter::find($this->id_adapter);
        $class = $adapter_config->class;

        // Costruisco l'oggetto che mi permetterà di effettuare il caricamento
        $adapter = new $class($adapter_config->options);
        $filesystem = new OSMFilesystem($adapter);

        $filesystem->delete($this->attachments_directory.'/'.$this->filename);

        return parent::delete();
    }

    public function save(array $options = [])
    {
        if ($this->isImage()) {
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
        return file_get_contents(base_dir().'/'.$this->local_filepath);
    }

    public static function getInfo($file)
    {
        return pathinfo((string) $file);
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

        $driver = extension_loaded('gd') ? 'gd' : 'imagick';
        ImageManagerStatic::configure(['driver' => $driver]);

        $img = ImageManagerStatic::make($filepath);

        $img->resize(600, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save(slashes($directory.'/'.$info['filename'].'_thumb600.'.$info['extension']));

        $img->resize(250, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save(slashes($directory.'/'.$info['filename'].'_thumb250.'.$info['extension']));

        $img->resize(100, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save(slashes($directory.'/'.$info['filename'].'_thumb100.'.$info['extension']));
    }

    protected static function ridimensionaImmagini($upload)
    {
        $info = $upload->info;
        $directory = $upload->attachments_directory;

        $filepath = base_dir().'/'.$info['dirname'].'/'.$info['filename'].'.'.$info['extension'];

        if (!in_array(mime_content_type($filepath), ['image/png', 'image/gif', 'image/jpeg'])) {
            return;
        }

        $driver = extension_loaded('gd') ? 'gd' : 'imagick';
        ImageManagerStatic::configure(['driver' => $driver]);

        $img = ImageManagerStatic::make($filepath);

        $img->resize(setting('Larghezza per ridimensionamento immagini'), null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save(slashes($filepath));
    }
}
