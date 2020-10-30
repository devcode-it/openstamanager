<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

use Modules\Interventi\Intervento;

include_once __DIR__.'/../../core.php';

$tecnici = [];
if (!empty($id_record)) {
    $documento = Intervento::find($id_record);
    $sessioni = $documento->sessioni;

    foreach ($sessioni as $sessione) {
        $id_tecnico = $sessione->idtecnico;
        $inizio = $sessione->orario_inizio;
        $fine = $sessione->orario_fine;
        if (!isset($tecnici[$id_tecnico])) {
            $tecnici[$id_tecnico] = [];
        }

        $tecnici[$id_tecnico][] = [
            'inizio' => $inizio,
            'fine' => $fine,
        ];
    }
}

// Lettura dei dati da URL
$tecnici_selezionati = filter('tecnici');
if (!empty($tecnici_selezionati)) {
    $inizio = filter('inizio');
    $fine = filter('fine');

    foreach ($tecnici_selezionati as $id_tecnico) {
        if (empty($id_tecnico)) {
            continue;
        }

        if (!isset($tecnici[$id_tecnico])) {
            $tecnici[$id_tecnico] = [];
        }

        $tecnici[$id_tecnico][] = [
            'inizio' => $inizio,
            'fine' => $fine,
        ];
    }
}

// Blocco dei controlli se non sono presenti tecnici
if (empty($tecnici)) {
    return;
}

// Individuazione dei conflitti con altri interventi
$elenco_conflitti = [];
foreach ($tecnici as $id_tecnico => $ore) {
    $query = 'SELECT idintervento, orario_inizio, orario_fine FROM in_interventi_tecnici WHERE idtecnico = '.prepare($id_tecnico).($id_record ? ' AND idintervento != '.prepare($id_record) : '');

    // Conflitti ristretti per orario
    foreach ($ore as $orario) {
        $query_conflitto = $query.' AND ((orario_inizio > '.prepare($orario['inizio']).' AND orario_inizio < '.prepare($orario['fine']).') OR
        (orario_fine > '.prepare($orario['inizio']).' AND orario_fine < '.prepare($orario['fine']).') OR
        (orario_inizio < '.prepare($orario['inizio']).' AND orario_fine > '.prepare($orario['inizio']).') OR
        (orario_inizio < '.prepare($orario['fine']).' AND orario_fine > '.prepare($orario['fine']).'))';

        $conflitto = $database->fetchArray($query_conflitto);
        if (!empty($conflitto)) {
            $elenco_conflitti[$id_tecnico][] = [
                'inizio' => $orario['inizio'],
                'fine' => $orario['fine'],
                'conflitti' => $conflitto,
            ];
        }
    }
}

if (empty($elenco_conflitti)) {
    return;
}

echo '
<div class="alert alert-warning">
    <p>'.tr('Sono presenti dei conflitti con le sessioni di lavoro di alcuni tecnici').'.</p>

    <table class="table table-condensed">
        <thead>
            <tr>
                <th>'.tr('Tecnico/attività').'</th>
                <th>'.tr('Orario di conflitto').'</th>
            </tr>
        </thead>

        <tbody>';

foreach ($elenco_conflitti as $id_tecnico => $elenco_conflitti_tecnico) {
    $anagrafica_tecnico = $database->fetchOne('SELECT ragione_sociale, deleted_at FROM an_anagrafiche WHERE idanagrafica = '.prepare($id_tecnico));

    foreach ($elenco_conflitti_tecnico as $conflitto) {
        echo '
            <tr>
                <td>'.$anagrafica_tecnico['ragione_sociale'].' '.(!empty($anagrafica_tecnico['deleted_at']) ? '<small class="text-danger">('.tr('Eliminato').')' : '').'</td>
                <td>'.timestampFormat($conflitto['inizio']).' - '.timestampFormat($conflitto['fine']).'</td>
            </tr>';

        foreach ($conflitto['conflitti'] as $conflitto_intervento) {
            $intervento = Intervento::find($conflitto_intervento['idintervento']);
            echo '
            <tr>
                <td>'.Modules::link('Interventi', $intervento->id, $intervento->getReference()).'</td>
                <td>'.timestampFormat($conflitto_intervento['orario_inizio']).' - '.timestampFormat($conflitto_intervento['orario_fine']).'</td>
            </tr>';
        }
    }
}

echo '
        </tbody>
    </table>
</div>';
