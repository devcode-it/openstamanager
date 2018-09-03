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
    public function getPath()
    {
        return $this->main_folder.'/'.$this->directory;
    }

    /**
     * Restituisce il percorso completo per il file indicato della struttura.
     *
     * @return string
     */
    public function filepath($file)
    {
        return App::filepath($this->getPath().'|custom|', $file);
    }

    /**
     * Restituisce l'URL completa per il file indicato della struttura.
     *
     * @return string
     */
    public function fileurl($file)
    {
        $filepath = App::filepath($this->getPath().'|custom|', $file);

        $result = str_replace(DOCROOT, ROOTDIR, $filepath);
        $result = str_replace('\\', '/', $result);

        return $result;
    }
}
