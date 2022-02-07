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

use Modules\Contratti\Contratto;
use Modules\Interventi\Intervento;

/**
* Calcolo imponibile contratto (totale_righe - sconto)
 */

function get_imponibile_contratto($idcontratto)
{
    $contratto = Contratto::find($idcontratto);

    return $contratto->totale_imponibile;
}


function get_totale_interventi_contratto($idcontratto)
{
  
    $interventi = Intervento::where('id_contratto', $idcontratto)->get();
    $array_interventi = $interventi->toArray();

    $totale = sum(array_column($array_interventi, 'totale_imponibile'));

    return $totale;
}
