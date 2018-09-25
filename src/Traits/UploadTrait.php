<?php

namespace Traits;

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
}
