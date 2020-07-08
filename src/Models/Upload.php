<?php

namespace Models;

use Common\Model;
use Intervention\Image\ImageManagerStatic;

class Upload extends Model
{
    protected $table = 'zz_files';

    protected $file_info;

    public function getCategoryAttribute()
    {
        return $this->attributes['category'] ?: 'Generale';
    }

    /**
     * Crea un nuovo upload.
     *
     * @param array $source
     * @param array $data
     *
     * @return self
     */
    public static function build($source, $data, $name = null, $category = null)
    {
        $model = parent::build();

        // Informazioni di base
        $original_name = isset($source['name']) ? $source['name'] : basename($source);
        $model->original_name = $original_name; // Fix per "original" di Eloquent
        $model->size = $source['size'];

        $model->name = !empty($name) ? $name : $original_name;
        $model->category = $category;

        // Informazioni aggiuntive
        foreach ($data as $key => $value) {
            $model->{$key} = $value;
        }

        // Nome fisico del file
        $directory = DOCROOT.'/'.$model->directory;
        $filename = self::getNextName($original_name, $directory);
        $model->filename = $filename;

        // Creazione file fisico
        directory($directory);
        if (
            (is_uploaded_file($source['tmp_name']) && !move_uploaded_file($source['tmp_name'], $directory.'/'.$filename)) ||
            (is_string($source) && !copy($source, $directory.'/'.$filename))
        ) {
            return null;
        }

        $model->size = \Util\FileSystem::fileSize($directory.'/'.$filename);
        $model->user()->associate(auth()->getUser());

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
        $directory = DOCROOT.'/'.$this->directory;

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
        $result = self::build(DOCROOT.'/'.$this->filepath, $data, $this->name, $this->category);

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
     * Genera casualmente il nome fisico per il file.
     *
     * @return string
     */
    protected static function getNextName($file, $directory)
    {
        $extension = self::getInfo($file)['extension'];
        $extension = strtolower($extension);

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
