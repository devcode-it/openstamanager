<?php

namespace Traits;

trait UploadTrait
{
    protected $upload_directory = 'files';

    /**
     * Restituisce il percorso per il salvataggio degli upload.
     *
     * @return string
     */
    public function getUploadDirectoryAttribute()
    {
        return $this->upload_directory.'/'.$this->directory;
    }
}
