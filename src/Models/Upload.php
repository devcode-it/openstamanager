<?php

namespace Models;

use Common\Model;

class Upload extends Model
{
    protected $table = 'zz_files';

    public function getCategoryAttribute()
    {
        return $this->attributes['category'] ?: 'Generale';
    }

    /**
     * @return string|null
     */
    public function getExtensionAttribute()
    {
        $pos = strrpos($this->filename, '.');

        if (!$pos) {
            return null;
        }

        $extension = substr($this->filename, $pos + 1);

        return strtolower($extension);
    }

    /**
     * @return string
     */
    public function getFilepathAttribute()
    {
        $parent = $this->plugin ?: $this->module;

        return $parent->upload_directory.'/'.$this->filename;
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

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function plugin()
    {
        return $this->belongsTo(Plugin::class, 'id_plugin');
    }
}
