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
}
