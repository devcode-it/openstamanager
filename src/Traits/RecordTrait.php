<?php

namespace Traits;

trait RecordTrait
{
    use PathTrait;

    /**
     * Restituisce il percorso per il file di crezione dei record.
     *
     * @return string
     */
    public function getAddFile()
    {
        if (method_exists($this, 'getCustomAddFile')) {
            $result = $this->getCustomAddFile();

            if (!empty($result)) {
                return $result;
            }
        }

        $php = $this->filepath('add.php');
        $html = $this->filepath('add.html');

        return !empty($php) ? $php : $html;
    }

    /**
     * Controlla l'esistenza del file di crezione dei record.
     *
     * @return bool
     */
    public function hasAddFile()
    {
        return !empty($this->getAddFile());
    }

    /**
     * Restituisce il percorso per il file di modifica dei record.
     *
     * @return string
     */
    public function getEditFile()
    {
        if (method_exists($this, 'getCustomEditFile')) {
            $result = $this->getCustomEditFile();

            if (!empty($result)) {
                return $result;
            }
        }

        $php = $this->filepath('edit.php');
        $html = $this->filepath('edit.html');

        return !empty($php) ? $php : $html;
    }
}
