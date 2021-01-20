<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Traits;

trait ManagerTrait
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
