<?php

namespace Traits;

use Models\Upload;

trait UploadTrait
{
    protected $uploads_directory = 'files';

    /**
     * Restituisce il percorso per il salvataggio degli upload.
     *
     * @return string
     */
    public function getUploadDirectoryAttribute()
    {
        $directory = $this->directory ?: 'common';

        return $this->uploads_directory.'/'.$directory;
    }

    public function uploads($id_record)
    {
        return $this->hasMany(Upload::class, $this->upload_identifier)->where('id_record', $id_record)->get();
    }
}
