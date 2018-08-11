<?php

namespace Traits;

use App;

trait Record
{
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

    public function hasAddFile()
    {
        return !empty($this->getAddFile());
    }

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

    public function getPath()
    {
        return $this->main_folder.'/'.$this->directory;
    }

    public function filepath($file)
    {
        return App::filepath($this->getPath().'|custom|', $file);
    }
}
