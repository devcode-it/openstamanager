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
use UnexpectedValueException;
use Util\FileSystem;

class Upload extends Model
{
    use SimpleModelTrait;

    /** @var array Elenco delle tipologie di file pericolose */
    protected static $not_allowed_types = [
        'php' => 'application/php',
        'php5' => 'application/php',
        'phtml' => 'application/php',
        'html' => 'text/html',
        'htm' => 'text/html',
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

    /**
     * @param $value
     */
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
    public static function build($source, $data, $name = null, $category = null)
    {
        $model = new static();

        // Informazioni di base
        $original_name = isset($source['name']) ? $source['name'] : basename($source);
        $name = isset($data['name']) ? $data['name'] : $name;
        $category = isset($data['category']) ? $data['category'] : $category;

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

        // Definizione del nome fisico del file
        $directory = base_dir().'/'.$model->directory;
        $filename = self::getNextName($original_name, $directory);
        if (empty($filename)) {
            throw new UnexpectedValueException("Estensione dell'allegato non supportata");
        }
        $model->filename = $filename;

        // Creazione del file fisico
        directory($directory);
        $file = slashes($directory.DIRECTORY_SEPARATOR.$filename);
        if (
            (is_array($source) && is_uploaded_file($source['tmp_name']) && !move_uploaded_file($source['tmp_name'], $file)) ||
            (is_string($source) && is_file($source) && !copy($source, $file)) ||
            (is_string($source) && !is_file($source) && file_put_contents($file, $source) === false)
        ) {
            throw new UnexpectedValueException("Errore durante il salvataggio dell'allegato");
        }

        // Aggiornamento dimensione fisica e responsabile del caricamento
        $model->size = FileSystem::fileSize($file);
        $model->user()->associate(auth()->getUser());

        // Rimozione estensione dal nome visibile
        $extension = $model->extension;
        if (string_ends_with($model->name, $extension)) {
            $length = strlen($extension) + 1;
            $model->name = substr($model->name, 0, -$length);
        }

        $model->save();

        return $model;
    }

    /**
     * @return array
     */
    public function getInfoAttribute()
    {
        if (!isset($this->file_info)) {
            $filepath = $this->filepath;
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
        return strtolower($this->info['extension']);
    }

    /**
     * @return string
     */
    public function getDirectoryAttribute()
    {
        $parent = $this->plugin ?: $this->module;

        return $parent->upload_directory;
    }

    /**
     * @return string
     */
    public function getFilepathAttribute()
    {
        return $this->directory.'/'.$this->filename;
    }

    /**
     * @return string
     */
    public function getFileurlAttribute()
    {
        return str_replace('\\', '/', $this->filepath);
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
     * Restituisce i contenuti del file.
     *
     * @return false|string
     */
    public function getContent()
    {
        return file_get_contents($this->filepath);
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
        $info = $this->info;
        $directory = base_dir().'/'.$this->directory;

        $files = [
            $directory.'/'.$info['basename'],
            $directory.'/'.$info['filename'].'_thumb600.'.$info['extension'],
            $directory.'/'.$info['filename'].'_thumb100.'.$info['extension'],
            $directory.'/'.$info['filename'].'_thumb250.'.$info['extension'],
        ];

        delete($files);

        return parent::delete();
    }

    public function save(array $options = [])
    {
        if ($this->isImage()) {
            //self::generateThumbnails($this);
        }

        return parent::save($options);
    }

    public function copia($data)
    {
        $result = self::build(base_dir().'/'.$this->filepath, $data, $this->name, $this->category);

        return $result;
    }

    public static function getInfo($file)
    {
        return pathinfo($file);
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

    /**
     * Controlla se l'estensione è supportata dal sistema di upload.
     *
     * @param string $extension
     *
     * @return bool
     */
    protected static function isSupportedType($extension)
    {
        return !in_array(strtolower($extension), array_keys(self::$not_allowed_types));
    }

    /**
     * Genera casualmente il nome fisico per il file.
     *
     * @return string
     */
    protected static function getNextName($file, $directory)
    {
        $extension = self::getInfo($file)['extension'];
        $extension = strtolower($extension);

        // Controllo sulle estensioni permesse
        $allowed = self::isSupportedType($extension);
        if (!$allowed) {
            return false;
        }

        do {
            $filename = random_string().'.'.$extension;
        } while (file_exists($directory.'/'.$filename));

        return $filename;
    }

    /**
     * Genera le thumbnails per le immagini.
     */
    protected static function generateThumbnails($upload)
    {
        $info = $upload->info;
        $directory = $upload->directory;

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
}
