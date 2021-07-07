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

namespace App\OSM\Widgets\Retro;

use App\Http\Controllers\LegacyController;
use App\OSM\Widgets\ModalWidget as Original;
use Models\Module;
use Util\Query;

class ModalWidget extends Original
{
    public function getModal(): string
    {
        $content = '';

        $widget = $this->model;
        if (!empty($widget['more_link'])) {
            $content = LegacyController::simulate($widget['more_link']);
        }

        return $content;
    }

    public function getLink(): string
    {
        $id = $this->model->id;

        return route('widget-modal', [
            'id' => $id,
        ]);
    }

    public function getTitle(): string
    {
        return $this->model['text'] ?: '';
    }

    public function getContent(): string
    {
        $content = '';

        $widget = $this->model;
        if (!empty($widget['query'])) {
            $query = $widget['query'];
            $module = Module::pool($widget['id_module']);

            $additionals = \Modules::getAdditionalsQuery($widget['id_module']);
            //$additionals = $module->getAdditionalsQuery();
            if (!empty($additionals)) {
                $query = str_replace('1=1', '1=1 '.$additionals, $query);
            }

            $query = Query::replacePlaceholder($query);

            // Individuazione del risultato della query
            $database = database();
            $value = '-';
            if (!empty($query)) {
                $value = $database->fetchArray($query)[0]['dato'];
            }

            $content = preg_match('/\\d/', $value) ? $value : '-';
        }

        return $content;
    }
}
