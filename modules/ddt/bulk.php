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
use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Modules\Fatture\Tipo;

if ($module->name == 'Ddt in uscita') {
    $dir = 'entrata';
    $module_fatture = 'Fatture di vendita';
} else {
    $dir = 'uscita';
    $module_fatture = 'Fatture di acquisto';
}

// Segmenti
$id_fatture = Module::where('name', $module_fatture)->first()->id;
if (!isset($_SESSION['module_'.$id_fatture]['id_segment'])) {
    $segments = Modules::getSegments($id_fatture);
    $_SESSION['module_'.$id_fatture]['id_segment'] = $segments[0]['id'] ?? null;
}
$id_segment = $_SESSION['module_'.$id_fatture]['id_segment'];
$idconto = $module_fatture == 'Fatture di vendita' ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');
$idtipodocumento = $dbo->selectOne('co_tipidocumento', ['id'], [
    'predefined' => 1,
    'dir' => $dir,
])['id'];

switch (post('op')) {
    case 'create_invoice':
        $documenti = collect();
        $numero_totale = 0;

        // Informazioni della fattura
        $tipo_documento = Tipo::where('id', post('idtipodocumento'))->first();

        $stato_documenti_accodabili = Stato::where('name', 'Bozza')->first();
        $accodare = post('accodare');

        $data = date('Y-m-d');
        $id_segment = post('id_segment');
        $raggruppamento = post('raggruppamento');

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $documento_import = DDT::find($id);
            $anagrafica = $documento_import->anagrafica;
            $id_anagrafica = $anagrafica->id;

            // Proseguo solo se i documenti scelti sono fatturabili
            if ($documento_import->isImportabile()) {
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
                            $copia->id_conto = ($articolo->idconto_vendita ?: $idconto);

                            // Aggiornamento seriali dalla riga dell'ordine
                            if ($copia->isArticolo()) {
                                $copia->serials = $riga->serials;
                            }

                            $copia->save();
                        }
                    }
                }
            }
        }

        if ($numero_totale > 0) {
            flash()->info(tr('_NUM_ ddt fatturati!', [
                '_NUM_' => $numero_totale,
            ]));
        } else {
            flash()->warning(tr('Nessun ddt fatturato!'));
        }
        break;

    case 'delete_bulk':
        foreach ($id_records as $id) {
            $documento = DDT::find($id);
            try {
                $documento->delete();
            } catch (InvalidArgumentException) {
            }
        }

        flash()->info(tr('Ddt eliminati!'));
        break;

    case 'change_status':
        $id_stato = post('id_stato');

        $n_ddt = 0;

        foreach ($id_records as $id) {
            $ddt = DDT::find($id);
            $ddt->idstatoddt = $id_stato;
            $ddt->save();

            ++$n_ddt;
        }

        if ($n_ddt > 0) {
            flash()->info(tr('Stato cambiato a _NUM_ DDT!', [
                '_NUM_' => $n_ordini,
            ]));
        } else {
            flash()->warning(tr('Nessun DDT modificato!'));
        }

        break;
}

$operations['change_status'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Cambia stato'),
    'data' => [
        'title' => tr('Vuoi davvero cambiare lo stato per questi DDT?'),
        'msg' => tr('Seleziona lo stato in cui spostare tutti i DDT').'.<br>
            <br>{[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT `dt_statiddt`.`id`, `dt_statiddt_lang`.`title` as descrizione, `colore` as _bgcolor_ FROM dt_statiddt LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt`.`id` = `dt_statiddt_lang`.`id_record` AND `dt_statiddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['delete_bulk'] = [
    'text' => '<span><i class="fa fa-trash"></i> '.tr('Elimina').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero eliminare i ddt selezionati?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-danger',
    ],
];

$operations['create_invoice'] = [
    'text' => '<span><i class="fa fa-file-code-o"></i> '.tr('Fattura _TYPE_', ['_TYPE_' => strtolower((string) $module->getTranslation('title'))]),
    'data' => [
        'title' => tr('Fatturare i _TYPE_ selezionati?', ['_TYPE_' => strtolower((string) $module->getTranslation('title'))]),
        'msg' => '{[ "type": "checkbox", "label": "<small>'.tr('Aggiungere alle _TYPE_ non ancora emesse?', ['_TYPE_' => strtolower($module_fatture)]).'", "placeholder": "'.tr('Aggiungere alle _TYPE_ nello stato bozza?', ['_TYPE_' => strtolower($module_fatture)]).'</small>", "name": "accodare" ]}<br>
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_fatture, 'is_sezionale' => 1]).', "value": "'.$id_segment.'", "select-options-escape": true ]}<br>
            {[ "type": "select", "label": "'.tr('Tipo documento').'", "name": "idtipodocumento", "required": 1, "values": "query=SELECT `co_tipidocumento`.`id`, CONCAT(`codice_tipo_documento_fe`, \' - \', `co_tipidocumento_lang`.`title`) AS descrizione FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id`=`co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `enabled` = 1 AND `dir` ='.prepare($dir).' ORDER BY `codice_tipo_documento_fe`", "value": "'.$idtipodocumento.'" ]}<br>
            {[ "type": "select", "label": "'.tr('Raggruppa per').'", "name": "raggruppamento", "required": 1, "values": "list=\"cliente\":\"Cliente\",\"sede\":\"Sede\"", "value": "'.setting('Raggruppamento fatturazione massiva ddt').'" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

return $operations;
