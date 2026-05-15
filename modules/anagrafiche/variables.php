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

use Modules\Anagrafiche\Anagrafica;

$anagrafica = Anagrafica::find($id_record);

// cliente
if ($anagrafica->id_conto_cliente != '') {
    $conto = $anagrafica->id_conto_cliente;
    $conto_descrizione = $dbo->fetchOne('SELECT CONCAT ((SELECT numero FROM co_piano_dei_conti2 WHERE id=co_piano_dei_conti3.id_piano_dei_conti2), ".", numero, " ", descrizione) AS descrizione FROM co_piano_dei_conti3 WHERE id='.prepare($conto))['descrizione'];
}
// Fornitore
elseif ($anagrafica->id_conto_fornitore != '') {
    $conto = $anagrafica->id_conto_fornitore;
    $conto_descrizione = $dbo->fetchOne('SELECT CONCAT ((SELECT numero FROM co_piano_dei_conti2 WHERE id=co_piano_dei_conti3.id_piano_dei_conti2), ".", numero, " ", descrizione) AS descrizione FROM co_piano_dei_conti3 WHERE id='.prepare($conto))['descrizione'];
}

// Variabili da sostituire
return [
    'ragione_sociale' => $anagrafica->ragione_sociale,
    'codice' => $anagrafica->codice,
    'id_anagrafica' => $anagrafica->id,
    'conto' => $conto,
    'conto_descrizione' => $conto_descrizione,
    'email' => $anagrafica->email,
    'pec' => $anagrafica->pec,
];
