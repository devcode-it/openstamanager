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

$documento = Intervento::find($id_record);

$preventivo = $dbo->fetchOne('SELECT numero, data_bozza  FROM co_preventivi WHERE id = '.prepare($documento['id_preventivo']));
$contratto = $dbo->fetchOne('SELECT nome, numero, data_bozza FROM co_contratti WHERE id = '.prepare($documento['id_contratto']));

$id_cliente = $documento['idanagrafica'];
$id_sede = $documento['idsede'];

if (!empty($documento['idsede_destinazione'])) {
    $sedi = $dbo->fetchOne('SELECT nomesede, cap, citta, indirizzo, provincia FROM an_sedi WHERE id = '.prepare($documento['idsede_destinazione']));

    $s_citta = $sedi['citta'];
    $s_indirizzo = $sedi['indirizzo'];
    $s_cap = $sedi['cap'];
    $s_provincia = $sedi['provincia'];
}

//Se ho deciso di NON mostrare i prezzi al tencico mi assicuro che non li possa vedere dalla stampa
if (Auth::user()['gruppo'] == 'Tecnici' and $options['pricing'] == true and setting('Mostra i prezzi al tecnico') == 0) {
    $options['pricing'] = false;
}
