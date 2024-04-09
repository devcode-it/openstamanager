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
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Modules\Fatture\Tipo as TipoFattura;
use Modules\Ordini\Ordine;
use Modules\Ordini\Tipo;

// Segmenti
$id_modulo_fatture = (new Module())->getByField('name', 'Fatture di vendita', Models\Locale::getPredefined()->id);
if (!isset($_SESSION['module_'.$id_modulo_fatture]['id_segment'])) {
    $segments = Modules::getSegments($id_modulo_fatture);
    $_SESSION['module_'.$id_modulo_fatture]['id_segment'] = $segments[0]['id'] ?? null;
}
$id_segment = $_SESSION['module_'.$id_modulo_fatture]['id_segment'];
$id_segment_ordini = $_SESSION['module_'.$id_module]['id_segment'];
$idconto = setting('Conto predefinito fatture di vendita');
$idtipodocumento = $dbo->selectOne('co_tipidocumento', ['id'], [
    'predefined' => 1,
    'dir' => 'entrata',
])['id'];

switch (post('op')) {
    case 'crea_fattura':
        $documenti = collect();
        $numero_totale = 0;

        $tipo_documento = TipoFattura::where('id', post('idtipodocumento'))->first();

        $stato_documenti_accodabili = (new Stato())->getByField('name', 'Bozza', Models\Locale::getPredefined()->id);
        $accodare = post('accodare');

        $data = date('Y-m-d');
        $id_segment = post('id_segment');
        $raggruppamento = post('raggruppamento');

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $documento_import = Ordine::find($id);
            $anagrafica = $documento_import->anagrafica;
            $id_anagrafica = $anagrafica->id;

            // Proseguo solo se i documenti scelti sono fatturabili
            $ordine = $dbo->fetchOne('SELECT `or_statiordine_lang`.`name` AS stato FROM `or_ordini` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine`=`or_statiordine`.`id` LEFT JOIN `or_statiordine_lang` ON (`or_statiordine`.`id`=`or_statiordine_lang`.`id_record` AND `or_statiordine_lang`.`id_lang`= '.prepare(Models\Locale::getDefault()->id).') WHERE `or_ordini`.`id`='.prepare($id))['stato'];
            if (!in_array($ordine, ['Fatturato', 'Evaso', 'Bozza', 'In attesa di conferma', 'Annullato'])) {
                $righe = $documento_import->getRighe();
                if (!empty($righe)) {
                    ++$numero_totale;

                    // Ricerca fattura per anagrafica tra le registrate
                    $id_sede = $raggruppamento == 'sede' ? $documento_import->idsede : 0;
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
                                ->first();
                        } else {
                            $fattura = Fattura::where('idanagrafica', $id_anagrafica)
                                ->where('idstatodocumento', $stato_documenti_accodabili->id)
                                ->where('idtipodocumento', $tipo_standard->id)
                                ->where('idsede', $id_sede)
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
            flash()->info(tr('_NUM_ ordini fatturati!', [
                '_NUM_' => $numero_totale,
            ]));
        } else {
            flash()->warning(tr('Nessun ordine fatturato!'));
        }
        break;

    case 'cambia_stato':
        $id_stato = post('id_stato');

        $n_ordini = 0;

        foreach ($id_records as $id) {
            $ordine = Ordine::find($id);
            $ordine->idstatoordine = $id_stato;
            $ordine->save();

            ++$n_ordini;
        }

        if ($n_ordini > 0) {
            flash()->info(tr('Stato cambiato a _NUM_ ordini!', [
                '_NUM_' => $n_ordini,
            ]));
        } else {
            flash()->warning(tr('Nessun ordine modificato!'));
        }

        break;

    case 'unisci_rdo':
        $id_stato = post('id_stato');
        $data = post('data') ?: null;
        $tipo = Tipo::where('dir', 'uscita')->first();

        $numero_ordini = [];
        $fornitori = [];
        $new_ordini = [];

        foreach ($id_records as $id) {
            $ordine = Ordine::find($id);

            if (in_array($ordine->stato->getTranslation('name'), ['Bozza', 'In attesa di conferma', 'Accettato'])) {
                // Controllo se è già stato creato un nuovo ordine per l'anagrafica
                if (in_array($ordine->idanagrafica, array_keys($new_ordini))) {
                    $new_ordine = Ordine::find($new_ordini[$ordine->idanagrafica]);
                } else {
                    $anagrafica = Anagrafica::find($ordine->idanagrafica);
                    $new_ordine = Ordine::build($anagrafica, $tipo, $data, post('id_segment'));
                    $new_ordine->idstatoordine = $id_stato;
                    $new_ordine->data = $data;
                    $new_ordine->save();

                    $new_ordini[$ordine->idanagrafica] = $new_ordine->id;
                    $numero_ordini[] = $new_ordine->numero;
                }

                $righe = $ordine->getRighe();

                foreach ($righe as $riga) {
                    $new_riga = $riga->replicate();
                    $new_riga->setDocument($new_ordine);
                    $new_riga->save();
                }

                $ordine->delete();
            }
        }

        if (sizeof($numero_ordini) > 0) {
            flash()->info(tr('Sono stati creati i seguenti ordini: ', [
                '_NUM_' => implode(',', $numero_ordini),
            ]));
        } else {
            flash()->warning(tr('Nessun ordine creato!'));
        }

        break;
}
if ($module->getTranslation('name') == 'Ordini cliente') {
    $operations['crea_fattura'] = [
        'text' => '<span><i class="fa fa-file-code-o"></i> '.tr('Fattura _TYPE_', ['_TYPE_' => strtolower($module->getTranslation('name'))]),
        'data' => [
            'title' => tr('Fatturare i _TYPE_ selezionati?', ['_TYPE_' => strtolower($module->getTranslation('name'))]),
            'msg' => '{[ "type": "checkbox", "label": "<small>'.tr('Aggiungere alle _TYPE_ non ancora emesse?', ['_TYPE_' => strtolower($module_fatture)]).'", "placeholder": "'.tr('Aggiungere alle _TYPE_ nello stato bozza?', ['_TYPE_' => strtolower($module_fatture)]).'</small>", "name": "accodare" ]}
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_modulo_fatture, 'is_sezionale' => 1]).', "value": "'.$id_segment.'", "select-options-escape": true ]}
            {[ "type": "select", "label": "'.tr('Tipo documento').'", "name": "idtipodocumento", "required": 1, "values": "query=SELECT `co_tipidocumento`.`id`, CONCAT(`codice_tipo_documento_fe`, \' - \', `name`) AS descrizione FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `enabled` = 1 AND `dir` =\'entrata\' ORDER BY `codice_tipo_documento_fe`", "value": "'.$idtipodocumento.'" ]}<br>
            {[ "type": "select", "label": "'.tr('Raggruppa per').'", "name": "raggruppamento", "required": 1, "values": "list=\"cliente\":\"Cliente\",\"sede\":\"Sede\"" ]}',
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ];
} else {
    if (App::debug()) {
        $operations['unisci_rdo'] = [
            'text' => '<span><i class="fa fa-refresh"></i> '.tr('Unisci rdo'),
            'data' => [
                'title' => tr('Unire gli ordini selezionati?'),
                'msg' => tr('Gli ordini saranno processati solo se in uno dei seguenti stati: Bozza, In attesa di conferma, Accettato.<br>Tutti gli ordini processati verranno eliminati e verrà creato un nuovo ordine unificato per fornitore.').'
                {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_module, 'is_sezionale' => 1]).', "value": "'.$id_segment_ordini.'", "select-options-escape": true ]}
                {[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT `or_statiordine`.`id`, `name` FROM `or_statiordine` LEFT JOIN `or_statiordine_lang` ON (`or_statiordine`.`id` = `or_statiordine_lang`.`id_record` AND `or_statiordine_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') ORDER BY `name` ASC" ]}
                {[ "type": "date", "label": "'.tr('Data').'", "name": "data", "required": 1]}',
                'button' => tr('Procedi'),
                'class' => 'btn btn-lg btn-warning',
                'blank' => false,
            ],
        ];
    }
}

$operations['cambia_stato'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Cambia stato'),
    'data' => [
        'title' => tr('Vuoi davvero cambiare lo stato per questi ordini?'),
        'msg' => tr('Seleziona lo stato in cui spostare tutti gli ordini').'.<br>
        <br>{[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT `or_statiordine`.`id`, `name`, `colore` as _bgcolor_ FROM `or_statiordine` LEFT JOIN `or_statiordine_lang` ON (`or_statiordine`.`id` = `or_statiordine_lang`.`id_record` AND `or_statiordine_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') ORDER BY `name` ASC" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

return $operations;
