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

//cliente
if ($anagrafica->idconto_cliente != '') {
    $conto = $anagrafica->idconto_cliente;
    $conto_descrizione = $dbo->fetchOne('SELECT CONCAT ((SELECT numero FROM co_pianodeiconti2 WHERE id=co_pianodeiconti3.idpianodeiconti2), ".", numero, " ", descrizione) AS descrizione FROM co_pianodeiconti3 WHERE id='.prepare($conto))['descrizione'];
}
//Fornitore
elseif ($anagrafica->idconto_fornitore != '') {
    $conto = $anagrafica->idconto_fornitore;
    $conto_descrizione = $dbo->fetchOne('SELECT CONCAT ((SELECT numero FROM co_pianodeiconti2 WHERE id=co_pianodeiconti3.idpianodeiconti2), ".", numero, " ", descrizione) AS descrizione FROM co_pianodeiconti3 WHERE id='.prepare($conto))['descrizione'];
}

// Variabili da sostituire
return [
    'ragione_sociale' => $anagrafica->ragione_sociale,
    'codice' => $anagrafica->codice,
    'id_anagrafica' => $anagrafica->idanagrafica,
    'conto' => $conto,
    'conto_descrizione' => $conto_descrizione,
];
