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

use App\OSM\Widgets\StatsWidget as Original;

class StatsWidget extends Original
{
    public function getQuery(): string
    {
        return $this->model['query'] ?: 'SELECT 0 AS dato';
    }

    public function getAttributes(): string
    {
        $attributes = parent::getAttributes();
        $js = $this->model['more_link'];

        if (!empty($js)) {
            return $attributes.' onclick="'.$js.'"';
        }

        return $attributes;
    }

    public function getTitle(): string
    {
        return $this->model['text'];
    }
}
