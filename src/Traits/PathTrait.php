<?php

namespace Traits;

use App;

trait PathTrait
{
    /**
     * Restituisce il percorso per i contenuti della struttura.
     *
     * @return string
     */
    public function getPathAttribute()
    {
        return $this->main_folder.'/'.$this->directory;
    }

    /**
     * Restituisce il percorso completo per il file indicato della struttura.
     *
     * @param $file
     *
     * @return string|null
     */
    public function filepath($file)
    {
        return App::filepath($this->path.'|custom|', $file);
    }

    /**
     * Restituisce l'URL completa per il file indicato della struttura.
     *
     * @param $file
     *
     * @return string|null
     */
    public function fileurl($file)
    {
        $filepath = App::filepath($this->path.'|custom|', $file);

        $result = str_replace(DOCROOT, ROOTDIR, $filepath);
        $result = str_replace('\\', '/', $result);

        return $result;
    }
}
