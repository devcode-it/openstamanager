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

use Models\Module;
use Util\Query;

/**
 * Tipologia di widget indirizzato alla visualizzazione di una statistica informativa per l'utente finale.
 * Presenta un titolo e una valore personalizzato; al click non prevede particolari operazioni.
 *
 * @since 2.5
 */
abstract class StatsWidget extends Manager
{
    abstract public function getQuery(): string;

    public function getContent(): string
    {
        $widget = $this->model;

        // Individuazione della query relativa
        $query = $this->getQuery();

        $module = Module::pool($widget['id_module']);

        $additionals = \Modules::getAdditionalsQuery($widget['id_module']);
        //$additionals = $module->getAdditionalsQuery();
        if (!empty($additionals)) {
            $query = str_replace('1=1', '1=1 '.$additionals, $query);
        }

        $query = Query::replacePlaceholder($query);

        // Individuazione del risultato della query
        $database = database();
        $value = null;
        if (!empty($query)) {
            $value = $database->fetchArray($query)[0]['dato'];
            if (!preg_match('/\\d/', $value)) {
                $value = '-';
            }
        }

        return $value;
    }
}
