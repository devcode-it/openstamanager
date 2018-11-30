<?php

namespace Traits;

use Models\Upload;

trait UploadTrait
{
    /**
     * Restituisce il percorso per il salvataggio degli upload.
     *
     * @return string
     */
    public function getUploadDirectoryAttribute()
    {
        return 'files/'.$this->directory;
    }

    public function uploads($id_record)
    {
        return $this->hasMany(Upload::class, $this->upload_identifier)->where('id_record', $id_record)->get()->groupBy('category');
    }
}
