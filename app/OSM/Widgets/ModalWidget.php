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

namespace App\OSM\Widgets;

/**
 * Tipologia di widget dedicato alla gestione di un modal aperto al click
 * Presenta un titolo e una valore personalizzato; al click produce l'apertura del modal specificato.
 *
 * @since 2.5
 */
abstract class ModalWidget extends Manager
{
    abstract public function getModal(): string;

    abstract public function getLink(): string;

    public function getAttributes(): string
    {
        $title = $this->getTitle();

        return 'data-href="'.$this->getLink().'" data-toggle="modal" data-title="'.$title.'"';
    }
}
