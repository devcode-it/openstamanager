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

use Models\Module;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato as StatoFattura;
use Modules\Fatture\Tipo;
use Modules\Preventivi\Preventivo;
use Modules\Preventivi\Stato as StatoPreventivo;

// Segmenti
$id_fatture = Module::where('name', 'Fatture di vendita')->first()->id;
if (!isset($_SESSION['module_'.$id_fatture]['id_segment'])) {
    $segments = Modules::getSegments($id_fatture);
    $_SESSION['module_'.$id_fatture]['id_segment'] = $segments[0]['id'] ?? null;
}
$id_segment = $_SESSION['module_'.$id_fatture]['id_segment'];
$idtipodocumento = $dbo->selectOne('co_tipidocumento', ['id'], [
    'predefined' => 1,
    'dir' => 'entrata',
])['id'];

switch (post('op')) {
    case 'crea_fattura':
        $documenti = collect();
        $numero_totale = 0;

        // Informazioni della fattura
        $tipo_documento = Tipo::where('id', post('idtipodocumento'))->first();

        $stato_documenti_accodabili = StatoFattura::where('name', 'Bozza')->first();
        $accodare = post('accodare');

        $data = date('Y-m-d');
        $id_segment = post('id_segment');
        $idconto = setting('Conto predefinito fatture di vendita');
        $raggruppamento = post('raggruppamento');

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $documento_import = Preventivo::find($id);
            $anagrafica = $documento_import->anagrafica;
            $id_anagrafica = $anagrafica->id;

            if (!$documento_import->stato->is_fatturabile && !$documento_import->stato->is_completato) {
                break;
            }

            // Proseguo solo se i documenti scelti sono fatturabili
            $righe = $documento_import->getRighe();
            if (!empty($righe)) {
                ++$numero_totale;

                // Ricerca fattura per anagrafica tra le registrate
                $id_sede = $raggruppamento == 'sede' ? $documento_import->idsede_destinazione : 0;
                if ($raggruppamento == 'sede') {
                    $fattura = $documenti->first(fn ($item, $key) => $item->anagrafica->id == $id_anagrafica && $item->idsede_destinazione == $id_sede);
                } else {
                    $fattura = $documenti->first(fn ($item, $key) => $item->anagrafica->id == $id_anagrafica);
                }

                // Ricerca fattura per anagrafica se l'impostazione di accodamento è selezionata
                if (!empty($accodare) && empty($fattura)) {
                    if ($raggruppamento == 'sede') {
                        $fattura = Fattura::where('idanagrafica', $id_anagrafica)
                            ->where('idstatodocumento', $stato_documenti_accodabili->id)
                            ->where('idtipodocumento', $tipo_documento->id)
                            ->where('idsede_destinazione', $id_sede)
                            ->first();
                    } else {
                        $fattura = Fattura::where('idanagrafica', $id_anagrafica)
                            ->where('idstatodocumento', $stato_documenti_accodabili->id)
                            ->where('idtipodocumento', $tipo_documento->id)
                            ->first();
                    }

                    if (!empty($fattura)) {
                        $documenti->push($fattura);
                    }
                }

                // Creazione fattura per anagrafica
                if (empty($fattura)) {
                    $fattura = Fattura::build($anagrafica, $tipo_documento, $data, $id_segment);
                    $fattura->idsede_destinazione = $id_sede;
                    $fattura->save();
                    $documenti->push($fattura);
                }

                // Inserimento righe
                foreach ($righe as $riga) {
                    $qta = $riga->qta_rimanente;

                    if ($qta > 0) {
                        $copia = $riga->copiaIn($fattura, $qta);

                        // Fix per idconto righe fattura
                        $articolo = ArticoloOriginale::find($copia->idarticolo);
                        $copia->idconto = ($articolo->idconto_vendita ?: $idconto);

                        // Aggiornamento seriali dalla riga dell'ordine
                        if ($copia->isArticolo()) {
                            $copia->serials = $riga->serials;
                        }

                        $copia->save();
                    }
                }
            }
        }

        if ($numero_totale > 0) {
            flash()->info(tr('_NUM_ preventivi fatturati!', [
                '_NUM_' => $numero_totale,
            ]));
        } else {
            flash()->warning(tr('Nessun preventivi fatturato!'));
        }
        break;

    case 'cambia_stato':
        $id_stato = post('id_stato');

        $n_preventivi = 0;
        $stato = StatoPreventivo::find($id_stato);

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $preventivo = Preventivo::find($id);

            $preventivo->stato()->associate($stato);
            $preventivo->save();

            ++$n_preventivi;
        }

        if ($n_preventivi > 0) {
            flash()->info(tr('Stato aggiornato a _NUM_ preventivi!', [
                '_NUM_' => $n_preventivi,
            ]));
        } else {
            flash()->warning(tr('Nessuno stato aggiornato!'));
        }

        break;
}

$operations['crea_fattura'] = [
    'text' => '<span><i class="fa fa-file-code-o"></i> '.tr('Fattura _TYPE_', ['_TYPE_' => strtolower((string) $module->getTranslation('title'))]),
    'data' => [
        'title' => tr('Fatturare i _TYPE_ selezionati?', ['_TYPE_' => strtolower((string) $module->getTranslation('title'))]),
        'msg' => '{[ "type": "checkbox", "label": "<small>'.tr('Aggiungere alle fatture di vendita non ancora emesse?').'</small>", "placeholder": "'.tr('Aggiungere alle fatture di vendita nello stato bozza?').'", "name": "accodare" ]}<br>{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT `zz_segments`.`id`, `zz_segments_lang`.`title` AS descrizione FROM `zz_segments` LEFT JOIN `zz_segments_lang` ON (`zz_segments`.`id` = `zz_segments_lang`.`id_record` AND `zz_segments_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `id_module`=\''.$id_fatture.'\' ORDER BY `zz_segments_lang`.`title`", "value": "'.$id_segment.'" ]}<br>
        {[ "type": "select", "label": "'.tr('Tipo documento').'", "name": "idtipodocumento", "required": 1, "values": "query=SELECT `co_tipidocumento`.`id`, CONCAT(`codice_tipo_documento_fe`, \' - \', `title`) AS descrizione FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `enabled` = 1 AND `dir` =\'entrata\' ORDER BY `codice_tipo_documento_fe`", "value": "'.$idtipodocumento.'" ]}<br>
        {[ "type": "select", "label": "'.tr('Raggruppa per').'", "name": "raggruppamento", "required": 1, "values": "list=\"cliente\":\"Cliente\",\"sede\":\"Sede\"", "value": "'.setting('Raggruppamento fatturazione massiva preventivi').'" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['cambia_stato'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Cambia stato'),
    'data' => [
        'title' => tr('Vuoi davvero aggiornare lo stato di questi preventivi?'),
        'msg' => '<br>{[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT `co_statipreventivi`.`id`, `co_statipreventivi_lang`.`title` AS descrizione, `colore` as _bgcolor_ FROM `co_statipreventivi` LEFT JOIN `co_statipreventivi_lang` ON (`co_statipreventivi`.`id` = `co_statipreventivi_lang`.`id_record` AND `co_statipreventivi_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') ORDER BY `title`" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

return $operations;
