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

use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Modules\Fatture\Tipo;
use Modules\Fatture\Components\Riga;

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
    $module_fatture = 'Fatture di vendita';
} else {
    $dir = 'uscita';
    $module_fatture = 'Fatture di acquisto';
}

// Segmenti
$id_fatture = Modules::get($module_fatture)['id'];
if (!isset($_SESSION['module_'.$id_fatture]['id_segment'])) {
    $segments = Modules::getSegments($id_fatture);
    $_SESSION['module_'.$id_fatture]['id_segment'] = isset($segments[0]['id']) ? $segments[0]['id'] : null;
}
$id_segment = $_SESSION['module_'.$id_fatture]['id_segment'];
$idconto = $module_fatture == 'Fatture di vendita' ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');
$idtipodocumento = $dbo->selectOne('co_tipidocumento', ['id'], [
    'predefined' => 1,
    'dir' => $dir,
])['id'];

switch (post('op')) {
    case 'crea_fattura':
        $documenti = collect();
        $numero_totale = 0;

        // Informazioni della fattura
        $tipo_documento = Tipo::where('id', post('idtipodocumento'))->first();

        $stato_documenti_accodabili = Stato::where('descrizione', 'Bozza')->first();
        $accodare = post('accodare');

        $data = date('Y-m-d');
        $id_segment = post('id_segment');

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
                    $fattura = $documenti->first(function ($item, $key) use ($id_anagrafica) {
                        return $item->anagrafica->id == $id_anagrafica;
                    });

                    // Ricerca fattura per anagrafica se l'impostazione di accodamento è selezionata
                    if (!empty($accodare) && empty($fattura)) {
                        $fattura = Fattura::where('idanagrafica', $id_anagrafica)
                            ->where('idstatodocumento', $stato_documenti_accodabili->id)
                            ->where('idtipodocumento', $tipo_documento->id)
                            ->first();

                        if (!empty($fattura)) {
                            $documenti->push($fattura);
                        }
                    }

                    // Creazione fattura per anagrafica
                    if (empty($fattura)) {
                        $fattura = Fattura::build($anagrafica, $tipo_documento, $data, $id_segment);
                        $documenti->push($fattura);
                    }

                    $fattura->idsede_destinazione = $documento_import->idsede_destinazione;
                    $fattura->save();

                    $idOrdini = [];
                    $totale = 0;

                    // Inserimento righe
                    foreach ($righe as $riga) {
                        if ($riga['idordine'] != 0) {
                            $idOrdini[] = $riga->idordine;
                        }
                        $qta = $riga->qta_rimanente;

                        if ($qta > 0) {
                            $copia = $riga->copiaIn($fattura, $qta);

                            //Fix per idconto righe fattura
                            $articolo = ArticoloOriginale::find($copia->idarticolo);
                            $copia->id_conto = ($articolo->idconto_vendita ? $articolo->idconto_vendita : $idconto);

                            // Aggiornamento seriali dalla riga dell'ordine
                            if ($copia->isArticolo()) {
                                $copia->serials = $riga->serials;
                            }

                            $copia->save();

                            $totale += ($copia->prezzo_unitario * $qta);
                        }
                    }

                    //row per gli anticipi
                    $acconti = $dbo->fetchArray(
                        'SELECT id, idanagrafica, idordine, importo
                        FROM ac_acconti
                        WHERE idordine IN ('.implode(',', $idOrdini).')'
                    );

                    if ($acconti != null) {
                        //foreach acconti
                        foreach ($acconti as $acconto) {
                            $acconto = $acconti[0];

                            //get acconto_righe
                            $acconto_righe = $dbo->fetchOne(
                                'SELECT idacconto, idfattura, sum(importo_fatturato) as da_stornare
                                FROM ac_acconti_righe
                                WHERE idacconto = '.prepare($acconto['id']).'
                                GROUP BY idacconto'
                            );

                            if ($acconto_righe['da_stornare']) {
                                $importo_rimasto = 0;
                                $calcolo = $totale - floatval($acconto_righe['da_stornare']);

                                if ($calcolo >= 0) {
                                    $totale -= floatval($acconto_righe['da_stornare']);
                                    $importo_fatturato = -1 * floatval($acconto_righe['da_stornare']);
                                } else {
                                    $importo_fatturato = -1 * ($totale);
                                    $totale = 0;
                                }

                                $fatturaAcconto = Fattura::find($acconto_righe['idfattura']);
                                $rigaAcconto = $dbo->fetchOne(
                                    'SELECT * FROM co_righe_documenti
                                    WHERE iddocumento = '.prepare($fatturaAcconto->id)
                                );

                                $iva_predefinita = setting('Iva predefinita');
                                $iva = $dbo->fetchOne(
                                    'SELECT id, descrizione, percentuale
                                    FROM co_iva
                                    WHERE id = '.prepare($iva_predefinita)
                                );

                                //aggiungo la riga fattura dell'acconto
                                $riga = Riga::build($fattura);

                                $riga->note = null;
                                $riga->um = null;
                                $riga->idarticolo = null;
                                $riga->calcolo_ritenuta_acconto = null;

                                $riga->descrizione = 'Storno acconto fattura '.$fattura->numero_esterno;

                                $riga->idiva = $iva['id'];
                                $riga->desc_iva = $iva['descrizione'];

                                $riga->idconto = $rigaAcconto['idconto'];

                                $riga->costo_unitario = 0;
                                $riga->subtotale = $importo_fatturato;
                                $riga->prezzo_unitario = $importo_fatturato;
                                $riga->prezzo_unitario_ivato = floatval($importo_fatturato) * (1 + (floatval($iva['percentuale']) / 100));

                                $riga->idordine = $acconto['idordine'];
                                $riga->qta = 1;

                                $riga->save();

                                $dbo->query(
                                    'INSERT INTO ac_acconti_righe (idacconto, idfattura, idriga_fattura, idiva, importo_fatturato, tipologia)
                                    VALUES ('.prepare($acconto_righe['idacconto']).', '.prepare($acconto_righe['idfattura']).', '.prepare($riga->id).','.prepare($riga->idiva).','.prepare($importo_fatturato).', '.prepare(tr('Storno da acconto')).')'
                                );
                            }
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

    case 'delete-bulk':
        foreach ($id_records as $id) {
            $documento = DDT::find($id);
            try {
                $documento->delete();
            } catch (InvalidArgumentException $e) {
            }
        }

        flash()->info(tr('Ddt eliminati!'));
    break;

    case 'cambia_stato':
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

if (App::debug()) {
    $operations['delete-bulk'] = [
        'text' => '<span><i class="fa fa-trash"></i> '.tr('Elimina selezionati').'</span>',
        'data' => [
            'msg' => tr('Vuoi davvero eliminare i ddt selezionati?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-danger',
        ],
    ];
}

$operations['crea_fattura'] = [
        'text' => '<span><i class="fa fa-file-code-o"></i> '.tr('Fattura _TYPE_', ['_TYPE_' => strtolower($module['name'])]),
        'data' => [
            'title' => tr('Fatturare i _TYPE_ selezionati?', ['_TYPE_' => strtolower($module['name'])]),
            'msg' => '{[ "type": "checkbox", "label": "<small>'.tr('Aggiungere alle _TYPE_ non ancora emesse?', ['_TYPE_' => strtolower($module_fatture)]).'", "placeholder": "'.tr('Aggiungere alle _TYPE_ nello stato bozza?', ['_TYPE_' => strtolower($module_fatture)]).'</small>", "name": "accodare" ]}<br>
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(["id_module" => $id_fatture, 'is_sezionale' => 1]).', "value": "'.$id_segment.'", "select-options-escape": true ]}<br>
            {[ "type": "select", "label": "'.tr('Tipo documento').'", "name": "idtipodocumento", "required": 1, "values": "query=SELECT id, CONCAT(codice_tipo_documento_fe, \' - \', descrizione) AS descrizione FROM co_tipidocumento WHERE enabled = 1 AND dir ='.prepare($dir).' ORDER BY codice_tipo_documento_fe", "value": "'.$idtipodocumento.'" ]}',
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ];

    $operations['cambia_stato'] = [
        'text' => '<span><i class="fa fa-refresh"></i> '.tr('Cambia stato'),
        'data' => [
            'title' => tr('Vuoi davvero cambiare lo stato per questi DDT?'),
            'msg' => tr('Seleziona lo stato in cui spostare tutti i DDT').'.<br>
            <br>{[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT id, descrizione FROM dt_statiddt" ]}',
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ];

return $operations;
