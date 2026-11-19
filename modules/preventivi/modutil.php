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

include_once __DIR__.'/../../core.php';

use Modules\Interventi\Intervento;
use Modules\Preventivi\Preventivo;

if (!function_exists('get_imponibile_preventivo')) {
    function get_imponibile_preventivo($id_preventivo)
    {
        $preventivo = Preventivo::find($id_preventivo);

        return $preventivo->totale_imponibile;
    }
}

if (!function_exists('get_totale_interventi_preventivo')) {
    function get_totale_interventi_preventivo($id_preventivo)
    {
        $interventi = Intervento::where('id_preventivo', $id_preventivo)->get();
        $array_interventi = $interventi->toArray();

        $totale = sum(array_column($array_interventi, 'totale_imponibile'));

        return $totale;
    }
}
